<?php
// app/handlers/hr/get_applicant.php

require_once __DIR__ . '/../../models/Applicant.php';

use App\Models\Applicant;
use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

// Check authentication
if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Check role
if (!Auth::isHR() && !Auth::isOwner()) {
    Response::forbidden('Access denied. HR role required.');
}

// Get applicant ID
$applicantId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($applicantId <= 0) {
    Response::error('Invalid applicant ID', 400);
}

// Get applicant
$applicantModel = new Applicant();
$applicant = $applicantModel->getById($applicantId);

if (!$applicant) {
    Response::notFound('Applicant not found');
}

// Add status label
$statusLabels = [
    'pending' => 'New Applicant',
    'initial_scheduled' => 'Initial Interview Pending',
    'initial_passed' => 'Initial Interview Passed',
    'initial_failed' => 'Initial Interview Failed (Rejected)',
    'final_scheduled' => 'Final Interview Pending',
    'final_passed' => 'Final Interview Passed',
    'final_failed' => 'Final Interview Failed (Rejected)',
    'screening' => 'Trainee Contract (In Training)',
    'screening_success' => 'Trainee Contract Completed',
    'screening_failed' => 'Trainee Contract Failed (Rejected)',
    'contract_offered' => 'Job Offer Pending',
    'contract_declined' => 'Offer Declined',
    'hired' => 'Hired',
    'withdrawn' => 'Withdrawn'
];

$applicant['status_label'] = $statusLabels[$applicant['status']] ?? ucfirst($applicant['status']);
$applicant['resume_url'] = '/ShelfSense/public/' . $applicant['resume_path'];

// Skills self-assessment, resolved to the same questionnaire the applicant
// was actually shown on the Apply page (derived from the job posting they
// applied to, not guessed from their own target_role). Ordered list so the
// chart's axis order matches the questionnaire, with rating=null for any
// skill left unanswered (e.g. an applicant who applied before this feature
// existed, or the rare mid-flight posting-department change).
$questionnaireKey = jobPostingToQuestionnaireKey($applicant['job_department'] ?? null, $applicant['job_role'] ?? null);
$applicant['skill_assessment'] = [];
if ($questionnaireKey && isset(SKILL_QUESTIONNAIRES[$questionnaireKey])) {
    foreach (SKILL_QUESTIONNAIRES[$questionnaireKey] as $skill) {
        $applicant['skill_assessment'][] = [
            'key' => $skill['key'],
            'label' => $skill['label'],
            'rating' => $applicant['skill_ratings'][$skill['key']] ?? null,
        ];
    }
}
unset($applicant['skill_ratings'], $applicant['job_department'], $applicant['job_role']);

// Get interview history
foreach ($applicant['interviews'] as &$interview) {
    $interview['hr_name'] = ($interview['first_name'] ?? '') . ' ' . ($interview['last_name'] ?? '');
}

Response::success([
    'applicant' => $applicant
], 'Applicant details fetched successfully');