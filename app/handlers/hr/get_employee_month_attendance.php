<?php
// app/handlers/hr/get_employee_month_attendance.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$monthYear = $_GET['month_year'] ?? date('Y-m');

if (!$userId) {
    Response::error('user_id is required');
}

try {
    $db = Database::getInstance()->getConnection();
    list($year, $month) = explode('-', $monthYear);
    $year = intval($year);
    $month = intval($month);

    $empStmt = $db->prepare("SELECT user_id, first_name, last_name, employee_number, role FROM users WHERE user_id = ?");
    $empStmt->execute([$userId]);
    $employee = $empStmt->fetch();
    if (!$employee) {
        Response::error('Employee not found');
    }

    $weeks = getWeeksForMonth($year, $month);

    $weekStmt = $db->prepare("
        SELECT week_number, week_start_date, week_end_date, status,
               total_days, present_days, late_days, absent_days,
               leave_paid_days, leave_unpaid_days, rest_days, holiday_days,
               total_overtime_hours
        FROM attendance_weekly_summaries
        WHERE user_id = ? AND month_year = ?
    ");
    $weekStmt->execute([$userId, $monthYear]);
    $weekRows = [];
    while ($row = $weekStmt->fetch()) {
        $weekRows[intval($row['week_number'])] = $row;
    }

    $weeklyStats = [];
    foreach ($weeks as $i => $range) {
        $weekNumber = $i + 1;
        $row = $weekRows[$weekNumber] ?? null;
        $weeklyStats[$weekNumber] = [
            'week_number' => $weekNumber,
            'week_start_date' => $row['week_start_date'] ?? $range['start_date'],
            'week_end_date' => $row['week_end_date'] ?? $range['end_date'],
            'status' => $row['status'] ?? 'draft',
            'total_days' => $row ? intval($row['total_days']) : 0,
            'present_days' => $row ? intval($row['present_days']) : 0,
            'late_days' => $row ? intval($row['late_days']) : 0,
            'absent_days' => $row ? intval($row['absent_days']) : 0,
            'leave_paid_days' => $row ? intval($row['leave_paid_days']) : 0,
            'leave_unpaid_days' => $row ? intval($row['leave_unpaid_days']) : 0,
            'rest_days' => $row ? intval($row['rest_days']) : 0,
            'holiday_days' => $row ? intval($row['holiday_days']) : 0,
            'total_overtime_hours' => $row ? floatval($row['total_overtime_hours']) : 0
        ];
    }

    $monthStart = sprintf('%04d-%02d-01', $year, $month);
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $monthEnd = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

    $days = [];
    $current = new DateTime($monthStart);
    $end = new DateTime($monthEnd);
    while ($current <= $end) {
        $days[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    $attStmt = $db->prepare("
        SELECT date, time_in, time_out, overtime_hours, status, notes
        FROM attendance
        WHERE user_id = ? AND date BETWEEN ? AND ?
    ");
    $attStmt->execute([$userId, $monthStart, $monthEnd]);
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

    $periodKeys = array_unique(array_map(function ($d) {
        return CutoffPeriod::getKeyForDate($d);
    }, $days));
    $overridesByPeriodDay = [];
    if (!empty($periodKeys)) {
        $placeholders = implode(',', array_fill(0, count($periodKeys), '?'));
        $overrideStmt = $db->prepare("
            SELECT period_key, day_of_week, time_in, time_out, is_rest_day
            FROM schedule_overrides
            WHERE user_id = ? AND period_key IN ($placeholders)
        ");
        $overrideStmt->execute(array_merge([$userId], $periodKeys));
        while ($row = $overrideStmt->fetch()) {
            $overridesByPeriodDay[$row['period_key']][$row['day_of_week']] = $row;
        }
    }

    $dayResult = [];
    foreach ($days as $date) {
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        $attendance = $attendanceRecords[$date] ?? null;
        $periodKey = CutoffPeriod::getKeyForDate($date);
        $schedule = $overridesByPeriodDay[$periodKey][$dayOfWeek] ?? ($scheduleRecords[$dayOfWeek] ?? null);

        $dayResult[] = [
            'date' => $date,
            'record_exists' => ($attendance !== null),
            'time_in' => $attendance ? $attendance['time_in'] : null,
            'time_out' => $attendance ? $attendance['time_out'] : null,
            'overtime_hours' => $attendance ? floatval($attendance['overtime_hours']) : 0,
            'status' => $attendance ? $attendance['status'] : null,
            'notes' => $attendance ? $attendance['notes'] : null,
            'scheduled_in' => $schedule ? $schedule['time_in'] : null,
            'scheduled_out' => $schedule ? $schedule['time_out'] : null,
            'is_rest_day' => $schedule ? intval($schedule['is_rest_day']) : 0
        ];
    }

    Response::success([
        'employee' => $employee,
        'month_year' => $monthYear,
        'weeks' => array_values($weeklyStats),
        'days' => $dayResult
    ], 'Employee month attendance fetched');

} catch (Exception $e) {
    error_log('get_employee_month_attendance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}

function getWeeksForMonth($year, $month) {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    if ($daysInMonth == 31) {
        $splits = [
            ['start' => 1, 'end' => 8],
            ['start' => 9, 'end' => 16],
            ['start' => 17, 'end' => 24],
            ['start' => 25, 'end' => 31]
        ];
    } elseif ($daysInMonth == 30) {
        $splits = [
            ['start' => 1, 'end' => 8],
            ['start' => 9, 'end' => 15],
            ['start' => 16, 'end' => 23],
            ['start' => 24, 'end' => 30]
        ];
    } else if ($daysInMonth == 29) {
        $splits = [
            ['start' => 1, 'end' => 7],
            ['start' => 8, 'end' => 15],
            ['start' => 16, 'end' => 22],
            ['start' => 23, 'end' => 29]
        ];
    } else {
        $splits = [
            ['start' => 1, 'end' => 7],
            ['start' => 8, 'end' => 14],
            ['start' => 15, 'end' => 21],
            ['start' => 22, 'end' => 28]
        ];
    }
    $weeks = [];
    foreach ($splits as $split) {
        $weeks[] = [
            'start_date' => date('Y-m-d', strtotime("$year-$month-{$split['start']}")),
            'end_date' => date('Y-m-d', strtotime("$year-$month-{$split['end']}")),
        ];
    }
    return $weeks;
}
