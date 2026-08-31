<?php
// app/handlers/hr/trainee_reports/add_observation.php
// The department reviewer (Store Manager / Finance Head / HR Staff) adds a
// separate observation and forwards the report to HR Head. This NEVER
// touches the Trainer's original report_content/strengths/improvements/etc
// fields -- only the distinct reviewer_* columns are written.

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
$observation = isset($input['observation']) ? trim($input['observation']) : '';

if ($reportId <= 0) {
    Response::error('Invalid report ID', 400);
}
if (mb_strlen($observation) > 3000) {
    Response::error('Observation cannot exceed 3000 characters.', 400);
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
    if ($report['status'] !== 'submitted') {
        $db->rollBack();
        Response::error('This report has already been reviewed/forwarded.', 400);
    }

    $requiredRole = mapDepartmentToReviewerRole($report['department']);
    $userRole = Auth::role();
    $isAuthorizedReviewer = ($userRole === $requiredRole) || Auth::isSuperAdmin();
    if (!$isAuthorizedReviewer) {
        $db->rollBack();
        Response::forbidden("Only the {$requiredRole} role may review this trainee's report.");
    }
    if ((int)$report['trainer_id'] === (int)Auth::userId()) {
        $db->rollBack();
        Response::forbidden('You submitted this report as the Trainer and cannot also review it.');
    }

    $stmt = $db->prepare("
        UPDATE trainee_reports
        SET reviewer_id = ?, reviewer_role = ?, reviewer_observation = ?, reviewed_at = NOW(),
            forwarded_at = NOW(), status = 'forwarded'
        WHERE id = ? AND status = 'submitted'
    ");
    $stmt->execute([Auth::userId(), $userRole, $observation !== '' ? $observation : null, $reportId]);
    if ($stmt->rowCount() === 0) {
        $db->rollBack();
        Response::error('This report has already been reviewed/forwarded.', 400);
    }

    logRecruitmentEvent('trainee_report', $reportId, 'forwarded_to_hr_head', [
        'previous_status' => 'submitted', 'new_status' => 'forwarded'
    ]);

    // Once all 12 weekly reports exist and none are still sitting at
    // 'submitted' (i.e. every one has at least been forwarded), the trainee's
    // report set is complete -- this is what surfaces the "Mark as Eligible"
    // action in the Trainees list, so it must flip the moment the last
    // report clears this stage, not just when an HR Head happens to look.
    $stmt = $db->prepare("SELECT COUNT(*) total, SUM(status NOT IN ('forwarded', 'hr_reviewed')) not_forwarded FROM trainee_reports WHERE trainee_id = ?");
    $stmt->execute([$report['trainee_id']]);
    $counts = $stmt->fetch();
    if ((int)$counts['total'] >= 12 && (int)$counts['not_forwarded'] === 0) {
        $db->prepare("UPDATE trainees SET reports_status = 'completed' WHERE id = ? AND reports_status != 'completed'")
            ->execute([$report['trainee_id']]);
    }

    $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $head) {
        createNotification($head['user_id'], 'trainee_report_forwarded', "A trainee's week {$report['week_number']} report was forwarded for your review.", "?page=hr_trainees");
    }

    $db->commit();
    Response::success(['report_id' => $reportId, 'status' => 'forwarded'], 'Observation added and report forwarded to HR Head.');

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('add_observation.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
