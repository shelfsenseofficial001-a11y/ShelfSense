<?php
// app/handlers/hr/get_dashboard_stats.php

require_once __DIR__ . '/../../models/Applicant.php';
require_once __DIR__ . '/../../models/Interview.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Models\Applicant;
use App\Models\Interview;
use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Allow HR, HR Head, SuperAdmin, and HR trainees
$targetRole = Auth::getNormalizedTargetRole();
$isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);

if (!Auth::canAccessModule('hr_head') && !$isHrTrainee) {
    Response::forbidden('Access denied. HR role required.');
}

$applicantModel = new Applicant();
$interviewModel = new Interview();

$stats = $applicantModel->getStatusCounts();

$currentUserId = Auth::userId();
$upcomingInterviews = $interviewModel->getUpcoming($currentUserId, 5);

foreach ($upcomingInterviews as &$interview) {
    $interview['applicant_name'] = $interview['first_name'] . ' ' . $interview['last_name'];
    $interview['formatted_date'] = date('F j, Y', strtotime($interview['scheduled_date']));
    $interview['formatted_time'] = date('h:i A', strtotime($interview['scheduled_date']));
    $interview['type_label'] = ucfirst($interview['interview_type']) . ' Interview';
}

$db = \App\Core\Database::getInstance()->getConnection();
$monthlyStmt = $db->query("
    SELECT 
        DATE_FORMAT(applied_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM applicants
    WHERE applied_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(applied_date, '%Y-%m')
    ORDER BY month ASC
");
$monthlyData = $monthlyStmt->fetchAll();

$pipelineStmt = $db->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM applicants
    GROUP BY status
");
$pipelineData = $pipelineStmt->fetchAll();

$pipelineLabels = [
    'pending' => 'Pending',
    'initial_scheduled' => 'Initial Interview',
    'initial_passed' => 'Passed Initial',
    'final_scheduled' => 'Final Interview',
    'final_passed' => 'Passed Final',
    'screening' => 'Training',
    'screening_success' => 'Completed Training',
    'contract_offered' => 'Contract',
    'hired' => 'Hired'
];

$pipeline = [];
foreach ($pipelineData as $item) {
    $label = $pipelineLabels[$item['status']] ?? $item['status'];
    $pipeline[] = [
        'label' => $label,
        'count' => (int)$item['count']
    ];
}

Response::success([
    'stats' => $stats,
    'upcoming_interviews' => $upcomingInterviews,
    'monthly_applications' => $monthlyData,
    'pipeline' => $pipeline
], 'Dashboard stats fetched successfully');