<?php
// app/handlers/shared/get_leave_requests.php

require_once __DIR__ . '/../../models/Leave.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Leave;

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
    $leaveModel = new Leave();
    $userId = Auth::userId();

    // Department Heads and SuperAdmin can see all; others see only their own
    if (Auth::canApprove() || Auth::isSuperAdmin()) {
        $filters = [];
        if ($status) $filters['status'] = $status;
        if ($leaveType) $filters['leave_type'] = $leaveType;
        if ($search) $filters['search'] = $search;
        $result = $leaveModel->getAll($page, $limit, $filters);
    } else {
        $result = $leaveModel->getForUser($userId, $page, $limit);
    }

    foreach ($result['leaves'] as &$leave) {
        $leave['formatted_start'] = date('M d, Y', strtotime($leave['start_date']));
        $leave['formatted_end'] = date('M d, Y', strtotime($leave['end_date']));
        $leave['duration'] = (new \DateTime($leave['start_date']))->diff(new \DateTime($leave['end_date']))->days + 1;
    }

    Response::success([
        'leaves' => $result['leaves'],
        'pagination' => $result['pagination']
    ], 'Leave requests fetched successfully');

} catch (Exception $e) {
    error_log('get_leave_requests.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}