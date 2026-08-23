<?php
// app/handlers/hr/get_all_employees.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$department = isset($_GET['department']) ? trim($_GET['department']) : 'all';

try {
    $db = Database::getInstance()->getConnection();

    $sql = "
        SELECT user_id, first_name, last_name, employee_number, role 
        FROM users 
        WHERE is_active = 1 AND role != 'trainee'
    ";
    if ($department !== 'all') {
        $sql .= " AND role = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$department]);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }

    $employees = $stmt->fetchAll();

    Response::success([
        'employees' => $employees,
        'department' => $department
    ], 'Employees fetched successfully');
} catch (Exception $e) {
    error_log('get_all_employees.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}