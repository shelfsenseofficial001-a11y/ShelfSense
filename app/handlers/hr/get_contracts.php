<?php
// app/handlers/hr/get_contracts.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

if (!function_exists('hr_contracts_build_data')) {
/**
 * Builds a page of contracts + stats. Shared by the API endpoint (filter/
 * pagination/search) and the Contracts page's first paint.
 */
function hr_contracts_build_data(PDO $db, int $page, int $limit, string $status, string $search, string $contractType = 'all'): array {
    expireOverdueContracts($db);

    $where = "1=1";
    $params = [];

    if ($contractType !== 'all') {
        $where .= " AND c.contract_type = ?";
        $params[] = $contractType;
    }

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

    // "Virtual" rows: applicants who passed Initial Interview but have no
    // Trainee Contract record yet. They aren't real `contracts` rows (no
    // salary/schedule has been decided), but still need to be visible in
    // Trainee Contract > Pending so HR can create the contract for them --
    // that's the row's only action (see contracts.js: is_virtual rows get a
    // "Create Contract" button instead of the usual view/edit/decline set).
    $virtualRows = [];
    if ($page === 1 && in_array($contractType, ['trainee', 'all'], true) && in_array($status, ['pending', 'all'], true)) {
        $vWhere = "a.status = 'initial_passed' AND a.id NOT IN (SELECT applicant_id FROM contracts WHERE contract_type = 'trainee')";
        $vParams = [];
        if (!empty($search)) {
            $vWhere .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
            $vSearchParam = "%{$search}%";
            $vParams = [$vSearchParam, $vSearchParam, $vSearchParam];
        }
        $vStmt = $db->prepare("SELECT a.id AS applicant_id, a.first_name, a.last_name, a.email, a.target_role FROM applicants a WHERE $vWhere ORDER BY a.first_name");
        $vStmt->execute($vParams);
        foreach ($vStmt->fetchAll() as $a) {
            $virtualRows[] = [
                'id' => null,
                'is_virtual' => true,
                'applicant_id' => (int)$a['applicant_id'],
                'contract_type' => 'trainee',
                'status' => 'awaiting_contract',
                'trainee_name' => trim($a['first_name'] . ' ' . $a['last_name']),
                'applicant_email' => $a['email'],
                'target_role' => $a['target_role'],
                'employee_number' => null,
                'shift_label' => '—',
                'status_color' => 'secondary',
                'formatted_start' => '—',
                'formatted_salary' => '—',
                'formatted_salary_range' => null,
            ];
        }
    }

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
        $contract['shift_label'] = $contract['shift']
            ? ($shiftLabels[$contract['shift']] ?? ucfirst($contract['shift']))
            : (($contract['schedule_start'] && $contract['schedule_end'])
                ? date('g:i A', strtotime($contract['schedule_start'])) . ' – ' . date('g:i A', strtotime($contract['schedule_end']))
                : '—');
        $contract['status_color'] = $statusColors[$contract['status']] ?? 'secondary';
        $contract['formatted_start'] = date('M d, Y', strtotime($contract['start_date']));
        $contract['formatted_salary'] = '₱' . number_format($contract['salary'], 2);
        $contract['formatted_salary_range'] = ($contract['salary_range_min'] !== null && $contract['salary_range_max'] !== null)
            ? '₱' . number_format($contract['salary_range_min'], 2) . ' – ₱' . number_format($contract['salary_range_max'], 2)
            : null;
    }
    unset($contract);

    $contracts = array_merge($virtualRows, $contracts);
    $total += count($virtualRows);

    $statsSql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
                 FROM contracts";
    $statsStmt = $db->query($statsSql);
    $stats = $statsStmt->fetch();

    return [
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
        'filters' => ['status' => $status, 'search' => $search, 'contract_type' => $contractType]
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
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
        $contractType = isset($_GET['contract_type']) ? $_GET['contract_type'] : 'all';

        if (strlen($search) > 100) {
            Response::error('Search term cannot exceed 100 characters.', 400);
        }

        Response::success(hr_contracts_build_data($db, $page, $limit, $status, $search, $contractType), 'Contracts fetched successfully');
    } catch (Exception $e) {
        error_log('get_contracts.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}