<?php
// app/handlers/hr/send_week_to_head_hr.php

require_once __DIR__ . '/../../models/AttendanceWeeklySummary.php';
require_once __DIR__ . '/../../models/AttendanceMonthlySummary.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\AttendanceWeeklySummary;
use App\Models\AttendanceMonthlySummary;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login');
}

// Only full HR or SuperAdmin can send (not trainees)
if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot send weeks for approval.');
}

if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$monthYear = $input['month_year'] ?? '';
$weekNumber = intval($input['week_number'] ?? 0);

if (empty($monthYear) || $weekNumber < 1 || $weekNumber > 4) {
    Response::error('Month year and valid week number required.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    list($year, $month) = explode('-', $monthYear);
    $year = intval($year);
    $month = intval($month);

    $weeks = getWeeksForMonth($year, $month);
    if (!isset($weeks[$weekNumber - 1])) {
        Response::error('Invalid week number', 400);
    }
    $week = $weeks[$weekNumber - 1];
    $weekStart = $week['start_date'];
    $weekEnd = $week['end_date'];

    $summaryModel = new AttendanceWeeklySummary();
    $stmt = $db->prepare("SELECT user_id FROM users WHERE is_active = 1 AND role != 'trainee'");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($employees as $userId) {
        $summaryModel->generateForUser($userId, $weekStart, $weekEnd, $weekNumber, $monthYear);
    }

    $stmt = $db->prepare("
        SELECT COUNT(*) as missing FROM attendance_weekly_summaries
        WHERE month_year = ? AND week_number = ? AND dtr_image_path IS NULL
    ");
    $stmt->execute([$monthYear, $weekNumber]);
    $missing = $stmt->fetchColumn();
    if ($missing > 0) {
        Response::error("DTR image is missing for $missing employee(s). Please upload all DTR images before sending.", 400);
    }

    $stmt = $db->prepare("
        UPDATE attendance_weekly_summaries
        SET status = 'sent', sent_by = ?, sent_at = NOW()
        WHERE month_year = ? AND week_number = ?
    ");
    $stmt->execute([Auth::userId(), $monthYear, $weekNumber]);

    $monthModel = new AttendanceMonthlySummary();
    $monthModel->getOrCreate($monthYear);

    $headHR = getHeadHRUserId();
    if ($headHR) {
        createNotification($headHR, 'attendance_week_sent',
            "Week $weekNumber of $monthYear has been sent for approval.",
            "?page=hr_attendance_review&month=$monthYear");
    }

    Response::success([
        'month_year' => $monthYear,
        'week_number' => $weekNumber,
        'status' => 'sent'
    ], "Week $weekNumber sent for approval.");

} catch (Exception $e) {
    error_log('send_week_to_head_hr.php error: ' . $e->getMessage());
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

function getHeadHRUserId() {
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1 LIMIT 1");
    $row = $stmt->fetch();
    return $row ? $row['user_id'] : null;
}