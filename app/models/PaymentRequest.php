<?php
namespace App\Models;

use App\Core\Database;

class PaymentRequest
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new payment request.
     */
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payment_requests (
                requisition_id, supplier_invoice_id, requested_by, notes,
                budget_checked, budget_exceeded, budget_exceeded_reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $data['requisition_id'],
            $data['supplier_invoice_id'],
            $data['requested_by'],
            $data['notes'] ?? null,
            $data['budget_checked'] ?? 0,
            $data['budget_exceeded'] ?? 0,
            $data['budget_exceeded_reason'] ?? null
        ]);
        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Get payment request by ID with related data.
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT pr.*, 
                   r.requisition_number, r.total as requisition_total,
                   si.invoice_number, si.total as invoice_total,
                   u1.first_name as requested_first, u1.last_name as requested_last,
                   u2.first_name as approved_first, u2.last_name as approved_last
            FROM payment_requests pr
            JOIN store_requisitions r ON pr.requisition_id = r.id
            JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
            JOIN users u1 ON pr.requested_by = u1.user_id
            LEFT JOIN users u2 ON pr.approved_by = u2.user_id
            WHERE pr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get payment requests by status with pagination.
     */
    public function getByStatus($status = 'pending', $limit = 20, $offset = 0)
    {
        $stmt = $this->db->prepare("
            SELECT pr.*, 
                   r.requisition_number, r.total as requisition_total,
                   si.invoice_number, si.total as invoice_total,
                   u1.first_name as requested_first, u1.last_name as requested_last,
                   s.company_name
            FROM payment_requests pr
            JOIN store_requisitions r ON pr.requisition_id = r.id
            JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
            JOIN users u1 ON pr.requested_by = u1.user_id
            JOIN suppliers s ON r.supplier_id = s.id
            WHERE pr.status = ?
            ORDER BY pr.requested_at ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$status, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Get count of payment requests by status.
     */
    public function getCountByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM payment_requests WHERE status = ?");
        $stmt->execute([$status]);
        return $stmt->fetch()['count'];
    }

    /**
     * Update payment request status.
     */
    public function updateStatus($id, $status, $approvedBy = null, $rejectionReason = null)
    {
        $sql = "UPDATE payment_requests SET status = ?";
        $params = [$status];
        if ($status === 'approved' && $approvedBy) {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $approvedBy;
        }
        if ($status === 'rejected' && $rejectionReason) {
            $sql .= ", rejection_reason = ?";
            $params[] = $rejectionReason;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get the latest payment request for a requisition.
     */
    public function getForRequisition($requisitionId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM payment_requests 
            WHERE requisition_id = ? 
            ORDER BY requested_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$requisitionId]);
        return $stmt->fetch();
    }

    /**
     * Get all payment requests (optionally filtered) for history.
     */
    public function getAll($filters = [], $limit = 20, $offset = 0)
    {
        $where = "1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $where .= " AND pr.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['requested_by'])) {
            $where .= " AND pr.requested_by = ?";
            $params[] = $filters['requested_by'];
        }
        $sql = "
            SELECT pr.*, 
                   r.requisition_number, r.total as requisition_total,
                   si.invoice_number, si.total as invoice_total,
                   u1.first_name as requested_first, u1.last_name as requested_last,
                   s.company_name
            FROM payment_requests pr
            JOIN store_requisitions r ON pr.requisition_id = r.id
            JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
            JOIN users u1 ON pr.requested_by = u1.user_id
            JOIN suppliers s ON r.supplier_id = s.id
            WHERE $where
            ORDER BY pr.requested_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTotalCount($filters = [])
    {
        $where = "1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $where .= " AND pr.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['requested_by'])) {
            $where .= " AND pr.requested_by = ?";
            $params[] = $filters['requested_by'];
        }
        $sql = "SELECT COUNT(*) as count FROM payment_requests pr WHERE $where";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'];
    }
}