<?php
// app/handlers/hr/trainee_reports/submit_report.php
// The assigned Trainer finalizes one weekly report -- gated on every
// non-rest day of the week already having its own trainee_daily_logs entry
// (see save_daily_log.php, freely editable up until this point). This is
// the moment those daily entries get frozen: report_content is built here
// by concatenating them in date order, and once inserted, a report's
// content fields are never updated by any handler in this codebase -- there
// is deliberately no "edit report" endpoint, which is what makes it immutable.

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
$rating = isset($input['performance_rating']) ? trim($input['performance_rating']) : '';
$strengths = isset($input['strengths']) ? trim($input['strengths']) : '';
$improvements = isset($input['improvements']) ? trim($input['improvements']) : '';
$attendanceNotes = isset($input['attendance_notes']) ? trim($input['attendance_notes']) : '';
$recommendation = isset($input['recommendation']) ? trim($input['recommendation']) : '';

if ($traineeId <= 0 || $weekNumber <= 0) {
    Response::error('Invalid trainee or week number', 400);
}
// Training is fixed at exactly 3 months / 12 weekly reports (4 per month) --
// never fewer, never more.
if ($weekNumber > 12) {
    Response::error('Only 12 weekly reports are expected over the 3-month training period.', 400);
}
if (mb_strlen($strengths) > 2000 || mb_strlen($improvements) > 2000 || mb_strlen($attendanceNotes) > 1000) {
    Response::error('One or more fields exceed the maximum length.', 400);
}
$validRatings = ['', 'excellent', 'good', 'satisfactory', 'needs_improvement', 'poor'];
if (!in_array($rating, $validRatings, true)) {
    Response::error('Invalid performance rating.', 400);
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

    // Only the currently-assigned trainer may submit -- department is derived
    // from the trainee's real target_role, never hard-coded.
    $department = mapRoleToDepartment($trainee['target_role']);
    if ((int)$trainee['trainer_id'] !== (int)Auth::userId() && !Auth::isSuperAdmin()) {
        Response::forbidden('Only the assigned trainer may submit this trainee\'s report.');
    }

    // Report period must fall within the trainee's real training window.
    $start = new DateTime($trainee['start_date']);
    $end = new DateTime($trainee['end_date']);
    $periodStart = (clone $start)->modify('+' . (($weekNumber - 1) * 7) . ' days');
    $periodEnd = (clone $periodStart)->modify('+6 days');
    if ($periodStart > $end) {
        Response::error('Week ' . $weekNumber . ' falls outside this trainee\'s training period.', 400);
    }
    $monthNumber = (int)ceil($weekNumber / 4);

    $stmt = $db->prepare("SELECT id FROM trainee_reports WHERE trainee_id = ? AND week_number = ?");
    $stmt->execute([$traineeId, $weekNumber]);
    if ($stmt->fetch()) {
        Response::error('A report for week ' . $weekNumber . ' has already been submitted and cannot be replaced.', 400);
    }

    if ($weekNumber > 1) {
        $stmt = $db->prepare("SELECT COUNT(*) c FROM trainee_reports WHERE trainee_id = ? AND week_number < ?");
        $stmt->execute([$traineeId, $weekNumber]);
        if ((int)$stmt->fetch()['c'] < $weekNumber - 1) {
            Response::error("Week {$weekNumber} isn't open yet -- submit the earlier week(s) first.", 400);
        }
    }

    // Every non-rest day in this week's range needs its own daily log
    // before the week can be finalized -- that's what "submitting the week"
    // now means (see save_daily_log.php).
    $stmt = $db->prepare("SELECT day_of_week FROM schedules WHERE user_id = ? AND is_rest_day = 1");
    $stmt->execute([$trainee['user_id']]);
    $restSet = array_flip(array_column($stmt->fetchAll(), 'day_of_week'));
    $dayNameList = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    $stmt = $db->prepare("SELECT log_date, day_name, content FROM trainee_daily_logs WHERE trainee_id = ? AND week_number = ? ORDER BY log_date ASC");
    $stmt->execute([$traineeId, $weekNumber]);
    $dailyLogs = $stmt->fetchAll();
    $loggedDates = array_column($dailyLogs, 'log_date');

    $missingDays = [];
    $cursor = clone $periodStart;
    while ($cursor <= $periodEnd) {
        $dayName = $dayNameList[(int)$cursor->format('N') - 1];
        if (!isset($restSet[$dayName]) && !in_array($cursor->format('Y-m-d'), $loggedDates, true)) {
            $missingDays[] = ucfirst($dayName);
        }
        $cursor->modify('+1 day');
    }
    if (!empty($missingDays)) {
        Response::error('Please log every working day before submitting this week. Missing: ' . implode(', ', $missingDays) . '.', 400);
    }
    if (empty($dailyLogs)) {
        Response::error('No daily entries found for this week yet.', 400);
    }

    $content = implode("\n", array_map(fn($d) => ucfirst($d['day_name']) . ': ' . $d['content'], $dailyLogs));

    $stmt = $db->prepare("
        INSERT INTO trainee_reports
            (trainee_id, week_number, month_number, period_start, period_end, department, trainer_id,
             report_content, performance_rating, strengths, improvements, attendance_notes, recommendation, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
    ");
    $stmt->execute([
        $traineeId, $weekNumber, $monthNumber, $periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d'), $department,
        Auth::userId(), $content, $rating !== '' ? $rating : null, $strengths !== '' ? $strengths : null,
        $improvements !== '' ? $improvements : null, $attendanceNotes !== '' ? $attendanceNotes : null,
        $recommendation !== '' ? $recommendation : null
    ]);
    $reportId = $db->lastInsertId();

    logRecruitmentEvent('trainee_report', $reportId, 'submitted', [
        'new_status' => 'submitted',
        'notes' => "trainee #{$traineeId}, week {$weekNumber}"
    ]);

    // Notify the department reviewer that a report is waiting.
    $reviewerRole = mapDepartmentToReviewerRole($department);
    if ($reviewerRole) {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE role = ? AND is_active = 1");
        $stmt->execute([$reviewerRole]);
        foreach ($stmt->fetchAll() as $reviewer) {
            createNotification($reviewer['user_id'], 'trainee_report_submitted', "A week {$weekNumber} report was submitted for review.", "?page=trainee_report");
        }
    }

    Response::success(['report_id' => $reportId, 'week_number' => $weekNumber], 'Report submitted. It cannot be edited after submission.');

} catch (Exception $e) {
    error_log('submit_report.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
