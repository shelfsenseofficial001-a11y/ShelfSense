<?php
namespace App\Models;

require_once __DIR__ . '/../core/CutoffPeriod.php';

use App\Core\Database;
use App\Core\CutoffPeriod;

class RevenueSplit
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ------------------------------------------------------------------
    // Split rules (percentage policy per department)
    // ------------------------------------------------------------------

    public function getRules()
    {
        $stmt = $this->db->query("SELECT * FROM revenue_split_rules ORDER BY is_remainder ASC, department ASC");
        return $stmt->fetchAll();
    }

    /**
     * Replace the full rule set. $rules is an array of ['department' => ..., 'percentage' => ...,
     * 'is_remainder' => bool]. Exactly one row must be marked remainder, and the non-remainder
     * percentages must not exceed 100 (the remainder department absorbs whatever's left).
     */
    public function saveRules($rules, $userId)
    {
        $remainderCount = 0;
        $sumPercentage = 0.0;
        $departments = [];

        foreach ($rules as $rule) {
            $dept = trim($rule['department'] ?? '');
            if ($dept === '' || !preg_match('/^[a-zA-Z0-9_ -]+$/', $dept) || strlen($dept) > 20) {
                throw new \Exception('Invalid department name: ' . $dept);
            }
            if (in_array($dept, $departments)) {
                throw new \Exception('Duplicate department in rules: ' . $dept);
            }
            $departments[] = $dept;

            $isRemainder = !empty($rule['is_remainder']);
            if ($isRemainder) {
                $remainderCount++;
            } else {
                $sumPercentage += (float)($rule['percentage'] ?? 0);
            }
        }

        if (count($rules) === 0) {
            throw new \Exception('At least one department rule is required.');
        }
        if ($remainderCount !== 1) {
            throw new \Exception('Exactly one department must be marked as the remainder (gets whatever is left).');
        }
        if ($sumPercentage > 100) {
            throw new \Exception('Percentages add up to more than 100%.');
        }

        $this->db->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($departments), '?'));
            $stmt = $this->db->prepare("DELETE FROM revenue_split_rules WHERE department NOT IN ($placeholders)");
            $stmt->execute($departments);

            foreach ($rules as $rule) {
                $dept = trim($rule['department']);
                $pct = !empty($rule['is_remainder']) ? 0 : round((float)($rule['percentage'] ?? 0), 2);
                $isRemainder = !empty($rule['is_remainder']) ? 1 : 0;

                $stmt = $this->db->prepare("
                    INSERT INTO revenue_split_rules (department, percentage, is_remainder, updated_by)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE percentage = ?, is_remainder = ?, updated_by = ?
                ");
                $stmt->execute([$dept, $pct, $isRemainder, $userId, $pct, $isRemainder, $userId]);
            }

            $this->db->commit();
            return $this->getRules();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ------------------------------------------------------------------
    // Cutoff periods (same semi-monthly halves used by payroll and the
    // department budgets system -- see App\Core\CutoffPeriod)
    // ------------------------------------------------------------------

    public function getHalves($year, $month)
    {
        return CutoffPeriod::getHalves($year, $month);
    }

    // ------------------------------------------------------------------
    // Compute / preview
    // ------------------------------------------------------------------

    private function getTotalRevenue($periodStart, $periodEnd)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(total), 0) as revenue
            FROM orders
            WHERE status = 'completed'
              AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
        ");
        $stmt->execute([$periodStart, $periodEnd]);
        return round((float)$stmt->fetch()['revenue'], 2);
    }

    /**
     * Get the existing draft for this exact period, if any (so recomputing a
     * period in progress updates the same row instead of piling up drafts).
     */
    public function getDraftForPeriod($periodStart, $periodEnd)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM revenue_splits
            WHERE period_start = ? AND period_end = ? AND status = 'draft'
            ORDER BY computed_at DESC LIMIT 1
        ");
        $stmt->execute([$periodStart, $periodEnd]);
        return $stmt->fetch();
    }

    /**
     * Whether this exact period has already been applied (so the UI can warn
     * before creating a correcting draft on top of it).
     */
    public function getAppliedForPeriod($periodStart, $periodEnd)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM revenue_splits
            WHERE period_start = ? AND period_end = ? AND status = 'applied'
            ORDER BY applied_at DESC LIMIT 1
        ");
        $stmt->execute([$periodStart, $periodEnd]);
        return $stmt->fetch();
    }

    /**
     * Compute (or refresh) a draft split for a period: total revenue plus the
     * per-department share breakdown using the current rules. Not written to
     * `budgets` yet -- that only happens on apply().
     */
    public function computeDraft($periodStart, $periodEnd, $periodLabel, $budgetPeriod, $userId)
    {
        $totalRevenue = $this->getTotalRevenue($periodStart, $periodEnd);
        $rules = $this->getRules();

        if (empty($rules)) {
            throw new \Exception('No revenue split rules are configured yet.');
        }

        $shares = [];
        $remainderDept = null;
        $sumNonRemainder = 0.0;

        foreach ($rules as $rule) {
            if ($rule['is_remainder']) {
                $remainderDept = $rule['department'];
                continue;
            }
            $amount = round($totalRevenue * ((float)$rule['percentage'] / 100), 2);
            $sumNonRemainder += $amount;
            $shares[] = [
                'department' => $rule['department'],
                'percentage' => (float)$rule['percentage'],
                'amount' => $amount
            ];
        }

        if ($remainderDept !== null) {
            $remainderAmount = round($totalRevenue - $sumNonRemainder, 2);
            $shares[] = [
                'department' => $remainderDept,
                'percentage' => null,
                'amount' => max(0, $remainderAmount)
            ];
        }

        $this->db->beginTransaction();
        try {
            $existingDraft = $this->getDraftForPeriod($periodStart, $periodEnd);

            if ($existingDraft) {
                $splitId = $existingDraft['id'];
                $stmt = $this->db->prepare("
                    UPDATE revenue_splits
                    SET total_revenue = ?, period_label = ?, budget_period = ?, computed_by = ?, computed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$totalRevenue, $periodLabel, $budgetPeriod, $userId, $splitId]);

                $stmt = $this->db->prepare("DELETE FROM revenue_split_shares WHERE revenue_split_id = ?");
                $stmt->execute([$splitId]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO revenue_splits (period_start, period_end, period_label, budget_period, total_revenue, computed_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$periodStart, $periodEnd, $periodLabel, $budgetPeriod, $totalRevenue, $userId]);
                $splitId = $this->db->lastInsertId();
            }

            foreach ($shares as $share) {
                $stmt = $this->db->prepare("
                    INSERT INTO revenue_split_shares (revenue_split_id, department, percentage, amount)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$splitId, $share['department'], $share['percentage'], $share['amount']]);
            }

            $this->db->commit();
            return $this->getById($splitId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM revenue_splits WHERE id = ?");
        $stmt->execute([$id]);
        $split = $stmt->fetch();
        if (!$split) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM revenue_split_shares WHERE revenue_split_id = ? ORDER BY amount DESC");
        $stmt->execute([$id]);
        $split['shares'] = $stmt->fetchAll();

        return $split;
    }

    /**
     * Apply a draft split: for each department share, add the amount on top of
     * that department's current allocated_budget for the split's cutoff period
     * (via Budget::adjustAllocation, which already writes a budget_adjustments
     * audit row), then lock the split as 'applied'.
     */
    public function apply($id, $userId)
    {
        require_once __DIR__ . '/Budget.php';
        $budgetModel = new Budget();

        $split = $this->getById($id);
        if (!$split) {
            throw new \Exception('Revenue split not found.');
        }
        if ($split['status'] !== 'draft') {
            throw new \Exception('This revenue split has already been applied.');
        }
        if (empty($split['shares'])) {
            throw new \Exception('This split has no department shares to apply.');
        }

        // Budget::adjustAllocation() runs its own transaction per department
        // (it locks that department/month's budget row), so each share is
        // applied as its own atomic step rather than nesting transactions on
        // the same connection. If one department fails mid-loop, the ones
        // already applied stay applied (visible in budget_adjustments) and
        // the split stays 'draft' so Finance Head can see and retry it.
        foreach ($split['shares'] as $share) {
            $current = $budgetModel->getOrCreate($share['department'], $split['budget_period']);
            $newAmount = round((float)$current['allocated_budget'] + (float)$share['amount'], 2);
            $budgetModel->adjustAllocation(
                $share['department'],
                $split['budget_period'],
                $newAmount,
                $userId,
                "Revenue split for {$split['period_label']} (+" . number_format($share['amount'], 2) . ")"
            );
        }

        $stmt = $this->db->prepare("
            UPDATE revenue_splits SET status = 'applied', applied_by = ?, applied_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$userId, $id]);

        return $this->getById($id);
    }

    public function getHistory($limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT rs.*, u1.first_name as computed_first, u1.last_name as computed_last,
                   u2.first_name as applied_first, u2.last_name as applied_last
            FROM revenue_splits rs
            JOIN users u1 ON rs.computed_by = u1.user_id
            LEFT JOIN users u2 ON rs.applied_by = u2.user_id
            ORDER BY rs.computed_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $splits = $stmt->fetchAll();

        foreach ($splits as &$split) {
            $stmt2 = $this->db->prepare("SELECT * FROM revenue_split_shares WHERE revenue_split_id = ? ORDER BY amount DESC");
            $stmt2->execute([$split['id']]);
            $split['shares'] = $stmt2->fetchAll();
        }

        return $splits;
    }
}
