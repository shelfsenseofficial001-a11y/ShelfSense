<?php
// app/handlers/pos/get_attendance_qr_status.php
// Polled by the POS terminal while the attendance QR modal is open. Once
// the phone (or the fallback local capture) confirms the face match, this
// is what actually attributes the register session to that cashier --
// the confirming device is often a different browser session entirely.

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

$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
if ($token === '') {
    Response::error('Missing token.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT s.*, u.first_name, u.last_name
        FROM attendance_qr_sessions s
        JOIN users u ON u.user_id = s.user_id
        WHERE s.token = ? AND s.register_id = ?
    ");
    $stmt->execute([$token, Auth::posRegisterId()]);
    $session = $stmt->fetch();

    if (!$session) {
        Response::error('Attendance session not found.', 404);
    }

    if ($session['status'] === 'pending' && strtotime($session['expires_at']) < time()) {
        $db->prepare("UPDATE attendance_qr_sessions SET status = 'expired' WHERE id = ?")->execute([$session['id']]);
        $session['status'] = 'expired';
    }

    if ($session['status'] === 'confirmed') {
        $fullName = $session['first_name'] . ' ' . $session['last_name'];
        Auth::posSetCashier((int)$session['user_id'], $fullName);
        Response::success([
            'status' => 'confirmed',
            'attendance_action' => $session['attendance_action'],
            'redirect' => '?page=pos_checkout'
        ]);
    }

    Response::success([
        'status' => $session['status'],
        'fail_reason' => $session['fail_reason']
    ]);

} catch (Exception $e) {
    error_log('get_attendance_qr_status.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
