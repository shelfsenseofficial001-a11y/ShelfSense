<?php
// app/handlers/hr/create_contract_from_interview.php

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
$interviewId = isset($input['interview_id']) ? intval($input['interview_id']) : 0;

if ($applicantId <= 0 || $interviewId <= 0) {
    Response::error('Missing required fields', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    // Verify applicant exists and is eligible
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, target_role FROM applicants WHERE id = ? AND status IN ('screening_success')");
    $stmt->execute([$applicantId]);
    $applicant = $stmt->fetch();

    if (!$applicant) {
        Response::error('Applicant not found or not eligible. Status must be "screening_success".', 400);
    }

    // Check if contract already exists
    $stmt = $db->prepare("SELECT id FROM contracts WHERE applicant_id = ?");
    $stmt->execute([$applicantId]);
    if ($stmt->fetch()) {
        Response::error('A contract already exists for this applicant.', 400);
    }

    // Get trainee user_id
    $stmt = $db->prepare("SELECT user_id FROM trainees WHERE applicant_id = ?");
    $stmt->execute([$applicantId]);
    $trainee = $stmt->fetch();

    if (!$trainee) {
        Response::error('No trainee record found for this applicant.', 400);
    }

    // Create pending contract with placeholder shift/salary/start_date
    // (contracts.shift, .salary, .start_date are NOT NULL with no default;
    // HR fills in the real values afterward via update_contract.php's update_details action)
    $stmt = $db->prepare("
        INSERT INTO contracts (applicant_id, user_id, status, shift, salary, start_date, created_at)
        VALUES (?, ?, 'pending', 'opening', 0, ?, NOW())
    ");
    $stmt->execute([$applicantId, $trainee['user_id'], date('Y-m-d')]);
    $contractId = $db->lastInsertId();

    // Update interview status to 'completed' (no result needed)
    $stmt = $db->prepare("UPDATE interviews SET status = 'completed', result = 'passed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$interviewId]);

    // Update applicant status to contract_offered
    $stmt = $db->prepare("UPDATE applicants SET status = 'contract_offered', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$applicantId]);

    // Mark trainee as eligible (if not already)
    $stmt = $db->prepare("UPDATE trainees SET eligible_for_contract = 1, updated_at = NOW() WHERE applicant_id = ?");
    $stmt->execute([$applicantId]);

    Response::success([
        'contract_id' => $contractId,
        'applicant_id' => $applicantId,
        'status' => 'pending'
    ], 'Contract created successfully. HR can now edit the contract details.');

} catch (Exception $e) {
    error_log('create_contract_from_interview.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}