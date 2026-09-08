<?php
// app/handlers/shared/get_face_enrollment_status.php

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
$stmt = $db->prepare("SELECT enrolled_at FROM face_enrollments WHERE user_id = ?");
$stmt->execute([Auth::userId()]);
$row = $stmt->fetch();

Response::success([
    'enrolled' => (bool)$row,
    'enrolled_at' => $row['enrolled_at'] ?? null
]);
