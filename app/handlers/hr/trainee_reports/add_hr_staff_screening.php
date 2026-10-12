<?php
// app/handlers/hr/trainee_reports/add_hr_staff_screening.php
// HR Staff screens a department head's forwarded report and forwards it on
// to HR Head for the final decision. This step is skipped entirely for HR
// department trainees (the trainer is already HR Staff, so there's no
// independent HR Staff member available to screen it) -- see
// add_observation.php, which routes 'hr' department reports straight to
// HR Head instead of here.

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
$reportId = isset($input['report_id']) ? intval($input['report_id']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($reportId <= 0) {
    Response::error('Invalid report ID', 400);
}
if (mb_strlen($notes) > 3000) {
    Response::error('Notes cannot exceed 3000 characters.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM trainee_reports WHERE id = ? FOR UPDATE");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        $db->rollBack();
        Response::notFound('Report not found');
    }

    if (!Auth::isHRStaff() && !Auth::isSuperAdmin()) {
        $db->rollBack();
        Response::forbidden('Only HR Staff may screen this report.');
    }
    if ((int)$report['trainer_id'] === (int)Auth::userId()) {
        $db->rollBack();
        Response::forbidden('You submitted this report as the Trainer and cannot also screen it.');
    }
    if ($report['department'] === 'hr') {
        $db->rollBack();
        Response::error('HR trainees skip HR Staff screening -- their trainer is already HR Staff, so this report goes straight to HR Head.', 400);
    }
    if ($report['status'] !== 'forwarded') {
        $db->rollBack();
        Response::error('This report is not awaiting HR Staff screening.', 400);
    }

    $stmt = $db->prepare("
        UPDATE trainee_reports
        SET hr_staff_id = ?, hr_staff_notes = ?, hr_staff_reviewed_at = NOW(), status = 'hr_staff_reviewed'
        WHERE id = ? AND status = 'forwarded'
    ");
    $stmt->execute([Auth::userId(), $notes !== '' ? $notes : null, $reportId]);
    if ($stmt->rowCount() === 0) {
        $db->rollBack();
        Response::error('This report is not awaiting HR Staff screening.', 400);
    }

    logRecruitmentEvent('trainee_report', $reportId, 'hr_staff_screened', [
        'previous_status' => 'forwarded', 'new_status' => 'hr_staff_reviewed'
    ]);

    $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $head) {
        createNotification($head['user_id'], 'trainee_report_screened', "A trainee's week {$report['week_number']} report passed HR Staff screening and needs your final review.", "?page=trainee_report");
    }

    $db->commit();
    Response::success(['report_id' => $reportId, 'status' => 'hr_staff_reviewed'], 'Report screened and forwarded to HR Head.');

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('add_hr_staff_screening.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
