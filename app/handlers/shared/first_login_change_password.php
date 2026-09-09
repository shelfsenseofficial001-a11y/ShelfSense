<?php
// app/handlers/shared/first_login_change_password.php
// Step 1 of the forced first-login flow (new account, or promoted to a
// new role -- both stamp is_first_login=1). Unlike the voluntary Profile
// change-password flow, this doesn't ask for the current password: the
// person already proved it by logging in with it moments ago in this
// same authenticated session.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if ($newPassword === '' || $confirmPassword === '') {
    Response::error('Please fill in both fields.', 400);
}

if ($newPassword !== $confirmPassword) {
    Response::error('New password and confirmation do not match.', 400);
}

if (strlen($newPassword) < 8) {
    Response::error('New password must be at least 8 characters.', 400);
}

$userId = Auth::userId();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT password, role FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    Response::error('Account not found.', 404);
}

if (password_verify($newPassword, $user['password'])) {
    Response::error('Your new password must be different from the current one.', 400);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$db->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hash, $userId]);

// Only cashier-facing roles need Face ID, and only if they don't already
// have one enrolled (e.g. a promotion or an admin password reset
// shouldn't force someone to re-enroll a face they already registered).
$requiresFace = false;
if (in_array($user['role'], ['employee', 'trainee'], true)) {
    $stmt = $db->prepare("SELECT 1 FROM face_enrollments WHERE user_id = ?");
    $stmt->execute([$userId]);
    $requiresFace = !$stmt->fetch();
}

if (!$requiresFace) {
    $db->prepare("UPDATE users SET is_first_login = 0 WHERE user_id = ?")->execute([$userId]);
    $_SESSION['is_first_login'] = 0;
}

Response::success(['requires_face' => $requiresFace], 'Password changed successfully');
