<?php
// app/handlers/hr/job_postings/archive_job_posting.php
// action=close: approved -> closed (no longer hiring, still visible in HR history).
// action=archive: approved/closed -> archived (fully deactivated, reusable later).

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
$action = isset($input['action']) ? trim($input['action']) : '';

if ($id <= 0 || !in_array($action, ['close', 'archive'], true)) {
    Response::error('Invalid request', 400);
}

$model = new JobPosting();
$posting = $model->getById($id);
if (!$posting) {
    Response::notFound('Job posting not found');
}

$isOwner = (int)$posting['created_by'] === (int)Auth::userId();
if (!$isOwner && !Auth::isHRHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('You may only archive/close job postings you created.');
}

if ($action === 'close' && $posting['status'] !== 'approved') {
    Response::error('Only an approved, active posting can be marked as no longer hiring.', 400);
}
if ($action === 'archive' && !in_array($posting['status'], ['approved', 'closed'], true)) {
    Response::error('Only an approved or closed posting can be archived.', 400);
}

try {
    if ($action === 'close') {
        $model->close($id);
        logRecruitmentEvent('job_posting', $id, 'closed', ['previous_status' => $posting['status'], 'new_status' => 'closed']);
        Response::success(['id' => $id, 'status' => 'closed'], 'Job posting marked as no longer hiring.');
    } else {
        $model->archive($id);
        logRecruitmentEvent('job_posting', $id, 'archived', ['previous_status' => $posting['status'], 'new_status' => 'archived']);
        Response::success(['id' => $id, 'status' => 'archived'], 'Job posting archived. It can be reused later.');
    }
} catch (Exception $e) {
    error_log('archive_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
