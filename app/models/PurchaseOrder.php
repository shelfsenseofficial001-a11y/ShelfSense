<?php
namespace App\Models;

use App\Core\Database;

class PurchaseOrder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generatePONumber()
    {
        $date = date('Ymd');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE po_number LIKE ?");
        $stmt->execute(['PO-' . $date . '-%']);
        $count = $stmt->fetch()['count'] + 1;
        return 'PO-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO purchase_orders (
                po_number, supplier_id, order_date, expected_delivery, 
                subtotal, tax, total, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $data['po_number'],
            $data['supplier_id'],
            $data['order_date'],
            $data['expected_delivery'] ?? null,
            $data['subtotal'] ?? 0,
            $data['tax'] ?? 0,
            $data['total'] ?? 0,
            $data['notes'] ?? null,
            $data['created_by']
        ]);
        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function updateTotals($id, $subtotal, $tax, $total)
    {
        $stmt = $this->db->prepare("
            UPDATE purchase_orders 
            SET subtotal = ?, tax = ?, total = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$subtotal, $tax, $total, $id]);
    }

    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND po.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['supplier_id'])) {
            $where .= " AND po.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (po.po_number LIKE ? OR s.company_name LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
        }

        $countSql = "SELECT COUNT(*) as total FROM purchase_orders po 
                     JOIN suppliers s ON po.supplier_id = s.id
                     WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $sql = "SELECT po.*, 
                       s.company_name, s.contact_person,
                       u1.first_name as created_first, u1.last_name as created_last,
                       u2.first_name as approved_first, u2.last_name as approved_last,
                       u3.first_name as received_first, u3.last_name as received_last
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.id
                LEFT JOIN users u1 ON po.created_by = u1.user_id
                LEFT JOIN users u2 ON po.approved_by = u2.user_id
                LEFT JOIN users u3 ON po.received_by = u3.user_id
                WHERE $where
                ORDER BY po.created_at DESC
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

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT po.*, 
                   s.company_name, s.contact_person, s.email, s.phone,
                   u1.first_name as created_first, u1.last_name as created_last,
                   u2.first_name as approved_first, u2.last_name as approved_last,
                   u3.first_name as received_first, u3.last_name as received_last
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN users u1 ON po.created_by = u1.user_id
            LEFT JOIN users u2 ON po.approved_by = u2.user_id
            LEFT JOIN users u3 ON po.received_by = u3.user_id
            WHERE po.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status, $userId = null)
    {
        $sql = "UPDATE purchase_orders SET status = ?";
        $params = [$status];

        if ($status === 'approved' && $userId) {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $userId;
        } elseif ($status === 'received' && $userId) {
            $sql .= ", received_by = ?, received_at = NOW()";
            $params[] = $userId;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getItems($poId)
    {
        $stmt = $this->db->prepare("
            SELECT poi.*, p.name, p.barcode, p.unit 
            FROM purchase_order_items poi
            JOIN products p ON poi.product_id = p.id
            WHERE poi.po_id = ?
        ");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function getPendingCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'pending'");
        $stmt->execute();
        return $stmt->fetch()['count'] ?? 0;
    }
}