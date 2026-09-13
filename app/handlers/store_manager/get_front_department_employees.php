<?php
// app/handlers/store_manager/get_front_department_employees.php
// Employee list for Store Manager's Schedules page -- hired cashiers plus
// Employee-track trainees only, unlike HR's schedules page which manages
// everyone.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Schedule.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Schedule;

header('Content-Type: application/json');

if (!Auth::check() || !Auth::isStoreManager()) {
    Response::unauthorized('Please login as a Store Manager to access this resource');
}

try {
    $scheduleModel = new Schedule();
    $employees = $scheduleModel->getFrontDepartmentEmployees();

    Response::success(['employees' => $employees], 'Employees fetched successfully');
} catch (Exception $e) {
    error_log('get_front_department_employees.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
