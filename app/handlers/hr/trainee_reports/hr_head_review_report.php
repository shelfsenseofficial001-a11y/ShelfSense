<?php
// app/handlers/hr/trainee_reports/hr_head_review_report.php
// HR Head adds their own review note to a forwarded report. This is a
// separate field from both the Trainer's report and the department
// reviewer's observation -- neither of those is ever overwritten.

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
if (!Auth::isHRHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. HR Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$reportId = isset($input['report_id']) ? intval($input['report_id']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($reportId <= 0) {
    Response::error('Invalid report ID', 400);
}
if (mb_strlen($notes) > 2000) {
    Response::error('Notes cannot exceed 2000 characters.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM trainee_reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        Response::notFound('Report not found');
    }
    if (!in_array($report['status'], ['forwarded', 'hr_reviewed'], true)) {
        Response::error('This report has not been forwarded by the department reviewer yet.', 400);
    }

    $stmt = $db->prepare("
        UPDATE trainee_reports
        SET hr_head_id = ?, hr_head_notes = ?, hr_head_reviewed_at = NOW(), status = 'hr_reviewed'
        WHERE id = ?
    ");
    $stmt->execute([Auth::userId(), $notes !== '' ? $notes : null, $reportId]);

    logRecruitmentEvent('trainee_report', $reportId, 'hr_head_reviewed', ['new_status' => 'hr_reviewed']);

    Response::success(['report_id' => $reportId, 'status' => 'hr_reviewed'], 'Review note saved.');

} catch (Exception $e) {
    error_log('hr_head_review_report.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
