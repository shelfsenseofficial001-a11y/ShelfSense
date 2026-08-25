<?php
// app/handlers/hr/trainee_reports/submit_report.php
// The assigned Trainer submits one weekly report. Once inserted, a report's
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
$content = isset($input['report_content']) ? trim($input['report_content']) : '';
$rating = isset($input['performance_rating']) ? trim($input['performance_rating']) : '';
$strengths = isset($input['strengths']) ? trim($input['strengths']) : '';
$improvements = isset($input['improvements']) ? trim($input['improvements']) : '';
$attendanceNotes = isset($input['attendance_notes']) ? trim($input['attendance_notes']) : '';
$recommendation = isset($input['recommendation']) ? trim($input['recommendation']) : '';

if ($traineeId <= 0 || $weekNumber <= 0) {
    Response::error('Invalid trainee or week number', 400);
}
if ($content === '' || mb_strlen($content) > 5000) {
    Response::error('Report content is required (max 5000 characters).', 400);
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
            createNotification($reviewer['user_id'], 'trainee_report_submitted', "A week {$weekNumber} report was submitted for review.", "?page=hr_trainees");
        }
    }

    Response::success(['report_id' => $reportId, 'week_number' => $weekNumber], 'Report submitted. It cannot be edited after submission.');

} catch (Exception $e) {
    error_log('submit_report.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
