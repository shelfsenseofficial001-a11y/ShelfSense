<?php
// app/handlers/hr/save_schedule.php

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

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$dayOfWeek = isset($input['day_of_week']) ? trim($input['day_of_week']) : '';
$timeIn = isset($input['time_in']) && $input['time_in'] !== '' ? trim($input['time_in']) : '00:00:00';
$timeOut = isset($input['time_out']) && $input['time_out'] !== '' ? trim($input['time_out']) : '00:00:00';
$isRestDay = isset($input['is_rest_day']) ? intval($input['is_rest_day']) : 0;

if ($isRestDay == 1) {
    $timeIn = '00:00:00';
    $timeOut = '00:00:00';
}

if ($userId <= 0 || empty($dayOfWeek)) {
    Response::error('Missing required fields', 400);
}

try {
    $scheduleModel = new Schedule();
    $result = $scheduleModel->saveSchedule($userId, $dayOfWeek, $timeIn, $timeOut, $isRestDay);
    
    if ($result) {
        Response::success([
            'user_id' => $userId,
            'day_of_week' => $dayOfWeek
        ], 'Schedule saved successfully');
    } else {
        Response::error('Failed to save schedule', 500);
    }
} catch (Exception $e) {
    error_log('save_schedule.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}