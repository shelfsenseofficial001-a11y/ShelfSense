<?php
// app/handlers/hr/get_schedule.php

require_once __DIR__ . '/../../models/Schedule.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Schedule;

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

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($userId <= 0) {
    Response::error('Invalid user ID', 400);
}

try {
    $scheduleModel = new Schedule();
    $schedule = $scheduleModel->getUserSchedule($userId);
    
    Response::success([
        'schedule' => $schedule,
        'user_id' => $userId
    ], 'Schedule fetched successfully');
} catch (Exception $e) {
    error_log('get_schedule.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}