<?php
namespace App\Models;

require_once __DIR__ . '/../core/CutoffPeriod.php';

use App\Core\Database;
use App\Core\CutoffPeriod;

class Requisition
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generateNumber()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM requisitions WHERE YEAR(created_at) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetch()['count'] + 1;
        return 'REQ-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO requisitions (
                requisition_number, requested_by, department_id, preferred_supplier_id,
                period_key, status, order_date, needed_by_date, subtotal, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['requisition_number'],
            $data['requested_by'],
            $data['department_id'],
            $data['preferred_supplier_id'],
            $data['period_key'] ?? CutoffPeriod::getCurrentKey(),
            $data['status'] ?? 'pending_budget_check',
            $data['order_date'],
            $data['needed_by_date'] ?? null,
            $data['subtotal'] ?? 0,
            $data['notes'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, s.company_name as supplier_name, d.name as department_name,
                   CONCAT(u.first_name, ' ', u.last_name) as requested_by_name
            FROM requisitions r
            JOIN suppliers s ON r.preferred_supplier_id = s.id
            JOIN departments d ON r.department_id = d.id
            JOIN users u ON r.requested_by = u.user_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getItems($requisitionId)
    {
        $stmt = $this->db->prepare("
            SELECT ri.*, p.name as store_product_name, p.barcode,
                   sp.name as supplier_product_name, sp.price as supplier_price
            FROM requisition_items ri
            JOIN products p ON ri.store_product_id = p.id
            JOIN supplier_products sp ON ri.supplier_product_id = sp.id
            WHERE ri.requisition_id = ?
            ORDER BY ri.id
        ");
        $stmt->execute([$requisitionId]);
        return $stmt->fetchAll();
    }

    public function getWithItems($id)
    {
        $requisition = $this->getById($id);
        if (!$requisition) {
            return null;
        }
        $requisition['items'] = $this->getItems($id);
        return $requisition;
    }

    public function addItem($requisitionId, $storeProductId, $supplierProductId, $quantity, $unitPrice, $notes = null)
    {
        $total = round($quantity * $unitPrice, 2);
        $stmt = $this->db->prepare("
            INSERT INTO requisition_items (requisition_id, store_product_id, supplier_product_id, quantity, estimated_unit_price, estimated_total, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$requisitionId, $storeProductId, $supplierProductId, $quantity, $unitPrice, $total, $notes]);
        return $total;
    }

    public function updateStatus($id, $status, $extra = [])
    {
        $fields = ['status = ?'];
        $params = [$status];
        if (array_key_exists('rejected_reason', $extra)) {
            $fields[] = 'rejected_reason = ?';
            $params[] = $extra['rejected_reason'];
        }
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE requisitions SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?");
        return $stmt->execute($params);
    }

    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['requested_by'])) {
            $where .= " AND r.requested_by = ?";
            $params[] = $filters['requested_by'];
        }
        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $placeholders = implode(',', array_fill(0, count($filters['statuses']), '?'));
            $where .= " AND r.status IN ($placeholders)";
            foreach ($filters['statuses'] as $s) {
                $params[] = $s;
            }
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM requisitions r WHERE $where");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        $sql = "
            SELECT r.*, s.company_name as supplier_name, d.name as department_name,
                   CONCAT(u.first_name, ' ', u.last_name) as requested_by_name
            FROM requisitions r
            JOIN suppliers s ON r.preferred_supplier_id = s.id
            JOIN departments d ON r.department_id = d.id
            JOIN users u ON r.requested_by = u.user_id
            WHERE $where
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'requisitions' => $stmt->fetchAll(),
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => (int)ceil($total / $limit),
            ],
        ];
    }
}
