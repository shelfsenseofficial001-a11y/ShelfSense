<?php
// app/handlers/shared/create_leave_request.php

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

// All authenticated users (including Employees) can apply
// No restriction — employees can apply for leave

$input = json_decode(file_get_contents('php://input'), true);
$leaveType = $input['leave_type'] ?? '';
$startDate = $input['start_date'] ?? '';
$endDate = $input['end_date'] ?? '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if (empty($leaveType) || !in_array($leaveType, ['sick', 'vacation', 'emergency', 'maternity', 'other'])) {
    Response::error('Invalid leave type', 400);
}

if (empty($startDate) || empty($endDate)) {
    Response::error('Start date and end date are required', 400);
}

$today = date('Y-m-d');
if ($startDate < $today) {
    Response::error('Start date cannot be in the past', 400);
}
if ($endDate < $startDate) {
    Response::error('End date must be after start date', 400);
}

$start = new \DateTime($startDate);
$end = new \DateTime($endDate);
$diff = $start->diff($end);
$days = $diff->days + 1;
if ($days > 30) {
    Response::error('Leave request cannot exceed 30 days', 400);
}

if (strlen($reason) > 500) {
    Response::error('Reason cannot exceed 500 characters', 400);
}

$leaveModel = new Leave();
$balances = $leaveModel->getBalances(Auth::userId());
$remaining = $balances['remaining'][$leaveType] ?? 0;

if ($remaining < $days) {
    Response::error("Insufficient leave balance. You have {$remaining} days remaining for {$leaveType} leave.", 400);
}

try {
    $result = $leaveModel->create([
        'user_id' => Auth::userId(),
        'leave_type' => $leaveType,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'reason' => $reason,
        'attachment_path' => null
    ]);

    if (!$result) {
        Response::error('Failed to create leave request', 500);
    }

    // Notify HR Heads
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_id FROM users WHERE role IN ('hr_head', 'owner') AND is_active = 1");
    $stmt->execute();
    $hrUsers = $stmt->fetchAll();

    $user = Auth::user();
    $userName = $user['first_name'] . ' ' . $user['last_name'];
    foreach ($hrUsers as $hr) {
        createNotification(
            $hr['user_id'],
            'leave_request',
            "{$userName} has requested {$leaveType} leave for {$days} day(s)",
            "?page=hr_leave_requests"
        );
    }

    Response::success([
        'duration' => $days,
        'leave_type' => $leaveType
    ], 'Leave request submitted successfully');

} catch (Exception $e) {
    error_log('create_leave_request.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}