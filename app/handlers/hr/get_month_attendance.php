<?php
// app/handlers/hr/get_month_attendance.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$monthYear = $_GET['month_year'] ?? date('Y-m');

try {
    $db = Database::getInstance()->getConnection();
    list($year, $month) = explode('-', $monthYear);
    $year = intval($year);
    $month = intval($month);

    $weeks = getWeeksForMonth($year, $month);

    $weeklyStats = [];
    for ($i = 1; $i <= 4; $i++) {
        $stmt = $db->prepare("
            SELECT 
                SUM(total_days) as total_days,
                SUM(present_days) as present_days,
                SUM(late_days) as late_days,
                SUM(absent_days) as absent_days,
                SUM(leave_paid_days) as leave_paid,
                SUM(leave_unpaid_days) as leave_unpaid,
                SUM(rest_days) as rest_days,
                SUM(holiday_days) as holiday_days,
                SUM(total_overtime_hours) as overtime,
                status
            FROM attendance_weekly_summaries
            WHERE month_year = ? AND week_number = ?
            GROUP BY week_number
        ");
        $stmt->execute([$monthYear, $i]);
        $row = $stmt->fetch();

        $weekDays = 0;
        if (isset($weeks[$i - 1])) {
            $start = new DateTime($weeks[$i - 1]['start_date']);
            $end = new DateTime($weeks[$i - 1]['end_date']);
            $weekDays = $start->diff($end)->days + 1;
        }

        if ($row) {
            $weeklyStats[$i] = [
                'total_days' => $row['total_days'] ?: 0,
                'week_days' => $weekDays,
                'present_days' => $row['present_days'] ?: 0,
                'late_days' => $row['late_days'] ?: 0,
                'absent_days' => $row['absent_days'] ?: 0,
                'leave_paid_days' => $row['leave_paid'] ?: 0,
                'leave_unpaid_days' => $row['leave_unpaid'] ?: 0,
                'rest_days' => $row['rest_days'] ?: 0,
                'holiday_days' => $row['holiday_days'] ?: 0,
                'total_overtime' => $row['overtime'] ?: 0,
                'status' => $row['status'] ?? 'draft'
            ];
        } else {
            $weeklyStats[$i] = [
                'total_days' => 0,
                'week_days' => $weekDays,
                'present_days' => 0,
                'late_days' => 0,
                'absent_days' => 0,
                'leave_paid_days' => 0,
                'leave_unpaid_days' => 0,
                'rest_days' => 0,
                'holiday_days' => 0,
                'total_overtime' => 0,
                'status' => 'draft'
            ];
        }
    }

    $stmt = $db->prepare("SELECT * FROM attendance_monthly_summaries WHERE month_year = ?");
    $stmt->execute([$monthYear]);
    $monthSummary = $stmt->fetch();

    $overall = $monthSummary['overall_status'] ?? 'draft';
    $sentBy = $monthSummary['sent_by'] ?? null;
    $sentAt = $monthSummary['sent_at'] ?? null;

    $sentByName = '-';
    if ($sentBy) {
        $stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE user_id = ?");
        $stmt->execute([$sentBy]);
        $r = $stmt->fetch();
        $sentByName = $r ? $r['name'] : '-';
    }

    Response::success([
        'month_year' => $monthYear,
        'exists' => ($monthSummary !== false),
        'overall_status' => $overall,
        'sent_by' => $sentByName,
        'sent_at' => $sentAt,
        'total_employees' => $monthSummary['total_employees'] ?? 0,
        'weeks' => $weeklyStats
    ], 'Month attendance fetched');

} catch (Exception $e) {
    error_log('get_month_attendance.php error: ' . $e->getMessage());
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
    } else {
        if ($daysInMonth == 29) {
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