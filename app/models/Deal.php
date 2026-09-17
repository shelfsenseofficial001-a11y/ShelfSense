<?php
namespace App\Models;

use App\Core\Database;

class Deal
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($activeOnly = true)
    {
        $where = $activeOnly ? 'WHERE d.is_active = 1' : '';
        $stmt = $this->db->query("SELECT d.* FROM deals d $where ORDER BY d.created_at DESC");
        $deals = $stmt->fetchAll();

        foreach ($deals as &$deal) {
            $deal['items'] = $this->getItems($deal['id']);
        }

        return $deals;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM deals WHERE id = ?");
        $stmt->execute([$id]);
        $deal = $stmt->fetch();
        if ($deal) {
            $deal['items'] = $this->getItems($id);
        }
        return $deal;
    }

    public function getItems($dealId)
    {
        $stmt = $this->db->prepare("
            SELECT di.product_id, di.quantity, p.name, p.price, p.image_path, p.stock_quantity
            FROM deal_items di
            JOIN products p ON p.id = di.product_id
            WHERE di.deal_id = ?
        ");
        $stmt->execute([$dealId]);
        return $stmt->fetchAll();
    }

    /**
     * $items: [{product_id, quantity}, ...]. Runs as one transaction so a
     * deal never ends up with a price but no components or vice versa.
     */
    public function create($data, $items)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO deals (name, description, price, image_path, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['price'],
                $data['image_path'] ?? null,
                $data['is_active'] ?? 1,
                $data['created_by'] ?? null
            ]);
            $dealId = $this->db->lastInsertId();

            $itemStmt = $this->db->prepare("INSERT INTO deal_items (deal_id, product_id, quantity) VALUES (?, ?, ?)");
            foreach ($items as $item) {
                $itemStmt->execute([$dealId, $item['product_id'], $item['quantity']]);
            }

            $this->db->commit();
            return $dealId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function setActive($id, $isActive)
    {
        $stmt = $this->db->prepare("UPDATE deals SET is_active = ? WHERE id = ?");
        return $stmt->execute([$isActive ? 1 : 0, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM deals WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
