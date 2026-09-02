<?php
// app/handlers/store_manager/get_tour_preference.php
// Returns whether the current user has the Store Manager dashboard's
// onboarding tour enabled (defaults to on for anyone who hasn't touched it).

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
$stmt = $db->prepare("SELECT show_dashboard_tour FROM users WHERE user_id = ?");
$stmt->execute([Auth::userId()]);
$row = $stmt->fetch();

Response::success(['show_dashboard_tour' => $row ? (bool)$row['show_dashboard_tour'] : true]);
