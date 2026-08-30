<?php
// app/handlers/hr/approve_avatar.php
// Owner-only: approve a pending profile picture upload, making it live.

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
if ($userId <= 0) {
    Response::error('user_id is required', 400);
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT profile_pic, pending_profile_pic, pending_profile_pic_status FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    Response::notFound('User not found');
}
if ($user['pending_profile_pic_status'] !== 'pending' || !$user['pending_profile_pic']) {
    Response::error('This user has no pending profile picture', 400);
}

$newPic = $user['pending_profile_pic'];
$oldPic = $user['profile_pic'];

$stmt = $db->prepare("UPDATE users SET profile_pic = ?, pending_profile_pic = NULL, pending_profile_pic_status = 'none', pending_profile_pic_reason = NULL WHERE user_id = ?");
if (!$stmt->execute([$newPic, $userId])) {
    Response::error('Database update failed', 500);
}

if ($oldPic && $oldPic !== $newPic) {
    $oldPath = __DIR__ . '/../../../public/' . $oldPic;
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

if ((int)Auth::userId() === $userId) {
    $_SESSION['profile_pic'] = $newPic;
}

Response::success(['profile_pic' => $newPic], 'Profile picture approved');
