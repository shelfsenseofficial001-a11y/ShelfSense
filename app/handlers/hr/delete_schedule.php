<?php
// app/handlers/hr/delete_schedule.php

require_once __DIR__ . '/../../models/Schedule.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Schedule;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login to access this resource');
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$dayOfWeek = isset($input['day_of_week']) ? trim($input['day_of_week']) : '';

if ($userId <= 0 || empty($dayOfWeek)) {
    Response::error('Missing required fields', 400);
}

try {
    $scheduleModel = new Schedule();
    $result = $scheduleModel->deleteSchedule($userId, $dayOfWeek);
    
    if ($result) {
        Response::success([
            'user_id' => $userId,
            'day_of_week' => $dayOfWeek
        ], 'Schedule deleted successfully');
    } else {
        Response::error('Failed to delete schedule', 500);
    }
} catch (Exception $e) {
    error_log('delete_schedule.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}