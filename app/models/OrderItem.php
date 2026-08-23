<?php
namespace App\Models;

use App\Core\Database;

class OrderItem
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['order_id'],
            $data['product_id'],
            $data['quantity'],
            $data['price'],
            $data['subtotal']
        ]);
    }

    public function bulkCreate($items)
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
            error_log('OrderItem::bulkCreate error: ' . $e->getMessage());
            return false;
        }
    }

    public function getByOrderId($orderId)
    {
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name, p.barcode 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.created_at
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}