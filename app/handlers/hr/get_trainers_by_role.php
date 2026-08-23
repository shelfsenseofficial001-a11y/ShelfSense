<?php
// app/handlers/hr/get_trainers_by_role.php

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

$role = isset($_GET['role']) ? trim($_GET['role']) : '';

if (empty($role)) {
    Response::error('Role is required', 400);
}

$roleMap = [
    'Employee' => 'employee',
    'HR Staff' => 'hr_staff',
    'Finance Staff' => 'finance_staff',
    'Head HR' => 'hr_head',
    'Head Finance' => 'finance_head'
];

$dbRole = $roleMap[$role] ?? $role;

error_log('🔍 Looking for trainers: display_role="' . $role . '" -> db_role="' . $dbRole . '"');

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT user_id, first_name, last_name, email, role, can_train 
        FROM users 
        WHERE role = ? 
            AND is_active = 1 
            AND can_train = 1
            AND role != 'trainee'
        ORDER BY first_name
    ");
    $stmt->execute([$dbRole]);
    $trainers = $stmt->fetchAll();
    
    error_log('✅ Found ' . count($trainers) . ' trainers for role: ' . $dbRole);
    
    if (empty($trainers)) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE role = ? AND is_active = 1 AND can_train = 0 AND role != 'trainee'
        ");
        $stmt->execute([$dbRole]);
        $locked = $stmt->fetch()['count'] ?? 0;
        
        $message = 'No available trainers for this role.';
        if ($locked > 0) {
            $message = $locked . ' trainer(s) are currently locked (training others). Please wait for them to complete training.';
        }
        
        Response::success([
            'trainers' => [],
            'role' => $role,
            'db_role' => $dbRole,
            'locked_count' => $locked,
            'message' => $message
        ], $message);
        exit;
    }
    
    Response::success([
        'trainers' => $trainers,
        'role' => $role,
        'db_role' => $dbRole,
        'available_count' => count($trainers)
    ], 'Trainers fetched successfully');

} catch (Exception $e) {
    error_log('get_trainers_by_role.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}