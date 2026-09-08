<?php
// app/handlers/pos/get_cashiers.php
// Lists active cashiers (role=employee) for the POS "who's on shift" picker.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::posCheck()) {
    Response::unauthorized('Please log in to a register first.');
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT user_id, first_name, last_name, employee_number, profile_pic
        FROM users
        WHERE role = 'employee' AND is_active = 1
        ORDER BY first_name ASC
    ");
    $cashiers = $stmt->fetchAll();

    // Trainees training for the Cashier role can also ring up sales under
    // supervision -- shown as a separate group below the hired cashiers.
    $stmt = $db->query("
        SELECT u.user_id, u.first_name, u.last_name, u.employee_number, u.profile_pic
        FROM users u
        JOIN trainees t ON t.user_id = u.user_id
        WHERE u.role = 'trainee' AND u.is_active = 1
          AND t.status = 'active' AND t.target_role = 'Cashier'
        ORDER BY u.first_name ASC
    ");
    $trainees = $stmt->fetchAll();

    Response::success(['cashiers' => $cashiers, 'trainees' => $trainees], 'Cashiers fetched');
} catch (Exception $e) {
    error_log('get_cashiers.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
