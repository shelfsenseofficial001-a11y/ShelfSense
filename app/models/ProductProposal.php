<?php
namespace App\Models;

use App\Core\Database;

class ProductProposal
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function selectWithNames()
    {
        return "
            SELECT pp.*,
                   CONCAT(u1.first_name, ' ', u1.last_name) AS proposed_by_name,
                   s.company_name AS supplier_name,
                   c.name AS category_name,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS supplier_responded_by_name,
                   CONCAT(u3.first_name, ' ', u3.last_name) AS owner_decided_by_name
            FROM product_proposals pp
            JOIN users u1 ON u1.user_id = pp.proposed_by
            JOIN suppliers s ON s.id = pp.supplier_id
            LEFT JOIN categories c ON c.id = pp.category_id
            LEFT JOIN users u2 ON u2.user_id = pp.supplier_responded_by
            LEFT JOIN users u3 ON u3.user_id = pp.owner_decided_by
        ";
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare($this->selectWithNames() . " WHERE pp.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * $filters: status (string|array), proposed_by (int), supplier_id (int) --
     * scoping to "only this supplier's own proposals" or "only this user's
     * own proposals" is the caller's job (handlers apply that from Auth).
     */
    public function getAll($filters = [])
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where[] = "pp.status IN ($placeholders)";
            array_push($params, ...$statuses);
        }
        if (!empty($filters['proposed_by'])) {
            $where[] = "pp.proposed_by = ?";
            $params[] = $filters['proposed_by'];
        }
        if (!empty($filters['supplier_id'])) {
            $where[] = "pp.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        $sql = $this->selectWithNames() . " WHERE " . implode(' AND ', $where) . " ORDER BY pp.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function barcodeExists($barcode)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM products WHERE barcode = ?");
        $stmt->execute([$barcode]);
        return (bool)$stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO product_proposals
                (proposed_by, supplier_id, proposed_name, proposed_barcode, category_id, proposed_price, description, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_supplier')
        ");
        $stmt->execute([
            $data['proposed_by'],
            $data['supplier_id'],
            $data['proposed_name'],
            $data['proposed_barcode'],
            $data['category_id'] ?? null,
            $data['proposed_price'],
            $data['description'] ?? null,
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Supplier confirms (their own name/price/quantity, moving the
     * proposal to pending_owner) or declines (dead end, logged). Locks the
     * row first so a proposal can't be double-answered by a race.
     */
    public function supplierRespond($id, $supplierId, $action, $userId, $data)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM product_proposals WHERE id = ? AND supplier_id = ? FOR UPDATE");
            $stmt->execute([$id, $supplierId]);
            $proposal = $stmt->fetch();

            if (!$proposal) {
                throw new \Exception('Proposal not found.');
            }
            if ($proposal['status'] !== 'pending_supplier') {
                throw new \Exception('This proposal has already been responded to.');
            }

            if ($action === 'confirm') {
                $stmt = $this->db->prepare("
                    UPDATE product_proposals
                    SET status = 'pending_owner',
                        supplier_product_name = ?,
                        supplier_price = ?,
                        supplier_quantity = ?,
                        supplier_responded_by = ?,
                        supplier_responded_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['supplier_product_name'],
                    $data['supplier_price'],
                    $data['supplier_quantity'],
                    $userId,
                    $id,
                ]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE product_proposals
                    SET status = 'rejected',
                        supplier_responded_by = ?,
                        supplier_responded_at = NOW(),
                        supplier_decline_reason = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $data['reason'] ?? null, $id]);
            }

            $this->db->commit();
            return $proposal;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Owner's final sign-off. Approving is the only path in the whole app
     * that creates a new `products` row (at zero stock) plus its linked
     * `supplier_products` row -- both inserts and the proposal update
     * happen in one transaction so they can't half-succeed.
     */
    public function ownerDecide($id, $action, $userId, $data)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM product_proposals WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $proposal = $stmt->fetch();

            if (!$proposal) {
                throw new \Exception('Proposal not found.');
            }
            if ($proposal['status'] !== 'pending_owner') {
                throw new \Exception('This proposal is not awaiting Owner approval.');
            }

            if ($action === 'approve') {
                $stmt = $this->db->prepare("SELECT 1 FROM products WHERE barcode = ?");
                $stmt->execute([$proposal['proposed_barcode']]);
                if ($stmt->fetch()) {
                    throw new \Exception('A product with this barcode already exists -- the proposal needs a different barcode.');
                }

                $stmt = $this->db->prepare("
                    INSERT INTO products (barcode, name, description, category_id, price, cost, stock_quantity, reorder_level)
                    VALUES (?, ?, ?, ?, ?, ?, 0, 5)
                ");
                $stmt->execute([
                    $proposal['proposed_barcode'],
                    $proposal['proposed_name'],
                    $proposal['description'],
                    $proposal['category_id'],
                    $proposal['proposed_price'],
                    $proposal['supplier_price'],
                ]);
                $productId = (int)$this->db->lastInsertId();

                $stmt = $this->db->prepare("
                    INSERT INTO supplier_products (supplier_id, store_product_id, name, price, quantity)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $proposal['supplier_id'],
                    $productId,
                    $proposal['supplier_product_name'],
                    $proposal['supplier_price'],
                    $proposal['supplier_quantity'],
                ]);
                $supplierProductId = (int)$this->db->lastInsertId();

                $stmt = $this->db->prepare("
                    UPDATE product_proposals
                    SET status = 'approved',
                        owner_decided_by = ?,
                        owner_decided_at = NOW(),
                        resulting_product_id = ?,
                        resulting_supplier_product_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $productId, $supplierProductId, $id]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE product_proposals
                    SET status = 'rejected',
                        owner_decided_by = ?,
                        owner_decided_at = NOW(),
                        owner_reject_reason = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$userId, $data['reason'] ?? null, $id]);
            }

            $this->db->commit();
            return $proposal;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
