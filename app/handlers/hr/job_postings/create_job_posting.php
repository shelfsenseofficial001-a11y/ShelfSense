<?php
// app/handlers/hr/job_postings/create_job_posting.php

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

$title = isset($input['title']) ? trim($input['title']) : '';
$department = isset($input['department']) ? trim($input['department']) : '';
$role = isset($input['role']) ? trim($input['role']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$requirements = isset($input['requirements']) ? trim($input['requirements']) : '';
$salaryMin = $input['salary_range_min'] ?? null;
$salaryMax = $input['salary_range_max'] ?? null;
$openUntil = isset($input['open_until']) ? trim($input['open_until']) : '';
$submitNow = !empty($input['submit']);

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
} elseif ($openUntil < date('Y-m-d')) {
    $errors['open_until'] = 'Closing date cannot be in the past.';
}

if (!empty($errors)) {
    Response::error('Please correct the highlighted fields.', 400, $errors);
}

try {
    $model = new JobPosting();
    $id = $model->create([
        'title' => $title,
        'department' => $department,
        'role' => $role,
        'description' => $description,
        'requirements' => $requirements !== '' ? $requirements : null,
        'salary_range_min' => $salaryMin !== '' ? $salaryMin : null,
        'salary_range_max' => $salaryMax !== '' ? $salaryMax : null,
        'open_until' => $openUntil,
        'status' => 'draft',
        'created_by' => Auth::userId()
    ]);

    if (!$id) {
        Response::error('Failed to create job posting.', 500);
    }

    logRecruitmentEvent('job_posting', $id, 'created', ['new_status' => 'draft']);

    if ($submitNow) {
        $model->submitForApproval($id);
        logRecruitmentEvent('job_posting', $id, 'submitted_for_approval', ['previous_status' => 'draft', 'new_status' => 'pending_approval']);

        $stmt = \App\Core\Database::getInstance()->getConnection()->prepare("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $head) {
            createNotification($head['user_id'], 'job_posting_submitted', "A new job posting \"{$title}\" is awaiting your review.", "?page=hr_job_postings");
        }
    }

    Response::success(['id' => $id], $submitNow ? 'Job posting created and submitted for approval.' : 'Job posting saved as draft.');

} catch (Exception $e) {
    error_log('create_job_posting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
