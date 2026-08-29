<?php
// app/handlers/shared/remove_avatar.php

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

$stmt = $db->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$old = $stmt->fetchColumn();

$stmt = $db->prepare("UPDATE users SET profile_pic = NULL WHERE user_id = ?");
if (!$stmt->execute([$userId])) {
    Response::error('Database update failed', 500);
}

if ($old) {
    $oldPath = __DIR__ . '/../../../public/' . $old;
    if (is_file($oldPath)) {
        @unlink($oldPath);
    }
}

$_SESSION['profile_pic'] = null;

Response::success([], 'Profile picture removed');
