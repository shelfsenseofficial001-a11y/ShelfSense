<?php
// app/handlers/hr/schedule_interview.php

require_once __DIR__ . '/../../models/Applicant.php';
require_once __DIR__ . '/../../models/Interview.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
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

// Block all trainees from scheduling interviews
if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot schedule interviews. Please ask a full HR staff.');
}

// Allow only HR and SuperAdmin
if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$applicantId = isset($input['applicant_id']) ? intval($input['applicant_id']) : 0;
$interviewType = isset($input['interview_type']) ? trim($input['interview_type']) : '';
$scheduledDate = isset($input['scheduled_date']) ? trim($input['scheduled_date']) : '';
$gmeetLink = isset($input['gmeet_link']) ? trim($input['gmeet_link']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';

if ($applicantId <= 0 || empty($interviewType) || empty($scheduledDate)) {
    Response::error('Missing required fields', 400);
}

if (empty($gmeetLink)) {
    Response::error('Gmeet link is required.', 400);
}

if (strlen($message) > 250) {
    Response::error('Message cannot exceed 250 characters.', 400);
}

$scheduledDateTime = new DateTime($scheduledDate);
$now = new DateTime();
$tomorrow = (new DateTime())->modify('+1 day')->setTime(0, 0, 0);
$maxDate = (new DateTime())->modify('+3 months');

if ($scheduledDateTime < $tomorrow) {
    Response::error('Date cannot be in the past. Please select a date from tomorrow onwards.', 400);
}
if ($scheduledDateTime > $maxDate) {
    Response::error('Date cannot exceed 3 months from now.', 400);
}

if (!in_array($interviewType, ['initial', 'final', 'contract'])) {
    Response::error('Invalid interview type', 400);
}

$currentUserId = Auth::userId();

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$currentUserId]);
    if (!$stmt->fetch()) {
        Response::error('User not found or inactive. Please log in again.', 401);
    }
} catch (Exception $e) {
    Response::error('Database error while validating user.', 500);
}

$applicantModel = new Applicant();
$applicant = $applicantModel->getById($applicantId);

if (!$applicant) {
    Response::notFound('Applicant not found');
}

$validStatuses = [
    'initial' => ['pending', 'initial_scheduled'],
    'final' => ['initial_passed'],
    'contract' => ['screening_success']
];

if (!in_array($applicant['status'], $validStatuses[$interviewType])) {
    Response::error(
        "Cannot schedule {$interviewType} interview. Current status: {$applicant['status']}",
        400
    );
}

if ($interviewType === 'final') {
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

$interviewModel = new Interview();

if ($interviewType === 'final') {
    $existingFinal = $interviewModel->getFinalInterview($applicantId);
    if ($existingFinal) {
        $updated = $interviewModel->update($existingFinal['id'], [
            'scheduled_date' => $scheduledDate,
            'gmeet_link' => $gmeetLink,
            'message' => $message
        ]);
        if (!$updated) {
            Response::error('Failed to update final interview', 500);
        }
        $interviewId = $existingFinal['id'];
        $messageText = 'Final interview updated successfully';
    } else {
        $interviewId = $interviewModel->create([
            'applicant_id' => $applicantId,
            'hr_user_id' => $currentUserId,
            'interview_type' => $interviewType,
            'scheduled_date' => $scheduledDate,
            'gmeet_link' => $gmeetLink,
            'message' => $message
        ]);
        if (!$interviewId) {
            Response::error('Failed to create final interview', 500);
        }
        $messageText = 'Final interview scheduled successfully';
    }
} else {
    $interviewId = $interviewModel->create([
        'applicant_id' => $applicantId,
        'hr_user_id' => $currentUserId,
        'interview_type' => $interviewType,
        'scheduled_date' => $scheduledDate,
        'gmeet_link' => $gmeetLink,
        'message' => $message
    ]);
    if (!$interviewId) {
        Response::error('Failed to schedule interview', 500);
    }
    $messageText = 'Interview scheduled successfully';
}

if ($interviewType === 'initial') {
    $applicantModel->updateStatus($applicantId, 'initial_scheduled');
} elseif ($interviewType === 'final') {
    $applicantModel->updateStatus($applicantId, 'final_scheduled');
}

$notificationMessage = "{$interviewType} interview " . (isset($existingFinal) ? 'updated' : 'scheduled') . " for {$applicant['first_name']} {$applicant['last_name']}";
createNotification($currentUserId, 'interview_scheduled', $notificationMessage);

try {
    $mailer = new Mailer();

    $emailMessage = "Your " . ucfirst($interviewType) . " interview has been scheduled for " .
                    date('F j, Y h:i A', strtotime($scheduledDate)) .
                    ".\n\nJoin via Google Meet: " . $gmeetLink;

    if (!empty($message)) {
        $emailMessage .= "\n\nMessage from HR: " . $message;
    }

    $result = $mailer->sendApplicantStatusUpdate(
        $applicant,
        $interviewType . '_scheduled',
        $emailMessage
    );

    if ($result['success']) {
        error_log("Interview email sent to: " . $applicant['email'] . " for " . $interviewType . " interview");
    } else {
        error_log("Interview email failed: " . $result['message'] . " for applicant: " . $applicant['email']);
    }
} catch (Exception $e) {
    error_log('Failed to send interview email: ' . $e->getMessage());
}

Response::success([
    'interview_id' => $interviewId,
    'applicant_id' => $applicantId,
    'interview_type' => $interviewType,
    'scheduled_date' => $scheduledDate,
    'updated' => isset($existingFinal)
], $messageText);