<?php
// app/handlers/hr/job_postings/reuse_job_posting.php
// Creates a brand-new draft copying an archived/closed posting's content,
// linked to the original via reused_from_id. The original record is never
// modified -- this is purely additive, preserving full history.

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
if (!in_array($posting['status'], ['closed', 'archived'], true)) {
    Response::error('Only a closed or archived posting can be reused. Current status: ' . $posting['status'], 400);
}

try {
    // Always point at the lineage root, so reusing a post that was itself a
    // reuse still groups all instances together under the original.
    $rootId = $posting['reused_from_id'] ?: $id;

    $openUntil = date('Y-m-d', strtotime('+30 days'));

    $newId = $model->create([
        'title' => $posting['title'],
        'department' => $posting['department'],
        'role' => $posting['role'],
        'description' => $posting['description'],
        'requirements' => $posting['requirements'],
        'salary_range_min' => $posting['salary_range_min'],
        'salary_range_max' => $posting['salary_range_max'],
        'open_until' => $openUntil,
        'status' => 'draft',
        'created_by' => Auth::userId(),
        'reused_from_id' => $rootId
    ]);

    if (!$newId) {
        Response::error('Failed to reuse job posting.', 500);
    }

    logRecruitmentEvent('job_posting', $newId, 'reused', ['new_status' => 'draft', 'notes' => "Reused from posting #{$id}"]);

    Response::success([
        'id' => $newId,
        'reused_from_id' => $rootId,
        'open_until' => $openUntil
    ], 'A new draft was created from this posting. Update the closing date and other details, then submit it for approval.');

} catch (Exception $e) {
    error_log('reuse_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
