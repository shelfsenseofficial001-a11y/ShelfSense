<?php
// app/handlers/shared/get_profile.php

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

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT user_id, employee_number, first_name, last_name, email, role, profile_pic, pending_profile_pic, pending_profile_pic_status, pending_profile_pic_reason, hired_date FROM users WHERE user_id = ?");
$stmt->execute([Auth::userId()]);
$user = $stmt->fetch();

if (!$user) {
    Response::notFound('User not found');
}

Response::success([
    'user_id' => (int)$user['user_id'],
    'employee_number' => $user['employee_number'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'role_label' => getRoleName($user['role']),
    'profile_pic' => $user['profile_pic'],
    'pending_profile_pic' => $user['pending_profile_pic'],
    'pending_profile_pic_status' => $user['pending_profile_pic_status'],
    'pending_profile_pic_reason' => $user['pending_profile_pic_reason'],
    'hired_date' => $user['hired_date'],
]);
