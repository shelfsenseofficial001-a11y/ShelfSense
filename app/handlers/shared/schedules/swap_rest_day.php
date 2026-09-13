<?php
// app/handlers/shared/schedules/swap_rest_day.php
// The only way a cutoff schedule change is made: pick a day that becomes
// a rest day and an existing rest day (same period) that becomes the
// work day instead -- one paired action, one reason. The work day
// inherits the rest day's former hours automatically.

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

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$periodKey = isset($input['period_key']) ? trim($input['period_key']) : '';
$restDay = isset($input['rest_day']) ? trim($input['rest_day']) : '';
$workDay = isset($input['work_day']) ? trim($input['work_day']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : null;

$validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

if ($userId <= 0 || !in_array($restDay, $validDays, true) || !in_array($workDay, $validDays, true)) {
    Response::error('Missing required fields', 400);
}
if (!CutoffPeriod::describeKey($periodKey)) {
    Response::error('Invalid period key', 400);
}
if (!$reason) {
    Response::error('A reason is required for this change.', 400);
}
if (strlen($reason) > 255) {
    Response::error('Reason cannot exceed 255 characters.', 400);
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
    $scheduleModel->saveRestDaySwap($userId, $periodKey, $restDay, $workDay, $reason, Auth::userId());
    Response::success([
        'user_id' => $userId,
        'period_key' => $periodKey,
        'rest_day' => $restDay,
        'work_day' => $workDay
    ], 'Schedule swap saved');
} catch (Exception $e) {
    Response::error($e->getMessage(), 400);
}
