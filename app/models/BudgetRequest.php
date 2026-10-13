<?php
namespace App\Models;

use App\Core\Database;

/**
 * A department's request for Finance to set its budget allocation to a
 * given amount, with a reason -- Finance Head approves (applies it via
 * Budget::adjustAllocation(), same effect as their existing direct "Set
 * Allocation" action) or rejects with a reason of their own.
 */
class BudgetRequest
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($departmentId, $periodKey, $amount, $reason, $requestedBy)
    {
        $stmt = $this->db->prepare("
            INSERT INTO budget_requests (department_id, period_key, requested_amount, reason, requested_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$departmentId, $periodKey, $amount, $reason, $requestedBy]);
        return (int)$this->db->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT br.*, d.name as department_name,
                   CONCAT(u.first_name, ' ', u.last_name) as requested_by_name
            FROM budget_requests br
            JOIN departments d ON br.department_id = d.id
            JOIN users u ON br.requested_by = u.user_id
            WHERE br.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll($filters = [])
    {
        $where = "1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $where .= " AND br.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['requested_by'])) {
            $where .= " AND br.requested_by = ?";
            $params[] = $filters['requested_by'];
        }
        if (!empty($filters['department_id'])) {
            $where .= " AND br.department_id = ?";
            $params[] = $filters['department_id'];
        }

        $stmt = $this->db->prepare("
            SELECT br.*, d.name as department_name,
                   CONCAT(u.first_name, ' ', u.last_name) as requested_by_name,
                   CONCAT(r.first_name, ' ', r.last_name) as reviewed_by_name
            FROM budget_requests br
            JOIN departments d ON br.department_id = d.id
            JOIN users u ON br.requested_by = u.user_id
            LEFT JOIN users r ON br.reviewed_by = r.user_id
            WHERE $where
            ORDER BY br.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markApproved($id, $reviewedBy)
    {
        $stmt = $this->db->prepare("UPDATE budget_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        return $stmt->execute([$reviewedBy, $id]);
    }

    public function markRejected($id, $reviewedBy, $rejectionReason)
    {
        $stmt = $this->db->prepare("UPDATE budget_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?");
        return $stmt->execute([$reviewedBy, $rejectionReason, $id]);
    }
}
