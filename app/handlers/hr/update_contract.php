<?php
// app/handlers/hr/update_contract.php
// HR-side contract management: HR may accept/decline on the trainee's behalf
// (e.g. an offline/paper signature) or edit pending contract details. The
// trainee's own self-service acceptance lives in
// app/handlers/trainee/respond_to_contract.php and shares the same
// activation logic (activateEmployeeFromAcceptedContract()) so an employee
// account is only ever activated once, from either path.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\Mailer;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR can update contracts (not trainees)
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot update contracts through this endpoint.');
}

$input = json_decode(file_get_contents('php://input'), true);
$contractId = isset($input['contract_id']) ? intval($input['contract_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$responseNotes = isset($input['response_notes']) ? trim($input['response_notes']) : '';

if ($contractId <= 0 || empty($action)) {
    Response::error('Missing required fields', 400);
}
if (strlen($responseNotes) > 500) {
    Response::error('Notes cannot exceed 500 characters.', 400);
}

$db = Database::getInstance()->getConnection();

try {
    if (in_array($action, ['accept', 'decline'], true)) {
        $db->beginTransaction();

        $stmt = $db->prepare("
            SELECT c.*, a.target_role, a.email as applicant_email, a.last_name as applicant_last_name,
                   u.first_name, u.user_id as employee_user_id
            FROM contracts c
            JOIN applicants a ON c.applicant_id = a.id
            JOIN users u ON c.user_id = u.user_id
            WHERE c.id = ? FOR UPDATE
        ");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();

        if (!$contract) {
            $db->rollBack();
            Response::notFound('Contract not found');
        }
        if ($contract['status'] !== 'pending') {
            $db->rollBack();
            Response::error('This contract has already been processed.', 400);
        }

        if ($action === 'accept') {
            $result = activateEmployeeFromAcceptedContract($db, $contract);
            $message = 'Contract accepted. Employee hired and schedule created!';

            try {
                $mailer = new Mailer();
                $mailer->sendApplicantStatusUpdate(
                    ['email' => $contract['applicant_email'], 'first_name' => $contract['first_name']],
                    'hired'
                );
            } catch (Exception $e) {
                error_log('update_contract.php: hired email failed: ' . $e->getMessage());
            }
        } else {
            $stmt = $db->prepare("UPDATE contracts SET status = 'declined', declined_at = NOW(), response_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$responseNotes !== '' ? $responseNotes : null, $contractId]);

            $stmt = $db->prepare("UPDATE applicants SET status = 'contract_declined', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$contract['applicant_id']]);

            logRecruitmentEvent('applicant', $contract['applicant_id'], 'contract_declined', [
                'previous_status' => 'contract_offered', 'new_status' => 'contract_declined', 'reason' => $responseNotes
            ]);
            $message = 'Contract declined.';
        }

        createNotification(Auth::userId(), 'contract_' . $action, "Contract {$action}ed for employee ID: " . $contract['employee_user_id']);
        $db->commit();

        Response::success(['contract_id' => $contractId, 'action' => $action], $message);
    }

    if ($action === 'update_details') {
        $shift = isset($input['shift']) ? trim($input['shift']) : '';
        $salary = isset($input['salary']) ? floatval($input['salary']) : 0;
        $startDate = isset($input['start_date']) ? trim($input['start_date']) : '';
        $jobDetails = isset($input['job_details']) ? trim($input['job_details']) : '';
        $decisionDeadline = isset($input['decision_deadline']) ? trim($input['decision_deadline']) : '';
        $restDays = isset($input['rest_days']) ? trim($input['rest_days']) : '';

        if (!validateDate($startDate) || !validateDate($decisionDeadline)) {
            Response::error('Start date and decision deadline must be valid dates (YYYY-MM-DD).', 400);
        }

        $today = date('Y-m-d');
        if ($startDate < $today) {
            Response::error('Start date cannot be in the past.', 400);
        }
        if ($decisionDeadline < $today) {
            Response::error('Decision deadline cannot be in the past.', 400);
        }
        if ($decisionDeadline < $startDate) {
            Response::error('Decision deadline must be after the start date.', 400);
        }

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

        if (empty($shift) || $salary <= 0 || empty($startDate) || empty($decisionDeadline)) {
            Response::error('All required fields must be filled.', 400);
        }

        if (strlen($jobDetails) > 250) {
            Response::error('Job details cannot exceed 250 characters.', 400);
        }

        $stmt = $db->prepare("
            UPDATE contracts
            SET shift = ?, salary = ?, start_date = ?,
                job_details = ?, decision_deadline = ?, rest_days = ?,
                updated_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$shift, $salary, $startDate, $jobDetails, $decisionDeadline, $restDays, $contractId]);

        if ($stmt->rowCount() === 0) {
            Response::error('Contract not found or already processed.', 404);
        }

        createNotification(Auth::userId(), 'contract_update_details', "Contract details updated for contract #{$contractId}");

        Response::success(['contract_id' => $contractId, 'action' => $action], 'Contract details updated successfully.');
    }

    Response::error('Invalid action', 400);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('update_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
