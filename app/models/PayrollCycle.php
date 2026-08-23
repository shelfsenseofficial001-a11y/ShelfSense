<?php
namespace App\Models;

use App\Core\Database;

class PayrollCycle
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payroll_cycles (
                cycle_name, start_date, end_date, payment_date,
                total_employees, total_gross, total_deductions, total_net,
                status, created_by, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?)
        ");
        $stmt->execute([
            $data['cycle_name'],
            $data['start_date'],
            $data['end_date'],
            $data['payment_date'],
            $data['total_employees'] ?? 0,
            $data['total_gross'] ?? 0,
            $data['total_deductions'] ?? 0,
            $data['total_net'] ?? 0,
            $data['created_by'],
            $data['notes'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function get($id)
    {
        $stmt = $this->db->prepare("
            SELECT pc.*, 
                   u1.first_name as approved_first, u1.last_name as approved_last,
                   u2.first_name as verified_first, u2.last_name as verified_last,
                   u3.first_name as created_first, u3.last_name as created_last
            FROM payroll_cycles pc
            LEFT JOIN users u1 ON pc.approved_by = u1.user_id
            LEFT JOIN users u2 ON pc.verified_by = u2.user_id
            LEFT JOIN users u3 ON pc.created_by = u3.user_id
            WHERE pc.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT pc.*, 
                       u1.first_name as approved_first, u1.last_name as approved_last,
                       u2.first_name as verified_first, u2.last_name as verified_last,
                       u3.first_name as created_first, u3.last_name as created_last
                FROM payroll_cycles pc
                LEFT JOIN users u1 ON pc.approved_by = u1.user_id
                LEFT JOIN users u2 ON pc.verified_by = u2.user_id
                LEFT JOIN users u3 ON pc.created_by = u3.user_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND pc.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(pc.start_date) = ?";
            $params[] = $filters['year'];
        }
        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(pc.start_date) = ?";
            $params[] = $filters['month'];
        }

        $sql .= " ORDER BY pc.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status, $userId = null)
    {
        $sql = "UPDATE payroll_cycles SET status = ?, updated_at = NOW()";
        $params = [$status];
        if ($status === 'approved' && $userId) {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $userId;
        } elseif ($status === 'verified' && $userId) {
            $sql .= ", verified_by = ?, verified_at = NOW()";
            $params[] = $userId;
        } elseif ($status === 'processed' && $userId) {
            $sql .= ", processed_by = ?, processed_at = NOW()";
            $params[] = $userId;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateTotals($id, $totalEmployees, $totalGross, $totalDeductions, $totalNet)
    {
        $stmt = $this->db->prepare("
            UPDATE payroll_cycles 
            SET total_employees = ?, total_gross = ?, total_deductions = ?, total_net = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$totalEmployees, $totalGross, $totalDeductions, $totalNet, $id]);
    }

    public function addLog($cycleId, $action, $userId, $notes = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payroll_approval_logs (payroll_cycle_id, action, action_by, notes)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$cycleId, $action, $userId, $notes]);
    }

    public function getLogs($cycleId)
    {
        $stmt = $this->db->prepare("
            SELECT pal.*, u.first_name, u.last_name
            FROM payroll_approval_logs pal
            JOIN users u ON pal.action_by = u.user_id
            WHERE pal.payroll_cycle_id = ?
            ORDER BY pal.action_at ASC
        ");
        $stmt->execute([$cycleId]);
        return $stmt->fetchAll();
    }
}