<?php
// app/handlers/store_manager/save_tour_preference.php
// Toggles the Store Manager dashboard's onboarding tour on/off for the
// current user -- used both by the tour's own "Don't show this again"
// control and the toggle on the Profile page.

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

$input = json_decode(file_get_contents('php://input'), true);
$enabled = !empty($input['enabled']) ? 1 : 0;

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("UPDATE users SET show_dashboard_tour = ? WHERE user_id = ?");
$stmt->execute([$enabled, Auth::userId()]);

Response::success(['show_dashboard_tour' => (bool)$enabled], 'Preference saved');
