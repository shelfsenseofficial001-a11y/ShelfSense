<?php
namespace App\Models;

use App\Core\Database;

class Leave
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason, attachment_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['leave_type'],
            $data['start_date'],
            $data['end_date'],
            $data['reason'] ?? null,
            $data['attachment_path'] ?? null
        ]);
    }

    public function getForUser($userId, $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;

        $countSql = "SELECT COUNT(*) as total FROM leaves WHERE user_id = ?";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute([$userId]);
        $total = $stmt->fetch()['total'];

        $sql = "SELECT l.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as approved_by_name
                FROM leaves l
                LEFT JOIN users u ON l.approved_by = u.user_id
                WHERE l.user_id = ?
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        $leaves = $stmt->fetchAll();

        return [
            'leaves' => $leaves,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $limit,
                'totalRecords' => (int)$total,
                'totalPages' => ceil($total / $limit)
            ]
        ];
    }

    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND l.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where .= " AND l.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['leave_type'])) {
            $where .= " AND l.leave_type = ?";
            $params[] = $filters['leave_type'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
        }

        $countSql = "SELECT COUNT(*) as total FROM leaves l 
                     JOIN users u ON l.user_id = u.user_id
                     WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $sql = "SELECT l.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as employee_name,
                       u.employee_number,
                       CONCAT(a.first_name, ' ', a.last_name) as approved_by_name
                FROM leaves l
                JOIN users u ON l.user_id = u.user_id
                LEFT JOIN users a ON l.approved_by = a.user_id
                WHERE $where
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $leaves = $stmt->fetchAll();

        return [
            'leaves' => $leaves,
            'pagination' => [
                'currentPage' => $page,
                'perPage' => $limit,
                'totalRecords' => (int)$total,
                'totalPages' => ceil($total / $limit)
            ]
        ];
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as employee_name,
                   u.employee_number,
                   CONCAT(a.first_name, ' ', a.last_name) as approved_by_name
            FROM leaves l
            JOIN users u ON l.user_id = u.user_id
            LEFT JOIN users a ON l.approved_by = a.user_id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getBalances($userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                sick_leave_balance,
                vacation_leave_balance,
                emergency_leave_balance,
                maternity_leave_balance,
                other_leave_balance
            FROM users
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $balances = $stmt->fetch();

        // Get used leaves for the current year
        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN leave_type = 'sick' AND status = 'approved' THEN 1 ELSE 0 END) as sick_used,
                SUM(CASE WHEN leave_type = 'vacation' AND status = 'approved' THEN 1 ELSE 0 END) as vacation_used,
                SUM(CASE WHEN leave_type = 'emergency' AND status = 'approved' THEN 1 ELSE 0 END) as emergency_used,
                SUM(CASE WHEN leave_type = 'maternity' AND status = 'approved' THEN 1 ELSE 0 END) as maternity_used,
                SUM(CASE WHEN leave_type = 'other' AND status = 'approved' THEN 1 ELSE 0 END) as other_used
            FROM leaves
            WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW()) AND status = 'approved'
        ");
        $stmt->execute([$userId]);
        $used = $stmt->fetch();

        return [
            'entitled' => [
                'sick' => (float)($balances['sick_leave_balance'] ?? 15),
                'vacation' => (float)($balances['vacation_leave_balance'] ?? 15),
                'emergency' => (float)($balances['emergency_leave_balance'] ?? 5),
                'maternity' => (float)($balances['maternity_leave_balance'] ?? 60),
                'other' => (float)($balances['other_leave_balance'] ?? 0)
            ],
            'used' => [
                'sick' => (int)($used['sick_used'] ?? 0),
                'vacation' => (int)($used['vacation_used'] ?? 0),
                'emergency' => (int)($used['emergency_used'] ?? 0),
                'maternity' => (int)($used['maternity_used'] ?? 0),
                'other' => (int)($used['other_used'] ?? 0)
            ],
            'remaining' => [
                'sick' => (float)($balances['sick_leave_balance'] ?? 15) - (int)($used['sick_used'] ?? 0),
                'vacation' => (float)($balances['vacation_leave_balance'] ?? 15) - (int)($used['vacation_used'] ?? 0),
                'emergency' => (float)($balances['emergency_leave_balance'] ?? 5) - (int)($used['emergency_used'] ?? 0),
                'maternity' => (float)($balances['maternity_leave_balance'] ?? 60) - (int)($used['maternity_used'] ?? 0),
                'other' => (float)($balances['other_leave_balance'] ?? 0) - (int)($used['other_used'] ?? 0)
            ]
        ];
    }

    public function updateStatus($id, $status, $approvedBy = null, $notes = null)
    {
        $sql = "UPDATE leaves SET status = ?";
        $params = [$status];

        if ($status === 'approved' && $approvedBy) {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $approvedBy;
        }

        if ($notes !== null) {
            $sql .= ", notes = ?";
            $params[] = $notes;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getPendingCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'");
        $stmt->execute();
        return $stmt->fetch()['count'] ?? 0;
    }
}