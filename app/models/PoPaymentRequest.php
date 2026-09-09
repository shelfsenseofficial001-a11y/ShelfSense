<?php
namespace App\Models;

use App\Core\Database;

/**
 * The real money-movement request in the pay-before-delivery flow: Finance
 * Staff requests payment for a supplier-confirmed PO, Finance Head approves
 * or rejects it. Approval is what actually posts the budget expense/release
 * and unlocks the supplier to ship -- there is no invoice involved at this
 * point (the invoice/3-way-match happens afterward, purely for
 * reconciliation, once the goods have been delivered).
 */
class PoPaymentRequest
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($poId, $requestedBy, $amount, $notes = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO po_payment_requests (po_id, requested_by, amount, notes)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$poId, $requestedBy, $amount, $notes]);
        return (int)$this->db->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT ppr.*, po.po_number, po.supplier_id, po.requisition_id, s.company_name as supplier_name,
                   CONCAT(u.first_name, ' ', u.last_name) as requested_by_name
            FROM po_payment_requests ppr
            JOIN purchase_orders po ON ppr.po_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN users u ON ppr.requested_by = u.user_id
            WHERE ppr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getPendingForPo($poId)
    {
        $stmt = $this->db->prepare("SELECT * FROM po_payment_requests WHERE po_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$poId]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status, $approvedBy = null, $rejectionReason = null)
    {
        $stmt = $this->db->prepare("
            UPDATE po_payment_requests SET status = ?, approved_by = ?, approved_at = NOW(), rejection_reason = ?
            WHERE id = ?
        ");
        return $stmt->execute([$status, $approvedBy, $rejectionReason, $id]);
    }

    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $where .= " AND ppr.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['requested_by'])) {
            $where .= " AND ppr.requested_by = ?";
            $params[] = $filters['requested_by'];
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM po_payment_requests ppr WHERE $where");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Once approved, the resulting payments row (method/reference/when)
        // is what actually happened -- surfaced here so Finance Staff can
        // see the real outcome of their own request, not just "approved".
        $sql = "
            SELECT ppr.*, po.po_number, s.company_name as supplier_name,
                   CONCAT(u.first_name, ' ', u.last_name) as requested_by_name,
                   CONCAT(approver.first_name, ' ', approver.last_name) as approved_by_name,
                   pay.method as payment_method, pay.reference_number as payment_reference, pay.paid_at as payment_paid_at
            FROM po_payment_requests ppr
            JOIN purchase_orders po ON ppr.po_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN users u ON ppr.requested_by = u.user_id
            LEFT JOIN users approver ON ppr.approved_by = approver.user_id
            LEFT JOIN payments pay ON pay.payment_request_id = ppr.id
            WHERE $where
            ORDER BY ppr.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'payment_requests' => $stmt->fetchAll(),
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => (int)ceil($total / $limit),
            ],
        ];
    }
}
