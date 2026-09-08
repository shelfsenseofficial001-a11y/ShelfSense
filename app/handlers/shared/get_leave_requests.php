<?php
// app/handlers/shared/get_leave_requests.php

require_once __DIR__ . '/../../models/Leave.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Leave;

if (!function_exists('leave_requests_build_data')) {
/**
 * Builds a page of leave requests. Shared by the API endpoint (filter/
 * pagination/search) and the Leave Management + My Leaves pages' first
 * paint. $canApprove decides all-leaves-with-filters vs own-leaves-only,
 * matching the same branch the endpoint itself used to make inline.
 */
function leave_requests_build_data(int $userId, bool $canApprove, int $page, int $limit, array $filters): array {
    $leaveModel = new Leave();

    if ($canApprove) {
        $result = $leaveModel->getAll($page, $limit, $filters);
    } else {
        $result = $leaveModel->getForUser($userId, $page, $limit);
    }

    foreach ($result['leaves'] as &$leave) {
        $leave['formatted_start'] = date('M d, Y', strtotime($leave['start_date']));
        $leave['formatted_end'] = date('M d, Y', strtotime($leave['end_date']));
        $leave['duration'] = (new \DateTime($leave['start_date']))->diff(new \DateTime($leave['end_date']))->days + 1;
    }

    return [
        'leaves' => $result['leaves'],
        'pagination' => $result['pagination']
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login to access this resource');
    }

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $leaveType = isset($_GET['leave_type']) ? trim($_GET['leave_type']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    try {
        $canApprove = Auth::canApprove() || Auth::isSuperAdmin();
        $filters = [];
        if ($status) $filters['status'] = $status;
        if ($leaveType) $filters['leave_type'] = $leaveType;
        if ($search) $filters['search'] = $search;

        Response::success(
            leave_requests_build_data(Auth::userId(), $canApprove, $page, $limit, $filters),
            'Leave requests fetched successfully'
        );
    } catch (Exception $e) {
        error_log('get_leave_requests.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}