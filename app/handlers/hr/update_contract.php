<?php
// app/handlers/hr/update_contract.php
// HR-side contract management: HR may decline a pending contract (e.g. to
// withdraw an offer). Contract terms are fixed at creation -- the offer
// email is sent as soon as the contract is created, so there's no "edit
// before the trainee sees it" step. HR can NOT accept a contract on the
// trainee's behalf -- only the trainee's own acceptance
// (app/handlers/trainee/respond_to_contract.php) activates their employee
// account, so that decision is never available through this HR-facing
// endpoint.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

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
    if ($action === 'accept') {
        Response::forbidden('HR cannot accept a contract on the trainee\'s behalf. Only the trainee\'s own acceptance activates their account.');
    }

    if ($action === 'decline') {
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

        $stmt = $db->prepare("UPDATE contracts SET status = 'declined', declined_at = NOW(), response_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$responseNotes !== '' ? $responseNotes : null, $contractId]);

        $stmt = $db->prepare("UPDATE applicants SET status = 'contract_declined', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$contract['applicant_id']]);

        logRecruitmentEvent('applicant', $contract['applicant_id'], 'contract_declined', [
            'previous_status' => 'contract_offered', 'new_status' => 'contract_declined', 'reason' => $responseNotes
        ]);
        $message = 'Contract declined.';

        createNotification(Auth::userId(), 'contract_declined', "Contract declined for employee ID: " . $contract['employee_user_id']);
        $db->commit();

        Response::success(['contract_id' => $contractId, 'action' => $action], $message);
    }

    Response::error('Invalid action', 400);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('update_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
