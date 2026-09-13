<?php
// app/handlers/shared/schedules/get_user_period_changes.php
// Change history for one employee's calendar in one cutoff period -- the
// list shown under the day editor, distinct from get_period_changes.php
// which lists changes across every employee.

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

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$periodKey = isset($_GET['period_key']) ? trim($_GET['period_key']) : '';

if ($userId <= 0) {
    Response::error('Missing required fields', 400);
}
if (!CutoffPeriod::describeKey($periodKey)) {
    Response::error('Invalid period key', 400);
}

$scheduleModel = new Schedule();

$targetRole = Auth::getNormalizedTargetRole();
$isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);
$isHr = Auth::canAccessModule('hr_head') || $isHrTrainee;
$isStoreManagerAllowed = Auth::isStoreManager() && $scheduleModel->isFrontDepartmentUser($userId);

if (!$isHr && !$isStoreManagerAllowed) {
    Response::forbidden('Access denied.');
}

try {
    $changes = $scheduleModel->getChangesForUserPeriod($userId, $periodKey);
    Response::success(['changes' => $changes], 'Changes fetched successfully');
} catch (Exception $e) {
    error_log('get_user_period_changes.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
