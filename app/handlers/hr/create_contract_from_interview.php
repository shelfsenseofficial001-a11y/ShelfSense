<?php
// app/handlers/hr/create_contract_from_interview.php
// "Finalize Hire": HR Head records the terms discussed with the Trainee and
// Owner at the Final Interview (salary, shift, rest days, and any leave/other
// notes) as a Hired Contract. The trainee/applicant must still accept it
// themselves (app/handlers/trainee/respond_to_contract.php) before their
// account is actually promoted -- this step only offers the contract.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only HR Head finalizes a hire -- consistent with HR Head being the one who
// scheduled the Final Interview and coordinated with the Owner.
if (!Auth::isHRHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Only HR Head may finalize a hire.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$applicantId = isset($input['applicant_id']) ? intval($input['applicant_id']) : 0;
$interviewId = isset($input['interview_id']) ? intval($input['interview_id']) : 0;
$shift = isset($input['shift']) ? trim($input['shift']) : '';
$salary = isset($input['salary']) ? $input['salary'] : null;
$startDate = isset($input['start_date']) ? trim($input['start_date']) : '';
$restDays = isset($input['rest_days']) ? trim($input['rest_days']) : '';
$jobDetails = isset($input['job_details']) ? trim($input['job_details']) : '';

if ($applicantId <= 0 || $interviewId <= 0) {
    Response::error('Missing required fields', 400);
}
if (!in_array($shift, ['opening', 'closing', 'midshift'], true)) {
    Response::error('A valid shift (opening, closing, or midshift) is required.', 400);
}
if (!is_numeric($salary) || (float)$salary <= 0) {
    Response::error('A valid salary is required.', 400);
}
if ($startDate === '' || !validateDate($startDate) || $startDate < date('Y-m-d')) {
    Response::error('A valid start date (today or later) is required.', 400);
}
$restDaysArray = array_filter(array_map('trim', explode(',', $restDays)));
$validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
foreach ($restDaysArray as $d) {
    if (!in_array($d, $validDays, true)) {
        Response::error('Invalid rest day value.', 400);
    }
}
if (count($restDaysArray) > 2) {
    Response::error('An employee cannot have more than 2 rest days per week.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    // Final Interview must have already passed -- this is the correct gate
    // in the reordered pipeline (Trainee Contract happens at Initial, Hired
    // Contract happens at Final), not the training-eligibility status.
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, target_role FROM applicants WHERE id = ? AND status = 'final_passed'");
    $stmt->execute([$applicantId]);
    $applicant = $stmt->fetch();

    if (!$applicant) {
        Response::error('Applicant not found or not eligible. The Final Interview must have passed first.', 400);
    }

    $stmt = $db->prepare("SELECT id FROM contracts WHERE applicant_id = ?");
    $stmt->execute([$applicantId]);
    if ($stmt->fetch()) {
        Response::error('A contract already exists for this applicant.', 400);
    }

    $stmt = $db->prepare("SELECT id, user_id FROM trainees WHERE applicant_id = ?");
    $stmt->execute([$applicantId]);
    $trainee = $stmt->fetch();

    if (!$trainee) {
        Response::error('No trainee record found for this applicant.', 400);
    }

    $stmt = $db->prepare("
        INSERT INTO contracts (applicant_id, user_id, contract_type, status, shift, salary, job_details, start_date, rest_days, offered_by, offered_at, created_at)
        VALUES (?, ?, 'hired', 'pending', ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $applicantId, $trainee['user_id'], $shift, $salary,
        $jobDetails !== '' ? $jobDetails : null, $startDate,
        !empty($restDaysArray) ? implode(',', $restDaysArray) : null,
        Auth::userId()
    ]);
    $contractId = $db->lastInsertId();

    $stmt = $db->prepare("UPDATE applicants SET status = 'contract_offered', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$applicantId]);

    createNotification($trainee['user_id'], 'hired_contract_ready', "Your Hired Contract is ready for your review. Please respond from your dashboard.", "?page=dashboard");
    logRecruitmentEvent('applicant', $applicantId, 'hired_contract_offered', [
        'previous_status' => 'final_passed',
        'new_status' => 'contract_offered',
        'notes' => "contract #{$contractId}"
    ]);

    $mailer = new Mailer();
    $mailer->send(
        $applicant['email'],
        'Your Employment Contract - ShelfSense',
        "<p>Dear {$applicant['first_name']},</p><p>Following your Final Interview, your Hired Contract is ready: shift <strong>{$shift}</strong>, salary <strong>₱" . number_format((float)$salary, 2) . "</strong>, starting <strong>" . date('F j, Y', strtotime($startDate)) . "</strong>.</p><p>Please log in to the portal to accept or decline.</p>"
    );

    // Notify the Owner too -- they were present at this Final Interview.
    $ownerStmt = $db->query("SELECT user_id, first_name, email FROM users WHERE role = 'owner' AND is_active = 1");
    foreach ($ownerStmt->fetchAll() as $owner) {
        createNotification($owner['user_id'], 'hired_contract_offered', "Hired Contract offered to {$applicant['first_name']} {$applicant['last_name']} following the Final Interview.");
    }

    Response::success([
        'contract_id' => $contractId,
        'applicant_id' => $applicantId,
        'status' => 'pending'
    ], 'Hired Contract created and sent to the applicant for review.');

} catch (Exception $e) {
    error_log('create_contract_from_interview.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}