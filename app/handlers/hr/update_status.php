<?php
// app/handlers/hr/update_status.php

require_once __DIR__ . '/../../models/Applicant.php';
require_once __DIR__ . '/../../models/Interview.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Mailer.php';

use App\Models\Applicant;
use App\Models\Interview;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Database;
use App\Core\Mailer;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR can update status (not trainees)
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot update applicant status.');
}

$input = json_decode(file_get_contents('php://input'), true);
$applicantId = isset($input['applicant_id']) ? intval($input['applicant_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : null;

if ($applicantId <= 0 || empty($action)) {
    Response::error('Missing required fields', 400);
}

$applicantModel = new Applicant();
$applicant = $applicantModel->getById($applicantId);

if (!$applicant) {
    Response::notFound('Applicant not found');
}

if ($reason && strlen($reason) > 250) {
    Response::error('Rejection reason cannot exceed 250 characters.', 400);
}

$currentUserId = Auth::userId();

// Recruitment flow: New Applicant -> Initial Interview -> Trainee Contract
// (screening/training) -> Final Interview -> Regular Contract/Job Offer -> Hired.
// Trainee Contract now follows the Initial Interview directly (not the Final
// Interview), and the Final Interview happens once training is complete.
$statusMap = [
    'accept_initial' => 'initial_scheduled',
    'reject_initial' => 'initial_failed',
    'pass_initial' => 'initial_passed',
    'fail_initial' => 'initial_failed',
    'accept_final' => 'final_scheduled',
    'reject_final' => 'final_failed',
    'pass_final' => 'final_passed',
    'fail_final' => 'final_failed',
    'start_screening' => 'screening',
    'complete_screening' => 'screening_success',
    'fail_screening' => 'screening_failed',
    'offer_contract' => 'contract_offered',
    'accept_contract' => 'hired',
    'decline_contract' => 'contract_declined',
    'withdraw' => 'withdrawn'
];

if (!isset($statusMap[$action])) {
    Response::error('Invalid action', 400);
}

$newStatus = $statusMap[$action];
$oldStatus = $applicant['status'];

$validTransitions = [
    'pending' => ['accept_initial', 'reject_initial'],
    'initial_scheduled' => ['pass_initial', 'fail_initial'],
    'initial_passed' => ['start_screening'],
    'screening' => ['complete_screening', 'fail_screening'],
    'screening_success' => ['accept_final', 'reject_final'],
    'final_scheduled' => ['pass_final', 'fail_final'],
    'final_passed' => ['offer_contract'],
    'contract_offered' => ['accept_contract', 'decline_contract']
];

// Withdrawal is allowed from any non-terminal, non-hired status -- the
// applicant is opting out, not being evaluated against a stage requirement.
$terminalStatuses = ['hired', 'initial_failed', 'final_failed', 'screening_failed', 'contract_declined', 'withdrawn'];
if ($action === 'withdraw') {
    if (in_array($oldStatus, $terminalStatuses, true)) {
        Response::error("Cannot withdraw an applicant already in a final status ('{$oldStatus}').", 400);
    }
} elseif (isset($validTransitions[$oldStatus]) && !in_array($action, $validTransitions[$oldStatus], true)) {
    Response::error("Cannot transition from '{$oldStatus}' using action '{$action}'", 400);
} elseif (!isset($validTransitions[$oldStatus])) {
    Response::error("No further status transitions are valid from '{$oldStatus}'.", 400);
}

if ($action === 'accept_final' || $action === 'pass_final') {
    $interviewModel = new Interview();
    $initialInterview = $interviewModel->getInitialInterview($applicantId);
    
    if ($initialInterview && $initialInterview['hr_user_id'] == $currentUserId) {
        Response::error(
            'You conducted the Initial interview. Another HR must conduct the Final interview.',
            400,
            ['code' => 'INTERVIEW_RULE_VIOLATION']
        );
    }
}

$result = $applicantModel->updateStatus($applicantId, $newStatus, $currentUserId);

if (!$result) {
    Response::error('Failed to update applicant status', 500);
}

if (strpos($action, 'reject_') === 0 || strpos($action, 'fail_') === 0) {
    $stage = str_replace(['reject_', 'fail_'], '', $action);
    $applicantModel->addRejectionReason($applicantId, $currentUserId, $stage, $reason);
}

logRecruitmentEvent('applicant', $applicantId, $action, [
    'previous_status' => $oldStatus,
    'new_status' => $newStatus,
    'reason' => $reason
]);

if (in_array($newStatus, ['initial_failed', 'final_failed'], true)) {
    try {
        $mailer = new Mailer();
        $mailer->sendApplicantStatusUpdate($applicant, $newStatus, $reason ? "Feedback: {$reason}" : null);
    } catch (Exception $e) {
        error_log('update_status.php: rejection email failed: ' . $e->getMessage());
    }
}

if ($action === 'start_screening') {
    Response::success([
        'applicant_id' => $applicantId,
        'requires_trainer_selection' => true,
        'applicant' => [
            'first_name' => $applicant['first_name'],
            'last_name' => $applicant['last_name'],
            'target_role' => $applicant['target_role'],
            'email' => $applicant['email']
        ]
    ], 'Please assign a trainer before creating the trainee account.');
    exit;
}

$notificationMessage = "Applicant {$applicant['first_name']} {$applicant['last_name']} moved to: " . ucfirst(str_replace('_', ' ', $newStatus));
createNotification($currentUserId, 'status_update', $notificationMessage);

Response::success([
    'applicant_id' => $applicantId,
    'old_status' => $oldStatus,
    'new_status' => $newStatus
], 'Status updated successfully');