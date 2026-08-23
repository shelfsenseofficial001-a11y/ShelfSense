<?php
namespace App\Models;

use App\Core\Database;

class Budget
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get existing budget record without auto-creating.
     * Returns null if not found.
     */
    public function get($department, $monthYear)
    {
        $stmt = $this->db->prepare("SELECT * FROM budgets WHERE department = ? AND month_year = ?");
        $stmt->execute([$department, $monthYear]);
        return $stmt->fetch();
    }

    /**
     * Get or create (only used during payment request creation, not on page load).
     */
    public function getOrCreate($department, $monthYear)
    {
        $budget = $this->get($department, $monthYear);
        if ($budget) {
            return $budget;
        }

        // Create with INSERT IGNORE to prevent duplicates
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO budgets (department, month_year, allocated_budget, used_budget) 
            VALUES (?, ?, 0, 0)
        ");
        $stmt->execute([$department, $monthYear]);

        // Fetch the newly created record
        $stmt = $this->db->prepare("SELECT * FROM budgets WHERE department = ? AND month_year = ?");
        $stmt->execute([$department, $monthYear]);
        return $stmt->fetch();
    }

    /**
     * Update used_budget for a department/month based on approved requisitions.
     */
    public function updateUsedBudget($department, $monthYear)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(r.total), 0) as total_approved
            FROM store_requisitions r
            WHERE r.budget_month_year = ? 
              AND r.department = ?
              AND r.status IN ('finance_approved', 'paid', 'shipped', 'completed')
        ");
        $stmt->execute([$monthYear, $department]);
        $total = $stmt->fetch()['total_approved'];

        $stmt = $this->db->prepare("
            UPDATE budgets SET used_budget = ? 
            WHERE department = ? AND month_year = ?
        ");
        return $stmt->execute([$total, $department, $monthYear]);
    }

    /**
     * Check if a requisition total exceeds remaining budget.
     */
    public function checkBudget($department, $monthYear, $amount)
    {
        $budget = $this->getOrCreate($department, $monthYear);
        if (!$budget) {
            return [
                'budget' => null,
                'remaining' => 0,
                'exceeded' => true,
                'shortfall' => $amount
            ];
        }
        $remaining = $budget['allocated_budget'] - $budget['used_budget'];
        return [
            'budget' => $budget,
            'remaining' => $remaining,
            'exceeded' => $amount > $remaining,
            'shortfall' => $amount - $remaining
        ];
    }

    /**
     * Full budget status for a department/period: allocated, used (real approved/paid
     * payments), reserved (live sum of currently-pending payment requests against this
     * department/period — not a stored counter, so it can never drift or double-count),
     * available (allocated - used - reserved), and a status label.
     *
     * $excludeRequisitionId lets a caller evaluating a specific requisition's own pending
     * request see the budget as it would be WITHOUT that one request's own reservation
     * (since that reservation is "this same request", not a competing one).
     */
    public function getBudgetStatus($department, $monthYear, $requestedAmount = 0.0, $excludeRequisitionId = null)
    {
        $budget = $this->get($department, $monthYear);
        $allocated = $budget ? (float)$budget['allocated_budget'] : 0.0;
        $used = $budget ? (float)$budget['used_budget'] : 0.0;

        $sql = "
            SELECT COALESCE(SUM(r.total), 0) as reserved
            FROM payment_requests pr
            JOIN store_requisitions r ON pr.requisition_id = r.id
            WHERE pr.status = 'pending'
              AND r.department = ?
              AND r.budget_month_year = ?
        ";
        $params = [$department, $monthYear];
        if ($excludeRequisitionId) {
            $sql .= " AND r.id != ?";
            $params[] = $excludeRequisitionId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $reserved = (float)$stmt->fetch()['reserved'];

        $available = $allocated - $used - $reserved;
        $requestedAmount = (float)$requestedAmount;
        $exceeded = $requestedAmount > $available;
        $shortfall = $exceeded ? round($requestedAmount - $available, 2) : 0.0;

        if (!$budget) {
            $status = 'no_budget';
        } elseif ($exceeded) {
            $status = 'exceeded';
        } elseif ($allocated > 0 && ($used + $reserved) / $allocated >= 0.9) {
            $status = 'near_limit';
        } else {
            $status = 'within_budget';
        }

        return [
            'department' => $department,
            'month_year' => $monthYear,
            'has_allocation' => (bool)$budget,
            'allocated' => round($allocated, 2),
            'used' => round($used, 2),
            'reserved' => round($reserved, 2),
            'available' => round($available, 2),
            'requested' => round($requestedAmount, 2),
            'exceeded' => $exceeded,
            'shortfall' => $shortfall,
            'status' => $status,
            'used_percentage' => $allocated > 0 ? round((($used + $reserved) / $allocated) * 100, 1) : null
        ];
    }

    /**
     * Set allocated budget for a department/month.
     * Uses INSERT ... ON DUPLICATE KEY UPDATE.
     */
    public function setAllocatedBudget($department, $monthYear, $amount)
    {
        $stmt = $this->db->prepare("
            INSERT INTO budgets (department, month_year, allocated_budget, used_budget) 
            VALUES (?, ?, ?, 0) 
            ON DUPLICATE KEY UPDATE allocated_budget = ?
        ");
        return $stmt->execute([$department, $monthYear, $amount, $amount]);
    }

    /**
     * Get budget summary for a department/month (total, used, remaining).
     * Returns zeros if no budget exists.
     */
    public function getSummary($department, $monthYear)
    {
        $budget = $this->get($department, $monthYear);
        if (!$budget) {
            return [
                'allocated' => 0,
                'used' => 0,
                'remaining' => 0,
                'department' => $department,
                'month_year' => $monthYear,
                'exists' => false
            ];
        }
        $remaining = $budget['allocated_budget'] - $budget['used_budget'];
        return [
            'allocated' => $budget['allocated_budget'],
            'used' => $budget['used_budget'],
            'remaining' => $remaining,
            'department' => $department,
            'month_year' => $monthYear,
            'exists' => true
        ];
    }

    /**
     * Get all budgets for a month.
     */
    public function getAllForMonth($monthYear)
    {
        $stmt = $this->db->prepare("
            SELECT department, allocated_budget, used_budget 
            FROM budgets 
            WHERE month_year = ?
        ");
        $stmt->execute([$monthYear]);
        return $stmt->fetchAll();
    }
}