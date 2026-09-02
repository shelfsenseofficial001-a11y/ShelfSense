<?php
// app/handlers/store_manager/save_dashboard_layout.php
// Persists the current user's drag-reordered widget layout for the Store
// Manager dashboard. The submitted order must be exactly the known widget
// id set (no extra/missing ids) -- this is a fixed, hand-authored
// dashboard, not a place to store arbitrary client data.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['widget_order'] ?? null;

// Each group is independently reorderable -- widgets never move between
// groups. The stat cards stay their own group (different card layout/
// size); the mini-tables and charts share one uniform-height row
// ("content") so they can be freely mixed with each other.
$knownGroups = [
    'stats' => ['stat_total', 'stat_pending', 'stat_finance', 'stat_lowstock'],
    'content' => ['table_mine', 'table_lowstock', 'table_finance', 'table_history', 'chart_trend', 'chart_status', 'list_recent', 'panel_insights'],
];

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
    VALUES (?, 'store_manager_dashboard', ?)
    ON DUPLICATE KEY UPDATE widget_order = VALUES(widget_order)
");
$stmt->execute([Auth::userId(), json_encode($order)]);

Response::success([], 'Dashboard layout saved');
