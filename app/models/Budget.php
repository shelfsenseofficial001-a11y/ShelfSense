<?php
namespace App\Models;

use App\Core\Database;

/**
 * Ledger-based department budget. Every allocation, reservation, release,
 * expense, and manual adjustment is an immutable row in budget_transactions
 * -- available balance is always computed by summing the ledger, so there is
 * no separate "used_budget" snapshot that can drift out of sync with what's
 * actually reserved/spent (the old table-column model had exactly that bug).
 */
class Budget
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getDepartmentByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }

    public function getDepartmentById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAllDepartments($activeOnly = true)
    {
        $sql = "SELECT * FROM departments";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function createDepartment($name, $code = null)
    {
        $stmt = $this->db->prepare("INSERT INTO departments (name, code, is_active) VALUES (?, ?, 1)");
        $stmt->execute([trim($name), $code ? trim($code) : null]);
        return (int)$this->db->lastInsertId();
    }

    public function setDepartmentActive($id, $active)
    {
        $stmt = $this->db->prepare("UPDATE departments SET is_active = ? WHERE id = ?");
        return $stmt->execute([$active ? 1 : 0, $id]);
    }

    private function getOrCreateBudgetRow($departmentId, $periodKey)
    {
        $stmt = $this->db->prepare("SELECT * FROM budgets WHERE department_id = ? AND period_key = ?");
        $stmt->execute([$departmentId, $periodKey]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        $stmt = $this->db->prepare("INSERT IGNORE INTO budgets (department_id, period_key, allocated_amount) VALUES (?, ?, 0)");
        $stmt->execute([$departmentId, $periodKey]);
        $stmt = $this->db->prepare("SELECT * FROM budgets WHERE department_id = ? AND period_key = ?");
        $stmt->execute([$departmentId, $periodKey]);
        return $stmt->fetch();
    }

    /**
     * Sum of ledger transactions for a department/period, broken down by type.
     */
    private function getLedgerTotals($departmentId, $periodKey, $excludeReferenceType = null, $excludeReferenceId = null)
    {
        $sql = "
            SELECT type, COALESCE(SUM(amount), 0) as total
            FROM budget_transactions
            WHERE department_id = ? AND period_key = ?
        ";
        $params = [$departmentId, $periodKey];
        if ($excludeReferenceType && $excludeReferenceId) {
            $sql .= " AND NOT (reference_type = ? AND reference_id = ?)";
            $params[] = $excludeReferenceType;
            $params[] = $excludeReferenceId;
        }
        $sql .= " GROUP BY type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $totals = ['allocation' => 0.0, 'reservation' => 0.0, 'release' => 0.0, 'expense' => 0.0, 'adjustment' => 0.0];
        foreach ($stmt->fetchAll() as $row) {
            $totals[$row['type']] = (float)$row['total'];
        }
        return $totals;
    }

    /**
     * Full budget status for a department/period: allocated (allocations +
     * adjustments), reserved (reservations - releases, i.e. still-open
     * commitments), used (expenses, i.e. actually paid), available.
     *
     * $excludeReferenceType/$excludeReferenceId lets a caller evaluating a
     * specific requisition see the budget as it would be WITHOUT that
     * requisition's own not-yet-posted reservation.
     */
    public function getBudgetStatus($departmentId, $periodKey, $requestedAmount = 0.0, $excludeReferenceType = null, $excludeReferenceId = null)
    {
        $totals = $this->getLedgerTotals($departmentId, $periodKey, $excludeReferenceType, $excludeReferenceId);
        $allocated = $totals['allocation'] + $totals['adjustment'];
        $reserved = $totals['reservation'] - $totals['release'];
        $used = $totals['expense'];
        $available = $allocated - $reserved - $used;

        $requestedAmount = (float)$requestedAmount;
        $exceeded = $requestedAmount > $available;
        $shortfall = $exceeded ? round($requestedAmount - $available, 2) : 0.0;

        if ($allocated <= 0) {
            $status = 'no_budget';
        } elseif ($exceeded) {
            $status = 'exceeded';
        } elseif ($allocated > 0 && ($used + $reserved) / $allocated >= 0.9) {
            $status = 'near_limit';
        } else {
            $status = 'within_budget';
        }

        return [
            'department_id' => (int)$departmentId,
            'period_key' => $periodKey,
            'allocated' => round($allocated, 2),
            'reserved' => round($reserved, 2),
            'used' => round($used, 2),
            'available' => round($available, 2),
            'requested' => round($requestedAmount, 2),
            'exceeded' => $exceeded,
            'shortfall' => $shortfall,
            'status' => $status,
            'used_percentage' => $allocated > 0 ? round((($used + $reserved) / $allocated) * 100, 1) : null,
        ];
    }

    public function getAllDepartmentsStatus($periodKey)
    {
        $out = [];
        foreach ($this->getAllDepartments() as $dept) {
            $status = $this->getBudgetStatus($dept['id'], $periodKey);
            $status['department_name'] = $dept['name'];
            $out[] = $status;
        }
        return $out;
    }

    public function getDepartmentsNearLimit($periodKey, $thresholdPercent = 80.0)
    {
        $all = $this->getAllDepartmentsStatus($periodKey);
        return array_values(array_filter($all, function ($d) use ($thresholdPercent) {
            return $d['allocated'] > 0 && $d['used_percentage'] !== null && $d['used_percentage'] >= $thresholdPercent;
        }));
    }

    /**
     * The still-open reservation for one reference (e.g. a specific
     * requisition): sum(reservation) - sum(release) posted against it so
     * far. Used to release the FULL remaining commitment when a requisition
     * is finally paid, regardless of whether the actual invoice total ended
     * up higher or lower than the original estimate -- releasing the
     * invoice amount instead (a different figure) would leave the ledger's
     * "reserved" column permanently drifting away from zero.
     */
    public function getOpenReservation($referenceType, $referenceId)
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type = 'reservation' THEN amount ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN type = 'release' THEN amount ELSE 0 END), 0) as open_amount
            FROM budget_transactions
            WHERE reference_type = ? AND reference_id = ?
        ");
        $stmt->execute([$referenceType, $referenceId]);
        return round((float)$stmt->fetch()['open_amount'], 2);
    }

    /**
     * Posts one ledger entry. This is the only way budget_transactions is
     * ever written -- every budget movement in the system (allocation,
     * reservation on approval, release on cancel, expense on payment,
     * manual adjustment) goes through this single method.
     */
    public function postTransaction($departmentId, $periodKey, $type, $amount, $referenceType, $referenceId, $userId, $notes = null)
    {
        $this->getOrCreateBudgetRow($departmentId, $periodKey);
        $stmt = $this->db->prepare("
            INSERT INTO budget_transactions (department_id, period_key, type, amount, reference_type, reference_id, created_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$departmentId, $periodKey, $type, round((float)$amount, 2), $referenceType, $referenceId, $userId, $notes]);
    }

    /**
     * Set/increase a department's allocation for a period via an 'adjustment'
     * ledger entry (positive or negative delta from the current allocated
     * total), so the full history stays in budget_transactions itself --
     * no separate audit table needed.
     */
    public function adjustAllocation($departmentId, $periodKey, $newAllocatedAmount, $userId, $reason = null)
    {
        $current = $this->getBudgetStatus($departmentId, $periodKey);
        $delta = round((float)$newAllocatedAmount - $current['allocated'], 2);
        $this->postTransaction($departmentId, $periodKey, 'adjustment', $delta, 'manual', null, $userId, $reason);
        $updated = $this->getBudgetStatus($departmentId, $periodKey);
        return [
            'previous_allocated' => $current['allocated'],
            'new_allocated' => $updated['allocated'],
            'adjustment_amount' => $delta,
            'used' => $updated['used'],
            'reserved' => $updated['reserved'],
            'below_committed' => $updated['allocated'] < ($updated['used'] + $updated['reserved']),
        ];
    }

    public function getTransactionHistory($filters = [], $limit = 20, $offset = 0)
    {
        $where = "1=1";
        $params = [];
        if (!empty($filters['department_id'])) {
            $where .= " AND bt.department_id = ?";
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['period_key'])) {
            $where .= " AND bt.period_key = ?";
            $params[] = $filters['period_key'];
        }
        if (!empty($filters['type'])) {
            $where .= " AND bt.type = ?";
            $params[] = $filters['type'];
        }
        $sql = "
            SELECT bt.*, d.name as department_name, u.first_name, u.last_name
            FROM budget_transactions bt
            JOIN departments d ON bt.department_id = d.id
            JOIN users u ON bt.created_by = u.user_id
            WHERE $where
            ORDER BY bt.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTransactionHistoryCount($filters = [])
    {
        $where = "1=1";
        $params = [];
        if (!empty($filters['department_id'])) {
            $where .= " AND department_id = ?";
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['period_key'])) {
            $where .= " AND period_key = ?";
            $params[] = $filters['period_key'];
        }
        if (!empty($filters['type'])) {
            $where .= " AND type = ?";
            $params[] = $filters['type'];
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM budget_transactions WHERE $where");
        $stmt->execute($params);
        return (int)$stmt->fetch()['count'];
    }
}
