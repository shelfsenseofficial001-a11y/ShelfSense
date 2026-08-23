<?php
// app/handlers/hr/get_week_summary.php

require_once __DIR__ . '/../../models/AttendanceWeeklySummary.php';
require_once __DIR__ . '/../../models/Attendance.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\AttendanceWeeklySummary;
use App\Models\Attendance;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login to access this resource');
}

$weekStart = isset($_GET['week_start']) ? trim($_GET['week_start']) : date('Y-m-d', strtotime('monday this week'));
$weekEnd = isset($_GET['week_end']) ? trim($_GET['week_end']) : date('Y-m-d', strtotime('sunday this week'));
$department = isset($_GET['department']) ? trim($_GET['department']) : 'all';

try {
    $attendanceModel = new Attendance();
    $employees = $attendanceModel->getWeekAttendance($weekStart, $weekEnd, $department);
    $summaryModel = new AttendanceWeeklySummary();
    
    $results = [];
    foreach ($employees as $userId => $data) {
        $summary = $attendanceModel->getWeekSummary($userId, $weekStart, $weekEnd);
        $savedSummary = $summaryModel->getSummary($userId, $weekStart);
        
        // Auto-calculate if not saved
        if (!$savedSummary && $summary) {
            $summaryModel->saveSummary(
                $userId,
                $weekStart,
                $weekEnd,
                1,
                date('Y-m', strtotime($weekStart)),
                $summary
            );
            $savedSummary = $summaryModel->getSummary($userId, $weekStart);
        }
        
        $results[] = [
            'user_id' => $userId,
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'employee_number' => $data['employee_number'],
            'role' => $data['role'],
            'summary' => $summary,
            'status' => $savedSummary ? $savedSummary['status'] : 'draft'
        ];
    }
    
    Response::success([
        'summaries' => $results,
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'total' => count($results)
    ], 'Week summaries fetched successfully');
} catch (Exception $e) {
    error_log('get_week_summary.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}