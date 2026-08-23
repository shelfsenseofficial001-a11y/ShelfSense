<?php
// app/handlers/hr/get_week_attendance.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$weekStart = isset($_GET['week_start']) ? trim($_GET['week_start']) : date('Y-m-d', strtotime('monday this week'));
$weekEnd = isset($_GET['week_end']) ? trim($_GET['week_end']) : date('Y-m-d', strtotime('sunday this week'));
$department = isset($_GET['department']) ? trim($_GET['department']) : 'all';

try {
    $db = Database::getInstance()->getConnection();

    $sql = "SELECT user_id, first_name, last_name, employee_number, role 
            FROM users 
            WHERE is_active = 1 AND role != 'trainee'";
    if ($department !== 'all') {
        $sql .= " AND role = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$department]);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }
    $employees = $stmt->fetchAll();

    $days = [];
    $current = new DateTime($weekStart);
    $end = new DateTime($weekEnd);
    while ($current <= $end) {
        $days[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    $result = [];
    foreach ($employees as $employee) {
        $userId = $employee['user_id'];
        $userData = [
            'user_id' => $userId,
            'first_name' => $employee['first_name'],
            'last_name' => $employee['last_name'],
            'employee_number' => $employee['employee_number'],
            'role' => $employee['role'],
            'days' => [],
            'dtr_image_path' => null
        ];

        $attStmt = $db->prepare("
            SELECT date, time_in, time_out, overtime_hours, status, notes
            FROM attendance
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $attStmt->execute([$userId, $weekStart, $weekEnd]);
        $attendanceRecords = [];
        while ($row = $attStmt->fetch()) {
            $attendanceRecords[$row['date']] = $row;
        }

        $scheduleStmt = $db->prepare("
            SELECT day_of_week, time_in, time_out, is_rest_day
            FROM schedules
            WHERE user_id = ?
        ");
        $scheduleStmt->execute([$userId]);
        $scheduleRecords = [];
        while ($row = $scheduleStmt->fetch()) {
            $scheduleRecords[$row['day_of_week']] = $row;
        }

        $dtrStmt = $db->prepare("
            SELECT dtr_image_path FROM attendance_weekly_summaries
            WHERE user_id = ? AND week_start_date = ?
        ");
        $dtrStmt->execute([$userId, $weekStart]);
        $dtrRow = $dtrStmt->fetch();
        if ($dtrRow) {
            $userData['dtr_image_path'] = $dtrRow['dtr_image_path'];
        }

        foreach ($days as $date) {
            $dayOfWeek = strtolower(date('l', strtotime($date)));
            $attendance = $attendanceRecords[$date] ?? null;
            $schedule = $scheduleRecords[$dayOfWeek] ?? null;

            $userData['days'][$date] = [
                'date' => $date,
                'record_exists' => ($attendance !== null),
                'time_in' => $attendance ? $attendance['time_in'] : null,
                'time_out' => $attendance ? $attendance['time_out'] : null,
                'overtime_hours' => $attendance ? $attendance['overtime_hours'] : 0,
                'status' => $attendance ? $attendance['status'] : null,
                'notes' => $attendance ? $attendance['notes'] : null,
                'scheduled_in' => $schedule ? $schedule['time_in'] : null,
                'scheduled_out' => $schedule ? $schedule['time_out'] : null,
                'is_rest_day' => $schedule ? $schedule['is_rest_day'] : 0
            ];
        }

        $result[] = $userData;
    }

    Response::success([
        'employees' => $result,
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'total_employees' => count($result)
    ], 'Week attendance fetched successfully');

} catch (Exception $e) {
    error_log('get_week_attendance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}