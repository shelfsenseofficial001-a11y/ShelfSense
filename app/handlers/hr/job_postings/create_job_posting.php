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
$departmentGroup = isset($input['department_group']) ? trim($input['department_group']) : '';
$department = isset($input['department']) ? trim($input['department']) : '';
$role = isset($input['role']) ? trim($input['role']) : '';
$location = isset($input['location']) ? trim($input['location']) : '';
$employmentType = 'Full-Time'; // no longer chosen at posting time
$slots = isset($input['slots']) ? trim((string)$input['slots']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$requirements = isset($input['requirements']) ? trim($input['requirements']) : '';
$responsibilities = isset($input['responsibilities']) ? trim($input['responsibilities']) : '';
$salaryMin = $input['salary_range_min'] ?? null;
$salaryMax = $input['salary_range_max'] ?? null;
$openUntil = isset($input['open_until']) ? trim($input['open_until']) : '';
$submitNow = !empty($input['submit']);

$errors = [];
if ($title === '' || mb_strlen($title) > 100) $errors['title'] = 'Title is required (max 100 characters).';
if (!in_array($departmentGroup, JOB_POSTING_DEPARTMENT_GROUPS, true)) $errors['department_group'] = 'Please select a valid department.';
if (!in_array($department, JOB_POSTING_DEPARTMENTS, true)) {
    $errors['department'] = 'Please select a valid position.';
} elseif (!empty($departmentGroup) && !in_array($department, JOB_POSTING_GROUP_POSITIONS[$departmentGroup] ?? [], true)) {
    $errors['department'] = 'This position does not belong to the selected department.';
}
if ($role === '' || mb_strlen($role) > 50) $errors['role'] = 'Role is required (max 50 characters).';
if (mb_strlen($location) > 150) $errors['location'] = 'Location cannot exceed 150 characters.';
if ($slots !== '' && (!ctype_digit($slots) || (int)$slots < 1 || (int)$slots > 299)) $errors['slots'] = 'Slots must be a whole number between 1 and 299, or left blank for unlimited.';
if ($description === '' || mb_strlen($description) > 5000) $errors['description'] = 'Description is required (max 5000 characters).';
if ($requirements !== '' && mb_strlen($requirements) > 5000) $errors['requirements'] = 'Requirements cannot exceed 5000 characters.';
if ($responsibilities !== '' && mb_strlen($responsibilities) > 5000) $errors['responsibilities'] = 'Responsibilities cannot exceed 5000 characters.';
if ($salaryMin !== null && $salaryMin !== '' && (!is_numeric($salaryMin) || $salaryMin < 0)) $errors['salary_range_min'] = 'Minimum salary must be a non-negative number.';
if ($salaryMax !== null && $salaryMax !== '' && (!is_numeric($salaryMax) || $salaryMax < 0)) $errors['salary_range_max'] = 'Maximum salary must be a non-negative number.';
if ($salaryMin !== null && $salaryMin !== '' && $salaryMax !== null && $salaryMax !== '' && (float)$salaryMax < (float)$salaryMin) {
    $errors['salary_range_max'] = 'Maximum salary cannot be less than minimum salary.';
}
if ($openUntil === '' || !validateDate($openUntil)) {
    $errors['open_until'] = 'A valid closing date (YYYY-MM-DD) is required.';
} elseif ($openUntil < date('Y-m-d')) {
    $errors['open_until'] = 'Closing date cannot be in the past.';
} elseif ($openUntil > date('Y-m-d', strtotime('+6 months'))) {
    $errors['open_until'] = 'Closing date cannot be more than 6 months out.';
}

if (empty($errors)) {
    $db = \App\Core\Database::getInstance()->getConnection();
    $liveStatuses = "'draft','pending_approval','approved'";
    if ($title !== '') {
        $stmt = $db->prepare("SELECT id FROM job_postings WHERE LOWER(title) = LOWER(?) AND status IN ($liveStatuses)");
        $stmt->execute([$title]);
        if ($stmt->fetch()) $errors['title'] = 'An active job posting with this title already exists.';
    }
    if ($role !== '') {
        $stmt = $db->prepare("SELECT id FROM job_postings WHERE LOWER(role) = LOWER(?) AND status IN ($liveStatuses)");
        $stmt->execute([$role]);
        if ($stmt->fetch()) $errors['role'] = 'An active job posting with this role already exists.';
    }
}

if (!empty($errors)) {
    Response::error('Please correct the highlighted fields.', 400, $errors);
}

try {
    $model = new JobPosting();
    $id = $model->create([
        'title' => $title,
        'department_group' => $departmentGroup,
        'department' => $department,
        'role' => $role,
        'location' => $location !== '' ? $location : null,
        'employment_type' => $employmentType,
        'slots' => $slots !== '' ? (int)$slots : null,
        'description' => $description,
        'requirements' => $requirements !== '' ? $requirements : null,
        'responsibilities' => $responsibilities !== '' ? $responsibilities : null,
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
