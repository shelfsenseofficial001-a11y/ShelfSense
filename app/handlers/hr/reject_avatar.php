<?php
// app/handlers/hr/reject_avatar.php
// Owner-only: reject a pending profile picture upload.

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

if (!Auth::isOwner()) {
    Response::forbidden('Only the owner can review profile picture uploads');
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
if ($userId <= 0) {
    Response::error('user_id is required', 400);
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT pending_profile_pic, pending_profile_pic_status FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    Response::notFound('User not found');
}
if ($user['pending_profile_pic_status'] !== 'pending' || !$user['pending_profile_pic']) {
    Response::error('This user has no pending profile picture', 400);
}

$rejectedPic = $user['pending_profile_pic'];

$stmt = $db->prepare("UPDATE users SET pending_profile_pic = NULL, pending_profile_pic_status = 'rejected', pending_profile_pic_reason = ? WHERE user_id = ?");
if (!$stmt->execute([$reason ?: null, $userId])) {
    Response::error('Database update failed', 500);
}

$path = __DIR__ . '/../../../public/' . $rejectedPic;
if (is_file($path)) {
    @unlink($path);
}

Response::success([], 'Profile picture rejected');
