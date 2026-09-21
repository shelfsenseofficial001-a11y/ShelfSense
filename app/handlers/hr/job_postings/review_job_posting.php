<?php
// app/handlers/hr/job_postings/review_job_posting.php
// HR Head approve/reject. Only valid from pending_approval, preventing
// double-processing (refresh/duplicate click cannot re-approve/re-reject).

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/JobPosting.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\JobPosting;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}
if (!Auth::isHRHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. HR Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($input['id']) ? intval($input['id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
// The moderation message -- required when rejecting (goes to
// rejection_reason), optional when approving (goes to approval_message).
// Markdown-formatted (rendered client-side via the shared mdToHtml()).
$message = isset($input['reason']) ? trim($input['reason']) : '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'reject' && $message === '') {
    Response::error('A moderation message is required when rejecting.', 400);
}
if (strlen($message) > 500) {
    Response::error('Moderation message cannot exceed 500 characters.', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM job_postings WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $posting = $stmt->fetch();

    if (!$posting) {
        $db->rollBack();
        Response::notFound('Job posting not found');
    }
    if ($posting['status'] !== 'pending_approval') {
        $db->rollBack();
        Response::error('This posting is not awaiting approval (current status: ' . $posting['status'] . ').', 400);
    }

    $model = new JobPosting();
    if ($action === 'approve') {
        $model->approve($id, Auth::userId(), $message);
        logRecruitmentEvent('job_posting', $id, 'approved', ['previous_status' => 'pending_approval', 'new_status' => 'approved', 'approval_message' => $message]);
        $notifText = $message !== ''
            ? "Your job posting \"{$posting['title']}\" was approved and is now public. HR Head left a moderation message."
            : "Your job posting \"{$posting['title']}\" was approved and is now public.";
        createNotification($posting['created_by'], 'job_posting_approved', $notifText, "?page=hr_job_postings&posting_id={$id}");
        $db->commit();
        Response::success(['id' => $id, 'status' => 'approved'], 'Job posting approved and now publicly visible.');
    } else {
        $model->reject($id, Auth::userId(), $message);
        logRecruitmentEvent('job_posting', $id, 'rejected', ['previous_status' => 'pending_approval', 'new_status' => 'rejected', 'reason' => $message]);
        createNotification($posting['created_by'], 'job_posting_rejected', "Your job posting \"{$posting['title']}\" was rejected. Tap to view the moderation message.", "?page=hr_job_postings&posting_id={$id}");
        $db->commit();
        Response::success(['id' => $id, 'status' => 'rejected'], 'Job posting rejected.');
    }

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('review_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
