<?php
// app/handlers/shared/schedules/get_effective_schedule.php
// The baseline schedule with this cutoff period's overrides merged in --
// what HR/Store Manager's grid actually shows, with overridden days
// flagged so the UI can highlight them.

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
$periodKey = isset($_GET['period_key']) ? trim($_GET['period_key']) : CutoffPeriod::getCurrentKey();

if ($userId <= 0) {
    Response::error('Invalid user ID', 400);
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
    $schedule = $scheduleModel->getEffectiveSchedule($userId, $periodKey);

    Response::success([
        'schedule' => $schedule,
        'user_id' => $userId,
        'period_key' => $periodKey,
        'period' => CutoffPeriod::describeKey($periodKey)
    ], 'Schedule fetched successfully');
} catch (Exception $e) {
    error_log('get_effective_schedule.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
