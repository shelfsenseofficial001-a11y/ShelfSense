<?php
// app/handlers/hr/save_dashboard_layout.php
// Persists the current user's drag-reordered widget layout for the HR
// dashboard. The submitted order must be exactly the known widget id set
// (no extra/missing ids) -- this is a fixed, hand-authored dashboard, not
// a place to store arbitrary client data.

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

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['widget_order'] ?? null;

// Each group is independently reorderable (stats among themselves, tables
// among themselves, charts among themselves) -- widgets never move
// between groups since they have different card layouts/sizes.
$knownGroups = [
    'stats' => ['stat_total', 'stat_pending', 'stat_scheduled', 'stat_hired'],
    'tables' => ['table_applicants', 'table_interviews', 'table_trainees'],
    'charts' => ['chart_monthly', 'chart_pipeline'],
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
    VALUES (?, 'hr_dashboard', ?)
    ON DUPLICATE KEY UPDATE widget_order = VALUES(widget_order)
");
$stmt->execute([Auth::userId(), json_encode($order)]);

Response::success([], 'Dashboard layout saved');
