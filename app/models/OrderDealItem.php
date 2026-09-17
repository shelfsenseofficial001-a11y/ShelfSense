<?php
namespace App\Models;

use App\Core\Database;

class OrderDealItem
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO order_deal_items (order_id, deal_id, deal_name, quantity, price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['order_id'],
            $data['deal_id'],
            $data['deal_name'],
            $data['quantity'],
            $data['price'],
            $data['subtotal']
        ]);
    }

    public function getByOrderId($orderId)
    {
        $stmt = $this->db->prepare("SELECT * FROM order_deal_items WHERE order_id = ? ORDER BY created_at");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}
