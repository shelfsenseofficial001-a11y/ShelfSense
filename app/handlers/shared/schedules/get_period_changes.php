<?php
// app/handlers/shared/schedules/get_period_changes.php
// The "changes this period" list: every override made for a cutoff,
// across users -- not a per-employee schedule view. Store Manager only
// ever sees Front Department changes; HR sees everyone's.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';
require_once __DIR__ . '/../../../models/Schedule.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Schedule;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$periodKey = isset($_GET['period_key']) ? trim($_GET['period_key']) : CutoffPeriod::getCurrentKey();
if (!CutoffPeriod::describeKey($periodKey)) {
    Response::error('Invalid period key', 400);
}

$targetRole = Auth::getNormalizedTargetRole();
$isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);
$isHr = Auth::canAccessModule('hr_head') || $isHrTrainee;
$isStoreManager = Auth::isStoreManager();

if (!$isHr && !$isStoreManager) {
    Response::forbidden('Access denied.');
}

try {
    $scheduleModel = new Schedule();
    $changes = $scheduleModel->getChangesForPeriod($periodKey, !$isHr);

    Response::success([
        'changes' => $changes,
        'period_key' => $periodKey,
        'period' => CutoffPeriod::describeKey($periodKey)
    ], 'Changes fetched successfully');
} catch (Exception $e) {
    error_log('get_period_changes.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
