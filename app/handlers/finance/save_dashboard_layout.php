<?php
// app/handlers/finance/save_dashboard_layout.php
// Persists the current user's drag-reordered widget layout for their
// Finance dashboard. The submitted order must be exactly the known widget
// id set for that dashboard (no extra/missing ids) -- this is a fixed,
// hand-authored dashboard, not a place to store arbitrary client data.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/dashboard_layout_widgets.php';

use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$dashboardKey = fn_dashboard_key_for_current_user();
if ($dashboardKey === null) {
    Response::forbidden('Access denied. Finance role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['widget_order'] ?? null;

$knownGroups = fn_dashboard_widget_groups($dashboardKey);

$valid = is_array($order) && array_keys($order) === array_keys($knownGroups);
if ($valid) {
    foreach ($knownGroups as $group => $ids) {
        $submitted = $order[$group] ?? null;
        if (
            !is_array($submitted)
            || array_diff($submitted, $ids) !== []
            || array_diff($ids, $submitted) !== []
        ) {
            $valid = false;
            break;
        }
    }
}

if (!$valid) {
    Response::validationError(['widget_order' => 'Must contain a full permutation of each group\'s widget ids.']);
}

$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->prepare("
    INSERT INTO user_dashboard_layouts (user_id, dashboard_key, widget_order)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE widget_order = VALUES(widget_order)
");
$stmt->execute([Auth::userId(), $dashboardKey, json_encode($order)]);

Response::success([], 'Dashboard layout saved');
