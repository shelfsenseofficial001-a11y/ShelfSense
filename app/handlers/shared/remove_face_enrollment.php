<?php
// app/handlers/shared/remove_face_enrollment.php

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
$stmt = $db->prepare("DELETE FROM face_enrollments WHERE user_id = ?");
$stmt->execute([Auth::userId()]);

Response::success([], 'Face ID enrollment removed');
