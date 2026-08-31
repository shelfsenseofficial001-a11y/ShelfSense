<?php
namespace App\Models;

use App\Core\Database;

class Register
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get the store manager's register, auto-creating it on first use
     * (one register per store manager -- single-branch store today).
     */
    public function getOrCreateForStoreManager($storeManagerId)
    {
        $stmt = $this->db->prepare("SELECT * FROM registers WHERE store_manager_id = ?");
        $stmt->execute([$storeManagerId]);
        $register = $stmt->fetch();
        if ($register) {
            return $register;
        }

        $stmt = $this->db->prepare("INSERT INTO registers (store_manager_id) VALUES (?)");
        $stmt->execute([$storeManagerId]);

        $stmt = $this->db->prepare("SELECT * FROM registers WHERE store_manager_id = ?");
        $stmt->execute([$storeManagerId]);
        return $stmt->fetch();
    }

    /**
     * The currently active (not yet cashed out) allocation on a register, if any.
     */
    public function getActiveAllocation($registerId)
    {
        $stmt = $this->db->prepare("
            SELECT ra.*, u.first_name, u.last_name
            FROM register_allocations ra
            JOIN users u ON ra.cashier_id = u.user_id
            WHERE ra.register_id = ? AND ra.status = 'active'
            ORDER BY ra.opened_at DESC
            LIMIT 1
        ");
        $stmt->execute([$registerId]);
        return $stmt->fetch();
    }

    /**
     * The currently active allocation for a specific cashier, if any (used by the POS Budget tab).
     */
    public function getActiveAllocationForCashier($cashierId)
    {
        $stmt = $this->db->prepare("
            SELECT ra.*, r.name as register_name
            FROM register_allocations ra
            JOIN registers r ON ra.register_id = r.id
            WHERE ra.cashier_id = ? AND ra.status = 'active'
            ORDER BY ra.opened_at DESC
            LIMIT 1
        ");
        $stmt->execute([$cashierId]);
        return $stmt->fetch();
    }

    /**
     * Live sales totals for an allocation session, split cash vs online, from
     * completed orders tied to it (register_allocation_id set at order creation).
     */
    public function getLiveSalesForAllocation($allocationId)
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END), 0) as cash_sales,
                COALESCE(SUM(CASE WHEN payment_method != 'cash' THEN total ELSE 0 END), 0) as online_sales,
                COUNT(*) as order_count
            FROM orders
            WHERE register_allocation_id = ? AND status = 'completed'
        ");
        $stmt->execute([$allocationId]);
        $row = $stmt->fetch();
        return [
            'cash_sales' => round((float)$row['cash_sales'], 2),
            'online_sales' => round((float)$row['online_sales'], 2),
            'order_count' => (int)$row['order_count']
        ];
    }

    /**
     * Store Manager allocates a fresh cash float to a cashier on their register.
     * Fails if the register already has an active (not-yet-cashed-out) allocation.
     */
    public function allocateBudget($storeManagerId, $cashierId, $initialBudget, $notes = null)
    {
        $register = $this->getOrCreateForStoreManager($storeManagerId);

        $existing = $this->getActiveAllocation($register['id']);
        if ($existing) {
            throw new \Exception('This register already has an active budget. It must be cashed out first.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO register_allocations (register_id, cashier_id, allocated_by, initial_budget, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$register['id'], $cashierId, $storeManagerId, $initialBudget, $notes]);
            $allocationId = $this->db->lastInsertId();

            $stmt = $this->db->prepare("UPDATE registers SET status = 'open' WHERE id = ?");
            $stmt->execute([$register['id']]);

            $this->db->commit();

            return $this->getAllocationById($allocationId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getAllocationById($allocationId)
    {
        $stmt = $this->db->prepare("
            SELECT ra.*, u.first_name, u.last_name, r.store_manager_id
            FROM register_allocations ra
            JOIN users u ON ra.cashier_id = u.user_id
            JOIN registers r ON ra.register_id = r.id
            WHERE ra.id = ?
        ");
        $stmt->execute([$allocationId]);
        return $stmt->fetch();
    }

    /**
     * Cashier cash-out: pulls the float + cash sales from the drawer, snapshots
     * both cash and online totals, and frees the register for the next allocation.
     */
    public function cashOut($allocationId, $cashierId)
    {
        $allocation = $this->getAllocationById($allocationId);
        if (!$allocation || (int)$allocation['cashier_id'] !== (int)$cashierId) {
            throw new \Exception('Budget allocation not found.');
        }
        if ($allocation['status'] !== 'active') {
            throw new \Exception('This budget has already been cashed out.');
        }

        $sales = $this->getLiveSalesForAllocation($allocationId);
        $totalPulled = round((float)$allocation['initial_budget'] + $sales['cash_sales'], 2);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE register_allocations
                SET status = 'cashed_out', cash_sales = ?, online_sales = ?, total_pulled = ?, cashed_out_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$sales['cash_sales'], $sales['online_sales'], $totalPulled, $allocationId]);

            $stmt = $this->db->prepare("UPDATE registers SET status = 'closed' WHERE id = ?");
            $stmt->execute([$allocation['register_id']]);

            $this->db->commit();

            return $this->getAllocationById($allocationId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Allocation history for a store manager's register, most recent first.
     */
    public function getAllocationHistory($storeManagerId, $limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT ra.*, u.first_name, u.last_name
            FROM register_allocations ra
            JOIN registers r ON ra.register_id = r.id
            JOIN users u ON ra.cashier_id = u.user_id
            WHERE r.store_manager_id = ?
            ORDER BY ra.opened_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $storeManagerId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
