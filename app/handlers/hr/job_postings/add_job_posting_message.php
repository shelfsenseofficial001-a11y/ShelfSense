<?php
// app/handlers/hr/job_postings/add_job_posting_message.php
// HR Head can drop a moderation message into a posting's thread at any
// time -- not only while approving/rejecting (see review_job_posting.php,
// which also appends to this same thread on a decision).

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
if (!Auth::isHRHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. HR Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($input['id']) ? intval($input['id']) : 0;
$message = isset($input['message']) ? trim($input['message']) : '';

if ($id <= 0) {
    Response::error('Invalid job posting ID', 400);
}
if ($message === '') {
    Response::error('Message cannot be empty.', 400);
}
if (strlen($message) > 500) {
    Response::error('Message cannot exceed 500 characters.', 400);
}

$model = new JobPosting();
$posting = $model->getById($id);
if (!$posting) {
    Response::notFound('Job posting not found');
}

try {
    $messageId = $model->addMessage($id, Auth::userId(), $message, 'comment');
    logRecruitmentEvent('job_posting', $id, 'moderation_message', ['message' => $message]);
    createNotification($posting['created_by'], 'job_posting_message', "HR Head left a new message on \"{$posting['title']}\".", "?page=hr_job_postings&posting_id={$id}");

    Response::success(['id' => $messageId], 'Message posted.');
} catch (Exception $e) {
    error_log('add_job_posting_message.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
