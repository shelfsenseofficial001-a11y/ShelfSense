<?php
// app/handlers/hr/get_employee_attendance.php

require_once __DIR__ . '/../../models/Attendance.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Attendance;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login to access this resource');
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

if ($userId <= 0) {
    Response::error('Invalid user ID', 400);
}

try {
    $attendanceModel = new Attendance();
    $attendance = $attendanceModel->getAttendance($userId, $date);
    
    Response::success([
        'attendance' => $attendance,
        'user_id' => $userId,
        'date' => $date
    ], 'Attendance fetched successfully');
} catch (Exception $e) {
    error_log('get_employee_attendance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}