<?php
// app/handlers/pos/pos_attendance_fallback_login.php
// "No device to scan the QR with" fallback: re-authenticate the same
// employee with email + password directly on the register, then let the
// register's own camera perform the face capture (still required -- this
// only replaces the QR handoff, not the face verification itself).

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
$token = isset($input['token']) ? (string)$input['token'] : '';
$email = isset($input['email']) ? trim((string)$input['email']) : '';
$password = isset($input['password']) ? (string)$input['password'] : '';

if ($token === '' || $email === '' || $password === '') {
    Response::error('Please enter your email and password.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM attendance_qr_sessions WHERE token = ? AND register_id = ? AND status = 'pending'");
    $stmt->execute([$token, Auth::posRegisterId()]);
    $session = $stmt->fetch();

    if (!$session || strtotime($session['expires_at']) < time()) {
        Response::error('This attendance session has expired. Please select the cashier again.', 400);
    }

    $stmt = $db->prepare("SELECT user_id, password FROM users WHERE user_id = ? AND LOWER(email) = LOWER(?) AND is_active = 1");
    $stmt->execute([$session['user_id'], $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        Response::error('Email or password does not match the selected employee.', 401);
    }

    Response::success(['open_camera' => true], 'Identity confirmed, starting face scan');

} catch (Exception $e) {
    error_log('pos_attendance_fallback_login.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
