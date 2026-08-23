<?php
namespace App\Models;

use App\Core\Database;

class Order
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                order_number, cashier_id, subtotal, total, 
                amount_paid, change_amount, payment_method, payment_reference, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $data['order_number'],
            $data['cashier_id'],
            $data['subtotal'],
            $data['total'],
            $data['amount_paid'] ?? 0,
            $data['change_amount'] ?? 0,
            $data['payment_method'],
            $data['payment_reference'] ?? null,
            $data['notes'] ?? null
        ]);
        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   u.first_name, u.last_name,
                   v.first_name as voided_first, v.last_name as voided_last
            FROM orders o
            JOIN users u ON o.cashier_id = u.user_id
            LEFT JOIN users v ON o.voided_by = v.user_id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByNumber($orderNumber)
    {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   u.first_name, u.last_name
            FROM orders o
            JOIN users u ON o.cashier_id = u.user_id
            WHERE o.order_number = ?
        ");
        $stmt->execute([$orderNumber]);
        return $stmt->fetch();
    }

    public function getForCashier($cashierId, $page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "o.cashier_id = ?";
        $params = [$cashierId];

        if (!empty($filters['date'])) {
            $where .= " AND DATE(o.created_at) = ?";
            $params[] = $filters['date'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND o.order_number LIKE ?";
            $params[] = "%" . $filters['search'] . "%";
        }

        // Count
        $countSql = "SELECT COUNT(*) as total FROM orders o WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Data
        $sql = "SELECT o.*, 
                       u.first_name, u.last_name
                FROM orders o
                JOIN users u ON o.cashier_id = u.user_id
                WHERE $where
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        return [
            'orders' => $orders,
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => ceil($total / $limit)
            ]
        ];
    }

    public function getTodaySales($cashierId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as transaction_count,
                SUM(total) as total_sales,
                SUM(amount_paid) as total_paid
            FROM orders
            WHERE cashier_id = ? 
                AND status = 'completed'
                AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$cashierId]);
        return $stmt->fetch();
    }

    public function getTopProduct($cashierId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                p.id, p.name, p.barcode,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.subtotal) as total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.cashier_id = ? 
                AND o.status = 'completed'
                AND DATE(o.created_at) = CURDATE()
            GROUP BY oi.product_id
            ORDER BY total_quantity DESC
            LIMIT 1
        ");
        $stmt->execute([$cashierId]);
        return $stmt->fetch();
    }

    public function getRecentTransactions($cashierId, $limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.cashier_id = ? AND o.status = 'completed'
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$cashierId, $limit]);
        return $stmt->fetchAll();
    }

    public function updateStatus($orderId, $status, $voidReason = null, $voidedBy = null)
    {
        if ($status === 'voided') {
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET status = 'voided', void_reason = ?, voided_by = ?, voided_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$voidReason, $voidedBy, $orderId]);
        } else {
            $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $orderId]);
        }
    }

    public function generateOrderNumber()
    {
        $date = date('Ymd');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders WHERE order_number LIKE ?");
        $stmt->execute(['POS-' . $date . '-%']);
        $count = $stmt->fetch()['count'] + 1;
        return 'POS-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}