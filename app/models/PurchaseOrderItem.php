<?php
namespace App\Models;

use App\Core\Database;

class PurchaseOrderItem
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_price, total)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['po_id'],
            $data['product_id'],
            $data['quantity'],
            $data['unit_price'],
            $data['total']
        ]);
    }

    public function createBulk($items)
    {
        $this->db->beginTransaction();
        try {
            foreach ($items as $item) {
                $this->create($item);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('PurchaseOrderItem::createBulk error: ' . $e->getMessage());
            return false;
        }
    }

    public function getByPoId($poId)
    {
        $stmt = $this->db->prepare("
            SELECT poi.*, p.name, p.barcode
            FROM purchase_order_items poi
            JOIN products p ON poi.product_id = p.id
            WHERE poi.po_id = ?
        ");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function updateReceivedQuantity($id, $receivedQty)
    {
        $stmt = $this->db->prepare("UPDATE purchase_order_items SET received_quantity = ? WHERE id = ?");
        return $stmt->execute([$receivedQty, $id]);
    }
}