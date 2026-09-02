<?php
// app/handlers/pos/pos_select_cashier.php
// Attributes subsequent orders on this POS session to a specific staff
// member -- purely for accountability, grants no staff-portal access.

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

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($userId <= 0) {
    Response::error('Please select a cashier.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_id = ? AND role = 'employee' AND is_active = 1");
    $stmt->execute([$userId]);
    $cashier = $stmt->fetch();

    if (!$cashier) {
        Response::error('That employee is not available.', 400);
    }

    $fullName = $cashier['first_name'] . ' ' . $cashier['last_name'];
    Auth::posSetCashier($cashier['user_id'], $fullName);

    Response::success([
        'cashier_name' => $fullName,
        'redirect' => '?page=pos_checkout'
    ], 'Cashier selected');

} catch (Exception $e) {
    error_log('pos_select_cashier.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
