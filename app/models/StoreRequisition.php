<?php
namespace App\Models;

use App\Core\Database;

class StoreRequisition
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generate a unique requisition number
     */
    public function generateNumber()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM store_requisitions WHERE YEAR(created_at) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetch()['count'] + 1;
        return 'REQ-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new requisition
     */
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO store_requisitions (
                requisition_number, created_by, supplier_id, department, budget_month_year,
                order_date, expected_delivery, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['requisition_number'],
            $data['created_by'],
            $data['supplier_id'],
            $data['department'] ?? 'store',
            $data['budget_month_year'] ?? date('Y-m'),
            $data['order_date'],
            $data['expected_delivery'] ?? null,
            $data['notes'] ?? null,
            $data['status'] ?? 'pending_supplier'
        ]);
    }

    /**
     * Get a requisition by ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   s.company_name, s.id as supplier_id,
                   u.first_name, u.last_name,
                   CONCAT(u.first_name, ' ', u.last_name) as created_by_name
            FROM store_requisitions r
            JOIN suppliers s ON r.supplier_id = s.id
            JOIN users u ON r.created_by = u.user_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get a requisition by number
     */
    public function getByNumber($number)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   s.company_name, s.id as supplier_id,
                   u.first_name, u.last_name
            FROM store_requisitions r
            JOIN suppliers s ON r.supplier_id = s.id
            JOIN users u ON r.created_by = u.user_id
            WHERE r.requisition_number = ?
        ");
        $stmt->execute([$number]);
        return $stmt->fetch();
    }

    /**
     * Get requisitions with pagination and filters
     */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['department'])) {
            $where .= " AND r.department = ?";
            $params[] = $filters['department'];
        }

        if (!empty($filters['supplier_id'])) {
            $where .= " AND r.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        if (!empty($filters['created_by'])) {
            $where .= " AND r.created_by = ?";
            $params[] = $filters['created_by'];
        }

        if (!empty($filters['budget_month_year'])) {
            $where .= " AND r.budget_month_year = ?";
            $params[] = $filters['budget_month_year'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (r.requisition_number LIKE ? OR s.company_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM store_requisitions r 
                     JOIN suppliers s ON r.supplier_id = s.id 
                     JOIN users u ON r.created_by = u.user_id
                     WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Get data
        $sql = "SELECT r.*, s.company_name, u.first_name, u.last_name
                FROM store_requisitions r
                JOIN suppliers s ON r.supplier_id = s.id
                JOIN users u ON r.created_by = u.user_id
                WHERE $where
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $requisitions = $stmt->fetchAll();

        return [
            'requisitions' => $requisitions,
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Get requisitions by status (for Finance Staff)
     */
    public function getByStatus($status, $page = 1, $limit = 20)
    {
        $filters = ['status' => $status];
        return $this->getAll($page, $limit, $filters);
    }

    /**
     * Get pending requisitions awaiting finance staff
     */
    public function getPendingForFinanceStaff($page = 1, $limit = 20)
    {
        return $this->getByStatus('awaiting_finance_staff', $page, $limit);
    }

    /**
     * Get requisitions awaiting finance head approval
     */
    public function getPendingForFinanceHead($page = 1, $limit = 20)
    {
        return $this->getByStatus('awaiting_finance', $page, $limit);
    }

    /**
     * Update requisition status
     */
    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("
            UPDATE store_requisitions SET status = ?, updated_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Update requisition (general)
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        $allowed = ['supplier_id', 'department', 'order_date', 'expected_delivery', 'notes', 'subtotal', 'total'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE store_requisitions SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get requisition items with product details
     */
    public function getItems($requisitionId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                ri.*,
                p.id as store_product_id,
                p.name as store_product_name,
                p.barcode,
                sp.id as supplier_product_id,
                sp.name as supplier_product_name,
                sp.price as supplier_price
            FROM store_requisition_items ri
            JOIN products p ON ri.store_product_id = p.id
            JOIN supplier_products sp ON ri.supplier_product_id = sp.id
            WHERE ri.requisition_id = ?
            ORDER BY ri.created_at
        ");
        $stmt->execute([$requisitionId]);
        return $stmt->fetchAll();
    }

    /**
     * Get requisition with all items
     */
    public function getWithItems($id)
    {
        $requisition = $this->getById($id);
        if (!$requisition) {
            return null;
        }
        $requisition['items'] = $this->getItems($id);
        return $requisition;
    }

    /**
     * Get requisition with invoice
     */
    public function getWithInvoice($id)
    {
        $requisition = $this->getWithItems($id);
        if (!$requisition) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM supplier_invoices 
            WHERE requisition_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $requisition['invoice'] = $stmt->fetch();

        return $requisition;
    }

    /**
     * Get requisition with goods receipt
     */
    public function getWithGoodsReceipt($id)
    {
        $requisition = $this->getWithInvoice($id);
        if (!$requisition) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT gr.*, u.first_name, u.last_name
            FROM goods_receipts gr
            JOIN users u ON gr.received_by = u.user_id
            WHERE gr.requisition_id = ?
            ORDER BY gr.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $requisition['goods_receipt'] = $stmt->fetch();

        if ($requisition['goods_receipt']) {
            $stmt = $this->db->prepare("
                SELECT gri.*, ri.quantity as ordered_quantity, p.name as product_name
                FROM goods_receipt_items gri
                JOIN store_requisition_items ri ON gri.requisition_item_id = ri.id
                JOIN products p ON ri.store_product_id = p.id
                WHERE gri.goods_receipt_id = ?
            ");
            $stmt->execute([$requisition['goods_receipt']['id']]);
            $requisition['goods_receipt']['items'] = $stmt->fetchAll();
        }

        return $requisition;
    }

    /**
     * Get requisitions by department for a specific month
     */
    public function getByDepartmentAndMonth($department, $monthYear, $page = 1, $limit = 20)
    {
        $filters = [
            'department' => $department,
            'budget_month_year' => $monthYear
        ];
        return $this->getAll($page, $limit, $filters);
    }

    /**
     * Get requisition total by status for a department and month
     */
    public function getTotalsByDepartmentAndMonth($department, $monthYear, $status = null)
    {
        $sql = "SELECT COALESCE(SUM(total), 0) as total FROM store_requisitions 
                WHERE department = ? AND budget_month_year = ?";
        $params = [$department, $monthYear];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Get stats for dashboard
     */
    public function getStats($department = null, $monthYear = null)
    {
        $where = "1=1";
        $params = [];

        if ($department) {
            $where .= " AND department = ?";
            $params[] = $department;
        }

        if ($monthYear) {
            $where .= " AND budget_month_year = ?";
            $params[] = $monthYear;
        }

        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending_supplier' OR status = 'sent_to_supplier' THEN 1 ELSE 0 END) as pending_supplier,
                    SUM(CASE WHEN status = 'supplier_processed' THEN 1 ELSE 0 END) as supplier_processed,
                    SUM(CASE WHEN status = 'awaiting_finance_staff' THEN 1 ELSE 0 END) as awaiting_finance_staff,
                    SUM(CASE WHEN status = 'awaiting_finance' THEN 1 ELSE 0 END) as awaiting_finance,
                    SUM(CASE WHEN status = 'finance_approved' THEN 1 ELSE 0 END) as finance_approved,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'finance_rejected' THEN 1 ELSE 0 END) as finance_rejected,
                    COALESCE(SUM(total), 0) as total_amount
                FROM store_requisitions
                WHERE $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Check if requisition can be forwarded to finance staff
     */
    public function canForwardToFinanceStaff($id)
    {
        $requisition = $this->getById($id);
        if (!$requisition) {
            return false;
        }
        return $requisition['status'] === 'supplier_processed';
    }

    /**
     * Check if requisition can be approved by finance head
     */
    public function canApproveFinance($id)
    {
        $requisition = $this->getById($id);
        if (!$requisition) {
            return false;
        }
        return $requisition['status'] === 'awaiting_finance';
    }

    /**
     * Check if requisition can be paid
     */
    public function canBePaid($id)
    {
        $requisition = $this->getById($id);
        if (!$requisition) {
            return false;
        }
        return $requisition['status'] === 'finance_approved';
    }

    /**
     * Check if goods can be received
     */
    public function canReceiveGoods($id)
    {
        $requisition = $this->getById($id);
        if (!$requisition) {
            return false;
        }
        return in_array($requisition['status'], ['finance_approved', 'paid', 'shipped']);
    }
}