<?php
// app/handlers/shared/schedules/save_day_override.php
// Calendar cell editor: sets a direct Time In/Out override for one date in
// one cutoff period. Always a work day (is_rest_day cleared) -- turning a
// day into a rest day is a separate flow.

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
$dayOfWeek = isset($input['day_of_week']) ? trim($input['day_of_week']) : '';
$timeIn = isset($input['time_in']) ? trim($input['time_in']) : '';
$timeOut = isset($input['time_out']) ? trim($input['time_out']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : null;

$validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

if ($userId <= 0 || !in_array($dayOfWeek, $validDays, true) || empty($timeIn) || empty($timeOut)) {
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
    $scheduleModel->saveDayOverride($userId, $periodKey, $dayOfWeek, $timeIn, $timeOut, $reason, Auth::userId());
    Response::success([
        'user_id' => $userId,
        'period_key' => $periodKey,
        'day_of_week' => $dayOfWeek
    ], 'Schedule updated');
} catch (Exception $e) {
    error_log('save_day_override.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
