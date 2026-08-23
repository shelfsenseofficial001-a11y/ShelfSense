<?php
// app/handlers/hr/get_payroll_halves.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login to access this resource');
}

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

if ($month < 1 || $month > 12 || $year < 2000) {
    Response::error('Invalid month or year', 400);
}

try {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    // Define payroll halves
    if ($daysInMonth == 31) {
        $half1 = ['start' => 1, 'end' => 16, 'label' => '1st Half (1-16)'];
        $half2 = ['start' => 17, 'end' => 31, 'label' => '2nd Half (17-31)'];
    } elseif ($daysInMonth == 30) {
        $half1 = ['start' => 1, 'end' => 15, 'label' => '1st Half (1-15)'];
        $half2 = ['start' => 16, 'end' => 30, 'label' => '2nd Half (16-30)'];
    } else { // February
        if ($daysInMonth == 29) { // Leap year
            $half1 = ['start' => 1, 'end' => 15, 'label' => '1st Half (1-15)'];
            $half2 = ['start' => 16, 'end' => 29, 'label' => '2nd Half (16-29)'];
        } else {
            $half1 = ['start' => 1, 'end' => 15, 'label' => '1st Half (1-15)'];
            $half2 = ['start' => 16, 'end' => 28, 'label' => '2nd Half (16-28)'];
        }
    }
    
    $result = [
        [
            'half' => 1,
            'start_date' => date('Y-m-d', strtotime("$year-$month-{$half1['start']}")),
            'end_date' => date('Y-m-d', strtotime("$year-$month-{$half1['end']}")),
            'label' => $half1['label'],
            'pay_date' => date('Y-m-d', strtotime("$year-$month-{$half1['end']}"))
        ],
        [
            'half' => 2,
            'start_date' => date('Y-m-d', strtotime("$year-$month-{$half2['start']}")),
            'end_date' => date('Y-m-d', strtotime("$year-$month-{$half2['end']}")),
            'label' => $half2['label'],
            'pay_date' => date('Y-m-d', strtotime("$year-$month-{$half2['end']}"))
        ]
    ];
    
    Response::success([
        'halves' => $result,
        'year' => $year,
        'month' => $month,
        'days_in_month' => $daysInMonth
    ], 'Payroll halves fetched successfully');
} catch (Exception $e) {
    error_log('get_payroll_halves.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}