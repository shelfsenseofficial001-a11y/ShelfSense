<?php
// app/handlers/shared/cancel_pending_avatar.php
// Lets a user withdraw their own not-yet-reviewed profile picture upload.

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

$userId = Auth::userId();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT pending_profile_pic FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$pending = $stmt->fetchColumn();

$stmt = $db->prepare("UPDATE users SET pending_profile_pic = NULL, pending_profile_pic_status = 'none', pending_profile_pic_reason = NULL WHERE user_id = ?");
if (!$stmt->execute([$userId])) {
    Response::error('Database update failed', 500);
}

if ($pending) {
    $path = __DIR__ . '/../../../public/' . $pending;
    if (is_file($path)) {
        @unlink($path);
    }
}

Response::success([], 'Pending profile picture cancelled');
