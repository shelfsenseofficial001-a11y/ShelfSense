<?php
// app/handlers/shared/schedules/revert_swap.php
// Reverts both sides of a rest-day swap back to the standing baseline --
// pass either day of the pair.

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

$validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

if ($userId <= 0 || !in_array($dayOfWeek, $validDays, true)) {
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
    $scheduleModel->revertSwap($userId, $periodKey, $dayOfWeek);
    Response::success(['user_id' => $userId, 'period_key' => $periodKey], 'Reverted to standing schedule');
} catch (Exception $e) {
    error_log('revert_swap.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
