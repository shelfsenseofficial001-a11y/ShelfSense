<?php
// app/handlers/shared/update_avatar.php

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

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['avatar']['error'] ?? 'unknown';
    Response::error('File upload error. Code: ' . $err, 400);
}

$file = $_FILES['avatar'];

if ($file['size'] > 3 * 1024 * 1024) {
    Response::error('Image must be under 3MB', 400);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowed)) {
    Response::error('Invalid file type. Use JPG, PNG, or WEBP.', 400);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
    Response::error('Invalid image file', 400);
}

$userId = Auth::userId();
$baseDir = __DIR__ . '/../../../public/uploads/avatars/';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$filename = 'user_' . $userId . '_' . time() . '.' . $ext;
$dest = $baseDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    Response::error('Failed to save image. Check permissions.', 500);
}

$relativePath = 'uploads/avatars/' . $filename;

$db = Database::getInstance()->getConnection();

// Remove the previous avatar file, if any
$stmt = $db->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$old = $stmt->fetchColumn();

$stmt = $db->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
if (!$stmt->execute([$relativePath, $userId])) {
    unlink($dest);
    Response::error('Database update failed', 500);
}

if ($old && $old !== $relativePath) {
    $oldPath = __DIR__ . '/../../../public/' . $old;
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

$_SESSION['profile_pic'] = $relativePath;

Response::success(['profile_pic' => $relativePath], 'Profile picture updated');
