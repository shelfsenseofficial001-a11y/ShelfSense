<?php
// app/handlers/hr/get_month_employees.php

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

    $stmt = $db->prepare("
        SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.employee_number, u.role
        FROM attendance_weekly_summaries aws
        JOIN users u ON u.user_id = aws.user_id
        WHERE aws.month_year = ? AND u.role NOT IN ('trainee', 'supplier', 'owner')
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->execute([$monthYear]);
    $employees = $stmt->fetchAll();

    Response::success([
        'employees' => $employees
    ], 'Month employees fetched');

} catch (Exception $e) {
    error_log('get_month_employees.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
