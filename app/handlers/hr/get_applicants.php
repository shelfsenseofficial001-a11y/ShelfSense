<?php
// app/handlers/hr/get_applicants.php

require_once __DIR__ . '/../../models/Applicant.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Applicant;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR (not trainees) can access applicants
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

// Trainees cannot access applicants
if (Auth::isTrainee()) {
    Response::forbidden('Access denied. Trainees cannot access applicants.');
}

try {
    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 15;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $role = isset($_GET['role']) ? trim($_GET['role']) : '';

    if (strlen($search) > 100) {
        Response::error('Search term cannot exceed 100 characters.', 400);
    }

    $filters = [
        'status' => $status,
        'search' => $search,
        'role' => $role
    ];

    $applicantModel = new Applicant();
    $result = $applicantModel->getAll($page, $limit, $filters);

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

    foreach ($result['applicants'] as &$applicant) {
        $applicant['status_label'] = $statusLabels[$applicant['status']] ?? ucfirst($applicant['status']);
        $applicant['resume_url'] = '/ShelfSense/public/' . $applicant['resume_path'];
    }

    $stats = $applicantModel->getStatusCounts();

    Response::success([
        'applicants' => $result['applicants'],
        'pagination' => $result['pagination'],
        'stats' => $stats,
        'filters' => $filters
    ], 'Applicants fetched successfully');

} catch (Exception $e) {
    error_log('get_applicants.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}