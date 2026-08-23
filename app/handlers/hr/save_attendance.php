<?php
// app/handlers/hr/save_attendance.php

require_once __DIR__ . '/../../models/Attendance.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Attendance;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = intval($input['user_id'] ?? 0);
$date = $input['date'] ?? '';
$timeIn = $input['time_in'] ?? null;
$timeOut = $input['time_out'] ?? null;
$overtime = floatval($input['overtime_hours'] ?? 0);
$status = $input['status'] ?? 'absent';
$notes = $input['notes'] ?? null;

if ($userId <= 0 || empty($date)) {
    Response::error('Missing required fields', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $dateObj = new DateTime($date);
    $weekStart = $dateObj->modify('monday this week')->format('Y-m-d');

    $stmt = $db->prepare("SELECT status FROM attendance_weekly_summaries WHERE user_id = ? AND week_start_date = ?");
    $stmt->execute([$userId, $weekStart]);
    $statusRow = $stmt->fetch();
    if ($statusRow && in_array($statusRow['status'], ['sent', 'locked', 'approved'])) {
        Response::error('Attendance for this week is already sent or approved. Edits are locked.', 400);
    }

    $attendance = new Attendance();
    $result = $attendance->save($userId, $date, [
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'overtime_hours' => $overtime,
        'status' => $status,
        'notes' => $notes,
        'recorded_by' => Auth::userId()
    ]);
    if ($result) {
        Response::success([], 'Attendance saved');
    } else {
        Response::error('Failed to save', 500);
    }
} catch (Exception $e) {
    error_log('save_attendance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}