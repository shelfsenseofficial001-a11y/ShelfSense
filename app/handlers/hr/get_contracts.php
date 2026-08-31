<?php
// app/handlers/hr/get_contracts.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR can access contracts (not trainees)
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot access contracts.');
}

try {
    $db = Database::getInstance()->getConnection();
    
    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 15;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (strlen($search) > 100) {
        Response::error('Search term cannot exceed 100 characters.', 400);
    }

    $where = "1=1";
    $params = [];

    if ($status !== 'all') {
        $where .= " AND c.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $where .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $countSql = "SELECT COUNT(*) as total FROM contracts c 
                 JOIN applicants a ON c.applicant_id = a.id 
                 WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $offset = ($page - 1) * $limit;
    $sql = "SELECT 
                c.*,
                a.first_name as applicant_first_name,
                a.last_name as applicant_last_name,
                a.email as applicant_email,
                a.target_role,
                u.first_name as trainee_first_name,
                u.last_name as trainee_last_name,
                u.employee_number
            FROM contracts c
            JOIN applicants a ON c.applicant_id = a.id
            JOIN users u ON c.user_id = u.user_id
            WHERE $where
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($sql);
    $queryParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($queryParams);
    $contracts = $stmt->fetchAll();

    $shiftLabels = [
        'opening' => 'Opening (6am-2pm)',
        'closing' => 'Closing (2pm-10pm)',
        'midshift' => 'MidShift (10am-6pm)'
    ];

    $statusColors = [
        'pending' => 'warning',
        'accepted' => 'success',
        'declined' => 'danger'
    ];

    foreach ($contracts as &$contract) {
        $contract['applicant_name'] = $contract['applicant_first_name'] . ' ' . $contract['applicant_last_name'];
        $contract['trainee_name'] = $contract['trainee_first_name'] . ' ' . $contract['trainee_last_name'];
        $contract['shift_label'] = $shiftLabels[$contract['shift']] ?? ucfirst($contract['shift']);
        $contract['status_color'] = $statusColors[$contract['status']] ?? 'secondary';
        $contract['formatted_start'] = date('M d, Y', strtotime($contract['start_date']));
        $contract['formatted_salary'] = '₱' . number_format($contract['salary'], 2);
        $contract['formatted_salary_range'] = ($contract['salary_range_min'] !== null && $contract['salary_range_max'] !== null)
            ? '₱' . number_format($contract['salary_range_min'], 2) . ' – ₱' . number_format($contract['salary_range_max'], 2)
            : null;
    }

    $statsSql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
                 FROM contracts";
    $statsStmt = $db->query($statsSql);
    $stats = $statsStmt->fetch();

    Response::success([
        'contracts' => $contracts,
        'pagination' => [
            'currentPage' => $page,
            'perPage' => $limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'stats' => [
            'total' => (int)($stats['total'] ?? 0),
            'pending' => (int)($stats['pending'] ?? 0),
            'accepted' => (int)($stats['accepted'] ?? 0),
            'declined' => (int)($stats['declined'] ?? 0)
        ],
        'filters' => ['status' => $status, 'search' => $search]
    ], 'Contracts fetched successfully');

} catch (Exception $e) {
    error_log('get_contracts.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}