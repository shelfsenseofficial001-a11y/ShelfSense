<?php
// app/handlers/hr/get_interviews.php

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

// Allow HR, SuperAdmin, and HR trainees
$targetRole = Auth::getNormalizedTargetRole();
$isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);

if (!Auth::canAccessModule('hr_head') && !$isHrTrainee) {
    Response::forbidden('Access denied. HR role required.');
}

// Trainees cannot view interviews
if (Auth::isTrainee() && !$isHrTrainee) {
    Response::forbidden('Access denied. Trainees cannot view interviews.');
}

try {
    $db = Database::getInstance()->getConnection();
    $currentUserId = Auth::userId();

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 15;
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (strlen($search) > 100) {
        Response::error('Search term cannot exceed 100 characters.', 400);
    }

    $where = "1=1";
    $params = [];

    if ($type !== 'all') {
        $where .= " AND i.interview_type = ?";
        $params[] = $type;
    }

    if ($status !== 'all') {
        $where .= " AND i.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $where .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $countSql = "SELECT COUNT(*) as total FROM interviews i 
                 JOIN applicants a ON i.applicant_id = a.id 
                 WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $offset = ($page - 1) * $limit;
    $sql = "SELECT 
                i.*,
                a.first_name as applicant_first_name,
                a.last_name as applicant_last_name,
                a.email as applicant_email,
                a.target_role,
                a.status as applicant_status,
                u.first_name as hr_first_name,
                u.last_name as hr_last_name
            FROM interviews i
            JOIN applicants a ON i.applicant_id = a.id
            LEFT JOIN users u ON i.hr_user_id = u.user_id
            WHERE $where
            ORDER BY i.scheduled_date DESC
            LIMIT ? OFFSET ?";

    $stmt = $db->prepare($sql);
    $queryParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($queryParams);
    $interviews = $stmt->fetchAll();

    $finalMap = [];
    $finalStmt = $db->query("SELECT DISTINCT applicant_id FROM interviews WHERE interview_type = 'final'");
    while ($row = $finalStmt->fetch()) {
        $finalMap[$row['applicant_id']] = true;
    }

    $traineeMap = [];
    $traineeStmt = $db->query("SELECT DISTINCT applicant_id FROM trainees");
    while ($row = $traineeStmt->fetch()) {
        $traineeMap[$row['applicant_id']] = true;
    }

    $typeLabels = [
        'initial' => 'Initial Interview',
        'final' => 'Final Interview',
        'contract' => 'Contract Interview'
    ];

    $statusColors = [
        'scheduled' => 'warning',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];

    $resultColors = [
        'passed' => 'success',
        'failed' => 'danger',
        'pending' => 'secondary'
    ];

    foreach ($interviews as &$interview) {
        $interview['type_label'] = $typeLabels[$interview['interview_type']] ?? ucfirst($interview['interview_type']);
        $interview['status_color'] = $statusColors[$interview['status']] ?? 'secondary';
        $interview['result_color'] = $resultColors[$interview['result']] ?? 'secondary';
        $interview['applicant_name'] = $interview['applicant_first_name'] . ' ' . $interview['applicant_last_name'];
        $interview['hr_name'] = ($interview['hr_first_name'] ?? '') . ' ' . ($interview['hr_last_name'] ?? '');
        $interview['formatted_date'] = date('M d, Y', strtotime($interview['scheduled_date']));
        $interview['formatted_time'] = date('h:i A', strtotime($interview['scheduled_date']));
        $interview['applicant_id'] = (int)$interview['applicant_id'];
        $interview['has_final_interview'] = false;
        $interview['is_current_hr'] = false;
        $interview['has_trainee_account'] = false;
        $interview['has_contract'] = false;

        if ($interview['interview_type'] === 'initial') {
            $interview['has_final_interview'] = isset($finalMap[$interview['applicant_id']]);
            $interview['is_current_hr'] = ($interview['hr_user_id'] == $currentUserId);
        }

        if ($interview['interview_type'] === 'final') {
            $interview['has_trainee_account'] = isset($traineeMap[$interview['applicant_id']]);
        }

        if ($interview['interview_type'] === 'contract') {
            $stmt = $db->prepare("SELECT id FROM contracts WHERE applicant_id = ?");
            $stmt->execute([$interview['applicant_id']]);
            $contractExists = $stmt->fetch();
            if ($contractExists) {
                $interview['has_contract'] = true;
                $interview['result'] = 'passed';
                $interview['result_color'] = 'success';
            }
        }
    }

    $statsSql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN i.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN i.status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN i.result = 'passed' THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN i.result = 'failed' THEN 1 ELSE 0 END) as failed
                 FROM interviews i";
    $statsStmt = $db->query($statsSql);
    $stats = $statsStmt->fetch();

    Response::success([
        'interviews' => $interviews,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'stats' => [
            'total' => (int)($stats['total'] ?? 0),
            'scheduled' => (int)($stats['scheduled'] ?? 0),
            'completed' => (int)($stats['completed'] ?? 0),
            'passed' => (int)($stats['passed'] ?? 0),
            'failed' => (int)($stats['failed'] ?? 0)
        ],
        'filters' => ['type' => $type, 'status' => $status, 'search' => $search]
    ], 'Interviews fetched successfully');

} catch (Exception $e) {
    error_log('get_interviews.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}