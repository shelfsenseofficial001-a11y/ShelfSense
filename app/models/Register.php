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
     * All registers a store manager owns, most recently created first. A
     * store can now have several independent registers/POS terminals, each
     * allocated and cashed out on its own.
     */
    public function getAllForStoreManager($storeManagerId)
    {
        $stmt = $this->db->prepare("SELECT * FROM registers WHERE store_manager_id = ? ORDER BY id ASC");
        $stmt->execute([$storeManagerId]);
        return $stmt->fetchAll();
    }

    /**
     * Creates a new, unprovisioned register for a store manager, auto-named
     * "Register N" by count of registers they already own. Owner still has
     * to run createPosAccount() on it separately to give it a POS ID + PIN.
     */
    public function createForStoreManager($storeManagerId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM registers WHERE store_manager_id = ?");
        $stmt->execute([$storeManagerId]);
        $nextNumber = (int)$stmt->fetchColumn() + 1;
        $name = 'Register ' . $nextNumber;

        $stmt = $this->db->prepare("INSERT INTO registers (store_manager_id, name) VALUES (?, ?)");
        $stmt->execute([$storeManagerId, $name]);

        return $this->getById($this->db->lastInsertId());
    }

    public function getById($registerId)
    {
        $stmt = $this->db->prepare("SELECT * FROM registers WHERE id = ?");
        $stmt->execute([$registerId]);
        return $stmt->fetch();
    }

    /**
     * The currently active (not yet cashed out) allocation on a register, if
     * any. Budget floats belong to the register/POS account itself, not to a
     * specific cashier -- cashier_id (if set) only ever records who cashed
     * a session out, so it's a LEFT JOIN, never required.
     */
    public function getActiveAllocation($registerId)
    {
        $stmt = $this->db->prepare("
            SELECT ra.*, u.first_name, u.last_name
            FROM register_allocations ra
            LEFT JOIN users u ON ra.cashier_id = u.user_id
            WHERE ra.register_id = ? AND ra.status = 'active'
            ORDER BY ra.opened_at DESC
            LIMIT 1
        ");
        $stmt->execute([$registerId]);
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
     * Store Manager allocates a fresh cash float to one of their registers --
     * no cashier is picked here; whoever logs into that POS terminal later
     * picks their own name purely for sale attribution. Fails if the
     * register isn't theirs, or already has an active budget.
     */
    public function allocateBudget($registerId, $storeManagerId, $initialBudget, $notes = null)
    {
        $register = $this->getById($registerId);
        if (!$register || (int)$register['store_manager_id'] !== (int)$storeManagerId) {
            throw new \Exception('Register not found.');
        }

        $existing = $this->getActiveAllocation($register['id']);
        if ($existing) {
            throw new \Exception('This register already has an active budget. It must be cashed out first.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO register_allocations (register_id, allocated_by, initial_budget, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$register['id'], $storeManagerId, $initialBudget, $notes]);
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
            SELECT ra.*, u.first_name, u.last_name, r.store_manager_id, r.pos_id, r.name as register_name
            FROM register_allocations ra
            LEFT JOIN users u ON ra.cashier_id = u.user_id
            JOIN registers r ON ra.register_id = r.id
            WHERE ra.id = ?
        ");
        $stmt->execute([$allocationId]);
        return $stmt->fetch();
    }

    /**
     * Cash-out: pulls the float + cash sales from the drawer, snapshots both
     * cash and online totals, and frees the register for the next
     * allocation. Authority to cash out belongs to whoever is logged into
     * THIS register's POS session (checked by $registerId), not to a
     * specific cashier -- $cashierId, if given (the POS session's currently
     * picked staff member), is only recorded as who closed the drawer.
     */
    public function cashOut($allocationId, $registerId, $cashierId = null)
    {
        $allocation = $this->getAllocationById($allocationId);
        if (!$allocation || (int)$allocation['register_id'] !== (int)$registerId) {
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
                SET status = 'cashed_out', cashier_id = ?, cash_sales = ?, online_sales = ?, total_pulled = ?, cashed_out_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$cashierId, $sales['cash_sales'], $sales['online_sales'], $totalPulled, $allocationId]);

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
     * Allocation history for one specific register, most recent first.
     */
    public function getAllocationHistory($registerId, $limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT ra.*, u.first_name, u.last_name
            FROM register_allocations ra
            LEFT JOIN users u ON ra.cashier_id = u.user_id
            WHERE ra.register_id = ?
            ORDER BY ra.opened_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $registerId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------
    // POS account provisioning (Owner-only)
    // ------------------------------------------------------------------

    /**
     * All registers, with their store manager's name and whether a POS
     * account has been provisioned yet -- feeds the Owner's POS Accounts page.
     */
    public function getAllWithStoreManagers()
    {
        $stmt = $this->db->query("
            SELECT r.id, r.name, r.pos_id, r.pos_created_at, r.status,
                   u.user_id as store_manager_id, u.first_name, u.last_name, u.employee_number
            FROM registers r
            JOIN users u ON r.store_manager_id = u.user_id
            ORDER BY u.first_name ASC, r.id ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * All active store managers, for the Owner's "create POS account" form.
     * A store manager can own any number of registers, so this is never
     * filtered down to only those "without one yet".
     */
    public function getAllStoreManagers()
    {
        $stmt = $this->db->query("
            SELECT u.user_id, u.first_name, u.last_name, u.employee_number
            FROM users u
            WHERE u.role = 'store_manager' AND u.is_active = 1
            ORDER BY u.first_name ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Provisions a brand-new register (auto-named) with a POS ID + PIN for a
     * store manager. Always creates a fresh register -- a store manager can
     * have any number of registers, so there's no "already has one" case to
     * guard against here.
     * $pin must already be validated as exactly 4 digits by the caller.
     */
    public function createPosAccount($storeManagerId, $pin, $ownerId)
    {
        $register = $this->createForStoreManager($storeManagerId);

        $posId = $this->generateUniquePosId();
        $pinHash = password_hash($pin, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("
            UPDATE registers SET pos_id = ?, pin_hash = ?, pos_created_by = ?, pos_created_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$posId, $pinHash, $ownerId, $register['id']]);

        return $this->getById($register['id']);
    }

    /**
     * Resets an existing POS account's PIN (e.g. forgotten/compromised),
     * keeping the same POS ID.
     */
    public function resetPosPin($registerId, $pin)
    {
        $pinHash = password_hash($pin, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE registers SET pin_hash = ? WHERE id = ?");
        $stmt->execute([$pinHash, $registerId]);
        return $this->getById($registerId);
    }

    private function generateUniquePosId()
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
            $posId = 'POS-' . $number;
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM registers WHERE pos_id = ?");
            $stmt->execute([$posId]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $posId;
            }
        }
        return 'POS-' . substr((string)time(), -3);
    }
}
