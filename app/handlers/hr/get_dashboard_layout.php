<?php
// app/handlers/hr/get_dashboard_layout.php
// Returns the current user's saved widget order for the HR dashboard, or
// null if they have never customized it (caller falls back to the
// default markup order).

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$targetRole = Auth::getNormalizedTargetRole();
$isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);

if (!Auth::canAccessModule('hr_head') && !$isHrTrainee) {
    Response::forbidden('Access denied. HR role required.');
}

$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT widget_order FROM user_dashboard_layouts WHERE user_id = ? AND dashboard_key = ?");
$stmt->execute([Auth::userId(), 'hr_dashboard']);
$row = $stmt->fetch();

$order = null;
if ($row) {
    $decoded = json_decode($row['widget_order'], true);
    if (is_array($decoded)) {
        $order = $decoded;
    }
}

Response::success(['widget_order' => $order], 'Dashboard layout fetched');
