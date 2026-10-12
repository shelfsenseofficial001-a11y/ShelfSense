<?php
// app/handlers/hr/trainee_reports/save_daily_log.php
// Upserts ONE day's entry within the currently-open week. Freely editable
// (re-save the same day any number of times) right up until the week itself
// is finalized -- see submit_report.php, which is what actually locks a
// week's daily_logs rows (by creating the trainee_reports row they get
// concatenated into).

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$traineeId = isset($input['trainee_id']) ? intval($input['trainee_id']) : 0;
$weekNumber = isset($input['week_number']) ? intval($input['week_number']) : 0;
$logDate = isset($input['log_date']) ? trim($input['log_date']) : '';
$content = isset($input['content']) ? trim($input['content']) : '';

if ($traineeId <= 0 || $weekNumber <= 0) {
    Response::error('Invalid trainee or week number', 400);
}
if ($weekNumber > 12) {
    Response::error('Only 12 weekly reports are expected over the 3-month training period.', 400);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $logDate)) {
    Response::error('Invalid date.', 400);
}
if ($content === '' || mb_strlen($content) > 2000) {
    Response::error('Report content is required (max 2000 characters).', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM trainees WHERE id = ?");
    $stmt->execute([$traineeId]);
    $trainee = $stmt->fetch();
    if (!$trainee) {
        Response::notFound('Trainee not found');
    }
    if ($trainee['status'] !== 'active') {
        Response::error('This trainee is not currently active.', 400);
    }
    if ((int)$trainee['trainer_id'] !== (int)Auth::userId() && !Auth::isSuperAdmin()) {
        Response::forbidden('Only the assigned trainer may log this trainee\'s day.');
    }

    // Week-gate: can't log into a week that isn't open yet -- every earlier
    // week must already have its own trainee_reports row (or this is week 1).
    if ($weekNumber > 1) {
        $stmt = $db->prepare("SELECT COUNT(*) c FROM trainee_reports WHERE trainee_id = ? AND week_number < ?");
        $stmt->execute([$traineeId, $weekNumber]);
        $priorSubmitted = (int)$stmt->fetch()['c'];
        if ($priorSubmitted < $weekNumber - 1) {
            Response::error("Week {$weekNumber} isn't open yet -- submit the earlier week(s) first.", 400);
        }
    }

    // Can't edit into a week that's already finalized.
    $stmt = $db->prepare("SELECT id FROM trainee_reports WHERE trainee_id = ? AND week_number = ?");
    $stmt->execute([$traineeId, $weekNumber]);
    if ($stmt->fetch()) {
        Response::error("Week {$weekNumber} has already been submitted and can no longer be edited.", 400);
    }

    // The date must actually fall inside this week's range for this trainee.
    $start = new DateTime($trainee['start_date']);
    $end = new DateTime($trainee['end_date']);
    $periodStart = (clone $start)->modify('+' . (($weekNumber - 1) * 7) . ' days');
    $periodEnd = (clone $periodStart)->modify('+6 days');
    $logDateObj = new DateTime($logDate);
    if ($logDateObj < $periodStart || $logDateObj > $periodEnd || $logDateObj > $end) {
        Response::error("That date doesn't fall within week {$weekNumber} of this trainee's training period.", 400);
    }

    $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $dayName = $dayNames[(int)$logDateObj->format('N') - 1];

    $stmt = $db->prepare("SELECT 1 FROM schedules WHERE user_id = ? AND day_of_week = ? AND is_rest_day = 1");
    $stmt->execute([$trainee['user_id'], $dayName]);
    if ($stmt->fetch()) {
        Response::error(ucfirst($dayName) . ' is a rest day for this trainee and cannot be logged.', 400);
    }

    $stmt = $db->prepare("
        INSERT INTO trainee_daily_logs (trainee_id, week_number, log_date, day_name, content, trainer_id)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE content = VALUES(content), week_number = VALUES(week_number), day_name = VALUES(day_name)
    ");
    $stmt->execute([$traineeId, $weekNumber, $logDate, $dayName, $content, Auth::userId()]);

    Response::success(['log_date' => $logDate, 'day_name' => $dayName], 'Day logged.');

} catch (Exception $e) {
    error_log('save_daily_log.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
