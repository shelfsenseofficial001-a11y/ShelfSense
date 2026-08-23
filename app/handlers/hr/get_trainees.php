<?php
// app/handlers/hr/get_trainees.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR (not trainees) can access trainees list
if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

// Trainees cannot access trainees list
if (Auth::isTrainee()) {
    Response::forbidden('Access denied. Trainees cannot access this module.');
}

try {
    $db = Database::getInstance()->getConnection();

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 15;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $role = isset($_GET['role']) ? trim($_GET['role']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (strlen($search) > 100) {
        Response::error('Search term cannot exceed 100 characters.', 400);
    }

    $where = "1=1";
    $where .= " AND a.status NOT IN ('hired', 'contract_declined')";
    $params = [];

    if ($status !== 'all') {
        $where .= " AND t.status = ?";
        $params[] = $status;
    }

    if (!empty($role) && $role !== 'all') {
        $where .= " AND t.target_role = ?";
        $params[] = $role;
    }

    if (!empty($search)) {
        $where .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $countSql = "SELECT COUNT(*) as total FROM trainees t 
                 JOIN applicants a ON t.applicant_id = a.id 
                 WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $offset = ($page - 1) * $limit;
    $sql = "SELECT 
                t.*,
                a.first_name as applicant_first_name,
                a.last_name as applicant_last_name,
                a.email as applicant_email,
                a.target_role,
                u.first_name as trainee_first_name,
                u.last_name as trainee_last_name,
                u.employee_number,
                u.can_train as trainee_can_train,
                tr.first_name as trainer_first_name,
                tr.last_name as trainer_last_name,
                tr.can_train as trainer_can_train
            FROM trainees t
            JOIN applicants a ON t.applicant_id = a.id
            JOIN users u ON t.user_id = u.user_id
            LEFT JOIN users tr ON t.trainer_id = tr.user_id
            WHERE $where
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = $db->prepare($sql);
    $queryParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($queryParams);
    $trainees = $stmt->fetchAll();

    $contractInterviewMap = [];
    $stmt = $db->query("SELECT DISTINCT applicant_id FROM interviews WHERE interview_type = 'contract' AND status = 'scheduled'");
    while ($row = $stmt->fetch()) {
        $contractInterviewMap[$row['applicant_id']] = true;
    }

    $contractMap = [];
    $stmt = $db->query("SELECT DISTINCT applicant_id FROM contracts");
    while ($row = $stmt->fetch()) {
        $contractMap[$row['applicant_id']] = true;
    }

    $statusLabels = [
        'active' => 'Active',
        'completed' => 'Completed',
        'terminated' => 'Terminated'
    ];

    $statusColors = [
        'active' => 'warning',
        'completed' => 'success',
        'terminated' => 'danger'
    ];

    foreach ($trainees as &$trainee) {
        $trainee['applicant_name'] = $trainee['applicant_first_name'] . ' ' . $trainee['applicant_last_name'];
        $trainee['trainee_name'] = $trainee['trainee_first_name'] . ' ' . $trainee['trainee_last_name'];
        $trainee['trainer_name'] = ($trainee['trainer_first_name'] ?? '') . ' ' . ($trainee['trainer_last_name'] ?? '');
        $trainee['status_label'] = $statusLabels[$trainee['status']] ?? ucfirst($trainee['status']);
        $trainee['status_color'] = $statusColors[$trainee['status']] ?? 'secondary';
        $trainee['formatted_start'] = date('M d, Y', strtotime($trainee['start_date']));
        $trainee['formatted_end'] = date('M d, Y', strtotime($trainee['end_date']));
        $trainee['schedule'] = date('h:i A', strtotime($trainee['schedule_start'])) . ' - ' . date('h:i A', strtotime($trainee['schedule_end']));

        $now = new DateTime();
        $end = new DateTime($trainee['end_date']);
        $diff = $now->diff($end);
        $trainee['days_remaining'] = $diff->days;
        $trainee['is_completed'] = $now > $end;

        $trainee['has_contract_interview'] = isset($contractInterviewMap[$trainee['applicant_id']]);
        $trainee['has_contract'] = isset($contractMap[$trainee['applicant_id']]);
        $trainee['trainer_status'] = ($trainee['trainer_can_train'] == 1) ? 'available' : 'locked';
        $trainee['trainer_status_label'] = ($trainee['trainer_can_train'] == 1) ? '🟢 Available' : '🔒 Training';
        $trainee['can_train'] = $trainee['trainee_can_train'] == 1;
    }

    $statsSql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN t.status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN t.status = 'terminated' THEN 1 ELSE 0 END) as terminated_count
                 FROM trainees t
                 JOIN applicants a ON t.applicant_id = a.id
                 WHERE a.status NOT IN ('hired', 'contract_declined')";
    $statsStmt = $db->query($statsSql);
    $stats = $statsStmt->fetch();

    Response::success([
        'trainees' => $trainees,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'stats' => [
            'total' => (int)($stats['total'] ?? 0),
            'active' => (int)($stats['active'] ?? 0),
            'completed' => (int)($stats['completed'] ?? 0),
            'terminated' => (int)($stats['terminated_count'] ?? 0)
        ],
        'filters' => ['status' => $status, 'role' => $role, 'search' => $search]
    ], 'Trainees fetched successfully');

} catch (Exception $e) {
    error_log('get_trainees.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}