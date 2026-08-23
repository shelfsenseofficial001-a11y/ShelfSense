<?php
// app/handlers/hr/get_employees_with_schedules.php

require_once __DIR__ . '/../../models/Schedule.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Schedule;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isHR()) {
    Response::unauthorized('Please login to access this resource');
}

try {
    $scheduleModel = new Schedule();
    $employees = $scheduleModel->getEmployeesWithSchedules();
    
    Response::success([
        'employees' => $employees
    ], 'Employees fetched successfully');
} catch (Exception $e) {
    error_log('get_employees_with_schedules.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}