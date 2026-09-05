<?php
// app/handlers/supplier/save_dashboard_layout.php
// Persists the current user's drag-reordered widget layout for the
// Supplier dashboard. The submitted order must be exactly the known widget
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

if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['widget_order'] ?? null;

// Each group is independently reorderable -- widgets never move between
// groups. The stat cards stay their own group (different card layout/
// size); the content cards (quick actions, activity, recent POs) share
// one full-width stacked group.
$knownGroups = [
    'stats' => ['stat_pending', 'stat_invoiced', 'stat_ready', 'stat_revenue'],
    'content' => ['table_ready', 'table_invoices', 'table_products', 'table_pos', 'table_recent', 'table_activity'],
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
    VALUES (?, 'supplier_dashboard', ?)
    ON DUPLICATE KEY UPDATE widget_order = VALUES(widget_order)
");
$stmt->execute([Auth::userId(), json_encode($order)]);

Response::success([], 'Dashboard layout saved');
