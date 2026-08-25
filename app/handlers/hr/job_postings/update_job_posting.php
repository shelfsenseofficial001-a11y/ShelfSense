<?php
// app/handlers/hr/job_postings/update_job_posting.php
// HR Staff may edit their own draft/rejected posts. HR Head may edit/overwrite
// any post that isn't archived (archived posts are historical and immutable).

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
$isHead = Auth::isHRHead() || Auth::isSuperAdmin();

if ($posting['status'] === 'archived') {
    Response::error('Archived job postings are historical and cannot be edited. Reuse it to create a new instance instead.', 400);
}
if (!$isHead) {
    if (!$isOwner) {
        Response::forbidden('You may only edit job postings you created.');
    }
    if (!in_array($posting['status'], ['draft', 'rejected'], true)) {
        Response::error('This posting is under review or already active and can no longer be edited directly.', 400);
    }
}

$title = isset($input['title']) ? trim($input['title']) : $posting['title'];
$department = isset($input['department']) ? trim($input['department']) : $posting['department'];
$role = isset($input['role']) ? trim($input['role']) : $posting['role'];
$description = isset($input['description']) ? trim($input['description']) : $posting['description'];
$requirements = array_key_exists('requirements', $input) ? trim((string)$input['requirements']) : ($posting['requirements'] ?? '');
$salaryMin = array_key_exists('salary_range_min', $input) ? $input['salary_range_min'] : $posting['salary_range_min'];
$salaryMax = array_key_exists('salary_range_max', $input) ? $input['salary_range_max'] : $posting['salary_range_max'];
$openUntil = isset($input['open_until']) ? trim($input['open_until']) : $posting['open_until'];

$errors = [];
if ($title === '' || mb_strlen($title) > 100) $errors['title'] = 'Title is required (max 100 characters).';
if ($department === '' || mb_strlen($department) > 50) $errors['department'] = 'Department is required (max 50 characters).';
if ($role === '' || mb_strlen($role) > 50) $errors['role'] = 'Role is required (max 50 characters).';
if ($description === '' || mb_strlen($description) > 5000) $errors['description'] = 'Description is required (max 5000 characters).';
if ($requirements !== '' && mb_strlen($requirements) > 5000) $errors['requirements'] = 'Requirements cannot exceed 5000 characters.';
if ($salaryMin !== null && $salaryMin !== '' && (!is_numeric($salaryMin) || $salaryMin < 0)) $errors['salary_range_min'] = 'Minimum salary must be a non-negative number.';
if ($salaryMax !== null && $salaryMax !== '' && (!is_numeric($salaryMax) || $salaryMax < 0)) $errors['salary_range_max'] = 'Maximum salary must be a non-negative number.';
if ($salaryMin !== null && $salaryMin !== '' && $salaryMax !== null && $salaryMax !== '' && (float)$salaryMax < (float)$salaryMin) {
    $errors['salary_range_max'] = 'Maximum salary cannot be less than minimum salary.';
}
if ($openUntil === '' || !validateDate($openUntil)) {
    $errors['open_until'] = 'A valid closing date (YYYY-MM-DD) is required.';
}

if (!empty($errors)) {
    Response::error('Please correct the highlighted fields.', 400, $errors);
}

try {
    $result = $model->update($id, [
        'title' => $title, 'department' => $department, 'role' => $role,
        'description' => $description, 'requirements' => $requirements !== '' ? $requirements : null,
        'salary_range_min' => $salaryMin !== '' ? $salaryMin : null,
        'salary_range_max' => $salaryMax !== '' ? $salaryMax : null,
        'open_until' => $openUntil
    ]);

    if (!$result) {
        Response::error('Failed to update job posting.', 500);
    }

    logRecruitmentEvent('job_posting', $id, $isHead && !$isOwner ? 'hr_head_overwrite' : 'updated', [
        'previous_status' => $posting['status'],
        'new_status' => $posting['status']
    ]);

    Response::success(['id' => $id], 'Job posting updated.');

} catch (Exception $e) {
    error_log('update_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
