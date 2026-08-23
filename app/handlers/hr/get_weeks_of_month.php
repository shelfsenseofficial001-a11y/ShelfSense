<?php
// app/handlers/hr/get_weeks_of_month.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Allow HR, SuperAdmin, and HR trainees
if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

if ($month < 1 || $month > 12 || $year < 2000) {
    Response::error('Invalid month or year', 400);
}

try {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    if ($daysInMonth == 31) {
        $weeks = [
            ['start' => 1, 'end' => 8],
            ['start' => 9, 'end' => 16],
            ['start' => 17, 'end' => 24],
            ['start' => 25, 'end' => 31]
        ];
    } elseif ($daysInMonth == 30) {
        $weeks = [
            ['start' => 1, 'end' => 8],
            ['start' => 9, 'end' => 15],
            ['start' => 16, 'end' => 23],
            ['start' => 24, 'end' => 30]
        ];
    } else {
        if ($daysInMonth == 29) {
            $weeks = [
                ['start' => 1, 'end' => 7],
                ['start' => 8, 'end' => 15],
                ['start' => 16, 'end' => 22],
                ['start' => 23, 'end' => 29]
            ];
        } else {
            $weeks = [
                ['start' => 1, 'end' => 7],
                ['start' => 8, 'end' => 14],
                ['start' => 15, 'end' => 21],
                ['start' => 22, 'end' => 28]
            ];
        }
    }
    
    $result = [];
    foreach ($weeks as $index => $week) {
        $startDate = date('Y-m-d', strtotime("$year-$month-{$week['start']}"));
        $endDate = date('Y-m-d', strtotime("$year-$month-{$week['end']}"));
        $result[] = [
            'week_number' => $index + 1,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $week['end'] - $week['start'] + 1
        ];
    }
    
    Response::success([
        'weeks' => $result,
        'year' => $year,
        'month' => $month,
        'days_in_month' => $daysInMonth
    ], 'Weeks fetched successfully');
} catch (Exception $e) {
    error_log('get_weeks_of_month.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}