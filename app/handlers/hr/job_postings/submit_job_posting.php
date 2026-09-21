<?php
// app/handlers/hr/job_postings/submit_job_posting.php

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
if (!Auth::isHRStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Only HR Staff can submit job postings.');
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
if (!$isOwner && !Auth::isSuperAdmin()) {
    Response::forbidden('You may only submit job postings you created.');
}
if (!in_array($posting['status'], ['draft', 'rejected'], true)) {
    Response::error('Only draft or rejected postings can be submitted for approval. Current status: ' . $posting['status'], 400);
}

// Draft saves only ever required a title (see create/update_job_posting.php)
// -- everything else the Publishing Checklist marks "Required" is enforced
// here instead, right before a posting actually goes live for review. Keep
// this list in sync with JP_CHECKLIST_ITEMS in job_posting_form.js.
$missing = [];
if (trim((string)$posting['title']) === '') $missing[] = 'Job Title';
if (!in_array($posting['department_group'], JOB_POSTING_DEPARTMENT_GROUPS, true)) $missing[] = 'Department';
if (!in_array($posting['department'], JOB_POSTING_DEPARTMENTS, true)) $missing[] = 'Position';
if (trim((string)$posting['location']) === '') $missing[] = 'Location';
if (trim((string)$posting['description']) === '') $missing[] = 'Description';
if (empty($posting['open_until'])) $missing[] = 'Timeline (Closing Date)';
// Qualifications are only a suggestion for most positions, but a real
// requirement for HR, Finance, and Store Manager postings.
if (in_array($posting['department'], ['HR Staff', 'Finance Staff', 'Store Manager'], true) && trim((string)($posting['requirements'] ?? '')) === '') {
    $missing[] = 'Qualifications';
}
if (!empty($missing)) {
    Response::error('This posting is missing required information before it can be submitted: ' . implode(', ', $missing) . '.', 400);
}

try {
    if (!$model->submitForApproval($id)) {
        Response::error('Failed to submit job posting.', 500);
    }

    logRecruitmentEvent('job_posting', $id, 'submitted_for_approval', [
        'previous_status' => $posting['status'],
        'new_status' => 'pending_approval'
    ]);

    $stmt = Database::getInstance()->getConnection()->prepare("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $head) {
        createNotification($head['user_id'], 'job_posting_submitted', "Job posting \"{$posting['title']}\" is awaiting your review.", "?page=hr_job_postings&posting_id={$id}");
    }

    Response::success(['id' => $id, 'status' => 'pending_approval'], 'Submitted for HR Head approval.');

} catch (Exception $e) {
    error_log('submit_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
