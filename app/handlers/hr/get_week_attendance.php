<?php
// app/handlers/hr/get_week_attendance.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;

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
            WHERE is_active = 1 AND role NOT IN ('trainee', 'supplier')";
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
    if (empty($employees)) {
        Response::success([
            'employees' => [],
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'total_employees' => 0
        ], 'Week attendance fetched successfully');
    }

    // Batch-fetch every table once for the whole employee list instead of
    // 3 queries per employee (was 3N round trips for N employees) -- the
    // same data, grouped by user_id in PHP instead of re-querying per row.
    $userIds = array_column($employees, 'user_id');
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));

    $attStmt = $db->prepare("
        SELECT user_id, date, time_in, time_out, overtime_hours, status, notes
        FROM attendance
        WHERE user_id IN ($placeholders) AND date BETWEEN ? AND ?
    ");
    $attStmt->execute(array_merge($userIds, [$weekStart, $weekEnd]));
    $attendanceByUser = [];
    while ($row = $attStmt->fetch()) {
        $attendanceByUser[$row['user_id']][$row['date']] = $row;
    }

    $scheduleStmt = $db->prepare("
        SELECT user_id, day_of_week, time_in, time_out, is_rest_day
        FROM schedules
        WHERE user_id IN ($placeholders)
    ");
    $scheduleStmt->execute($userIds);
    $scheduleByUser = [];
    while ($row = $scheduleStmt->fetch()) {
        $scheduleByUser[$row['user_id']][$row['day_of_week']] = $row;
    }

    // Per-cutoff overrides on top of the standing schedule above -- a
    // date range can span more than one H1/H2 period, so index by
    // period_key + day_of_week rather than assuming one period.
    $periodKeys = array_unique(array_map(function ($d) {
        return CutoffPeriod::getKeyForDate($d);
    }, $days));
    $overridesByUserPeriodDay = [];
    if (!empty($periodKeys)) {
        $periodPlaceholders = implode(',', array_fill(0, count($periodKeys), '?'));
        $overrideStmt = $db->prepare("
            SELECT user_id, period_key, day_of_week, time_in, time_out, is_rest_day
            FROM schedule_overrides
            WHERE user_id IN ($placeholders) AND period_key IN ($periodPlaceholders)
        ");
        $overrideStmt->execute(array_merge($userIds, $periodKeys));
        while ($row = $overrideStmt->fetch()) {
            $overridesByUserPeriodDay[$row['user_id']][$row['period_key']][$row['day_of_week']] = $row;
        }
    }

    $dtrStmt = $db->prepare("
        SELECT user_id, dtr_image_path FROM attendance_weekly_summaries
        WHERE user_id IN ($placeholders) AND week_start_date = ?
    ");
    $dtrStmt->execute(array_merge($userIds, [$weekStart]));
    $dtrByUser = [];
    while ($row = $dtrStmt->fetch()) {
        $dtrByUser[$row['user_id']] = $row['dtr_image_path'];
    }

    foreach ($employees as $employee) {
        $userId = $employee['user_id'];
        $userData = [
            'user_id' => $userId,
            'first_name' => $employee['first_name'],
            'last_name' => $employee['last_name'],
            'employee_number' => $employee['employee_number'],
            'role' => $employee['role'],
            'days' => [],
            'dtr_image_path' => $dtrByUser[$userId] ?? null
        ];

        $attendanceRecords = $attendanceByUser[$userId] ?? [];
        $scheduleRecords = $scheduleByUser[$userId] ?? [];
        $overridesByPeriodDay = $overridesByUserPeriodDay[$userId] ?? [];

        foreach ($days as $date) {
            $dayOfWeek = strtolower(date('l', strtotime($date)));
            $attendance = $attendanceRecords[$date] ?? null;
            $periodKey = CutoffPeriod::getKeyForDate($date);
            $schedule = $overridesByPeriodDay[$periodKey][$dayOfWeek] ?? ($scheduleRecords[$dayOfWeek] ?? null);

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