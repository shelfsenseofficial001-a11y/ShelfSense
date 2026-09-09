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
$password = isset($input['password']) ? (string)$input['password'] : '';

if ($userId <= 0) {
    Response::error('Please select a cashier.', 400);
}

if ($password === '') {
    Response::error('Please enter your password.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.password
        FROM users u
        LEFT JOIN trainees t ON t.user_id = u.user_id AND t.status = 'active' AND t.target_role = 'Cashier'
        WHERE u.user_id = ? AND u.is_active = 1
          AND (u.role = 'employee' OR (u.role = 'trainee' AND t.id IS NOT NULL))
    ");
    $stmt->execute([$userId]);
    $cashier = $stmt->fetch();

    if (!$cashier) {
        Response::error('That employee is not available.', 400);
    }

    if (!password_verify($password, $cashier['password'])) {
        Response::error('Incorrect password.', 401);
    }

    $fullName = $cashier['first_name'] . ' ' . $cashier['last_name'];

    // Password only confirms identity for register attribution -- actual
    // clock-in still requires a face-verified attendance scan, done right
    // here on the register's own camera. Stamping who just passed the
    // password check into this POS session (rather than trusting a
    // user_id the client sends later) is what stops someone from calling
    // the face-verify endpoint directly with an arbitrary user_id.
    $_SESSION['pos_pending_cashier_id'] = (int)$cashier['user_id'];
    $_SESSION['pos_pending_cashier_name'] = $fullName;
    $_SESSION['pos_pending_cashier_expires'] = time() + 180;

    Response::success([
        'cashier_name' => $fullName
    ], 'Identity confirmed, face verification required');

} catch (Exception $e) {
    error_log('pos_select_cashier.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
