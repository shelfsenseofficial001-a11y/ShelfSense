<?php
// app/handlers/hr/job_postings/delete_job_posting.php
// Permanent delete -- restricted to draft/rejected postings only (never
// pending_approval/approved/closed/archived, which are real hiring history
// or currently live). Applicants can only ever attach to an approved
// posting, so a draft/rejected one is always safe to remove outright.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/JobPosting.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\JobPosting;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}
if (!Auth::isHR() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($input['id']) ? intval($input['id']) : 0;
if ($id <= 0) {
    Response::error('Invalid job posting ID', 400);
}

$model = new JobPosting();
$posting = $model->getById($id);
if (!$posting) {
    Response::notFound('Job posting not found');
}

$isOwner = (int)$posting['created_by'] === (int)Auth::userId();
if (!$isOwner && !Auth::isHRHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('You may only delete job postings you created.');
}
if (!in_array($posting['status'], ['draft', 'rejected'], true)) {
    Response::error('Only a draft or rejected posting can be permanently deleted. Anything that has been submitted for review must be archived instead.', 400);
}

try {
    $model->delete($id);
    logRecruitmentEvent('job_posting', $id, 'deleted', ['previous_status' => $posting['status'], 'new_status' => null]);
    Response::success(['id' => $id], 'Job posting permanently deleted.');
} catch (Exception $e) {
    error_log('delete_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
