<?php
// app/handlers/shared/update_leave_request.php

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

// Only Department Heads, SuperAdmin can approve/reject
if (!Auth::canApprove() && !Auth::isSuperAdmin()) {
    Response::forbidden('Only Department Heads can approve/reject leave requests');
}

$input = json_decode(file_get_contents('php://input'), true);
$leaveId = isset($input['leave_id']) ? intval($input['leave_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($leaveId <= 0 || !in_array($action, ['approve', 'reject'])) {
    Response::error('Invalid request', 400);
}

try {
    $leaveModel = new Leave();
    $leave = $leaveModel->getById($leaveId);

    if (!$leave) {
        Response::notFound('Leave request not found');
    }

    if ($leave['status'] !== 'pending') {
        Response::error('Leave request has already been processed', 400);
    }

    $status = $action === 'approve' ? 'approved' : 'rejected';
    $result = $leaveModel->updateStatus($leaveId, $status, Auth::userId(), $notes);

    if (!$result) {
        Response::error('Failed to update leave request', 500);
    }

    $message = $action === 'approve' 
        ? "Your leave request has been approved" 
        : "Your leave request has been rejected" . ($notes ? ": {$notes}" : "");
    createNotification(
        $leave['user_id'],
        'leave_' . $action,
        $message,
        "?page=my_leaves"
    );

    Response::success([
        'leave_id' => $leaveId,
        'status' => $status
    ], "Leave request {$status} successfully");

} catch (Exception $e) {
    error_log('update_leave_request.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}