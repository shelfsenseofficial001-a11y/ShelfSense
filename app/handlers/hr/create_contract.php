<?php
// app/handlers/hr/create_contract.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isHR() && !Auth::isOwner()) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$applicantId = isset($input['applicant_id']) ? intval($input['applicant_id']) : 0;
$shift = isset($input['shift']) ? trim($input['shift']) : '';
$salary = isset($input['salary']) ? floatval($input['salary']) : 0;
$jobDetails = isset($input['job_details']) ? trim($input['job_details']) : '';
$startDate = isset($input['start_date']) ? trim($input['start_date']) : '';
$restDays = isset($input['rest_days']) ? trim($input['rest_days']) : '';

if ($applicantId <= 0 || empty($shift) || $salary <= 0 || empty($startDate)) {
    Response::error('Missing required fields', 400);
}

if (strlen($jobDetails) > 250) {
    Response::error('Job details cannot exceed 250 characters.', 400);
}

// ✅ Validate rest days
if (!empty($restDays)) {
    $daysArray = explode(',', $restDays);
    if (count($daysArray) > 2) {
        Response::error('You can only select up to 2 rest days.', 400);
    }
    $validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    foreach ($daysArray as $day) {
        if (!in_array(trim($day), $validDays)) {
            Response::error('Invalid day selected.', 400);
        }
    }
} else {
    $restDays = '';
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify applicant exists and is screening_success
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, target_role FROM applicants WHERE id = ? AND status = 'screening_success'");
    $stmt->execute([$applicantId]);
    $applicant = $stmt->fetch();
    
    if (!$applicant) {
        Response::error('Applicant not eligible for contract. They must have completed training.', 400);
    }
    
    // Get the trainee user_id
    $stmt = $db->prepare("SELECT user_id FROM trainees WHERE applicant_id = ?");
    $stmt->execute([$applicantId]);
    $trainee = $stmt->fetch();
    
    if (!$trainee) {
        Response::error('No trainee record found for this applicant.', 400);
    }
    $userId = $trainee['user_id'];
    
    // Create contract with rest_days
    $stmt = $db->prepare("
        INSERT INTO contracts (applicant_id, user_id, shift, salary, job_details, start_date, rest_days, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $applicantId,
        $userId,
        $shift,
        $salary,
        $jobDetails,
        $startDate,
        $restDays
    ]);
    $contractId = $db->lastInsertId();
    
    // Update applicant status to contract_offered
    $stmt = $db->prepare("UPDATE applicants SET status = 'contract_offered', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$applicantId]);
    
    // Create notification
    $notificationMessage = "Contract offered to {$applicant['first_name']} {$applicant['last_name']}";
    createNotification(Auth::userId(), 'contract_offered', $notificationMessage);
    
    Response::success([
        'contract_id' => $contractId,
        'applicant_id' => $applicantId
    ], 'Contract created successfully');
    
} catch (Exception $e) {
    error_log('create_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}