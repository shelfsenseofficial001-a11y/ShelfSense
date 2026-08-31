<?php
// app/handlers/hr/get_employee_contract_info.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login to access this resource');
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($userId <= 0) {
    Response::error('Invalid user ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get the employee's latest accepted contract
    $stmt = $db->prepare("
        SELECT c.*, a.target_role 
        FROM contracts c 
        JOIN applicants a ON c.applicant_id = a.id 
        WHERE c.user_id = ? AND c.status = 'accepted' 
        ORDER BY c.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $contract = $stmt->fetch();
    
    if (!$contract) {
        Response::success([
            'has_contract' => false,
            'message' => 'No active contract found for this employee.'
        ], 'No contract found');
        exit;
    }
    
    // Get the employee's schedule (if any)
    $stmt = $db->prepare("
        SELECT day_of_week, time_in, time_out, is_rest_day 
        FROM schedules 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $schedules = $stmt->fetchAll();
    
    // Calculate total weekly hours from schedule
    $totalHours = 0;
    foreach ($schedules as $sched) {
        if (!$sched['is_rest_day'] && $sched['time_in'] && $sched['time_out']) {
            $timeIn = new DateTime($sched['time_in']);
            $timeOut = new DateTime($sched['time_out']);
            $diff = $timeIn->diff($timeOut);
            $hours = $diff->h + ($diff->i / 60);
            $totalHours += $hours;
        }
    }
    
    Response::success([
        'has_contract' => true,
        'contract' => [
            'shift' => $contract['shift'],
            'salary' => $contract['salary'],
            'salary_range_min' => $contract['salary_range_min'],
            'salary_range_max' => $contract['salary_range_max'],
            'target_role' => $contract['target_role'],
            'start_date' => $contract['start_date'],
            'job_details' => $contract['job_details']
        ],
        'schedule_summary' => [
            'total_weekly_hours' => round($totalHours, 2),
            'schedules' => $schedules
        ],
        'warning' => 'Please ensure the schedule aligns with the employee\'s contract working hours.'
    ], 'Contract info fetched successfully');
    
} catch (Exception $e) {
    error_log('get_employee_contract_info.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}