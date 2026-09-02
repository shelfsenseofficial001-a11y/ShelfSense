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
    Response::success(['cashiers' => $stmt->fetchAll()], 'Cashiers fetched');
} catch (Exception $e) {
    error_log('get_cashiers.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
