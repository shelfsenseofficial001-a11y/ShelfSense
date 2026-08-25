<?php
// app/handlers/trainee/respond_to_contract.php
// The trainee/applicant accepts or declines their own Hired Contract.
// Ownership is verified against the logged-in user's own user_id -- nobody
// can respond to someone else's contract. Shares the same activation logic
// as the HR-side path (activateEmployeeFromAcceptedContract()) so an
// account is never activated twice regardless of which path triggers it.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\Mailer;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}
if (!Auth::isTrainee() && !Auth::isSuperAdmin()) {
    Response::forbidden('Only the trainee themselves may respond to their contract.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$contractId = isset($input['contract_id']) ? intval($input['contract_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$responseNotes = isset($input['response_notes']) ? trim($input['response_notes']) : '';

if ($contractId <= 0 || !in_array($action, ['accept', 'decline'], true)) {
    Response::error('Invalid request', 400);
}
if (strlen($responseNotes) > 500) {
    Response::error('Notes cannot exceed 500 characters.', 400);
}

$db = Database::getInstance()->getConnection();

try {
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
    if ((int)$contract['user_id'] !== (int)Auth::userId()) {
        $db->rollBack();
        Response::forbidden('This is not your contract.');
    }
    if ($contract['status'] !== 'pending') {
        $db->rollBack();
        Response::error('This contract has already been processed.', 400);
    }

    if ($action === 'accept') {
        activateEmployeeFromAcceptedContract($db, $contract);
        $message = 'Contract accepted! Your employee account is now active.';

        try {
            $mailer = new Mailer();
            $mailer->sendApplicantStatusUpdate(
                ['email' => $contract['applicant_email'], 'first_name' => $contract['first_name']],
                'hired'
            );
        } catch (Exception $e) {
            error_log('respond_to_contract.php: hired email failed: ' . $e->getMessage());
        }

        // Notify HR so they see the acceptance without polling.
        $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $head) {
            createNotification($head['user_id'], 'contract_accepted', "{$contract['first_name']} {$contract['applicant_last_name']} accepted their Hired Contract.", "?page=hr_contracts");
        }
    } else {
        $stmt = $db->prepare("UPDATE contracts SET status = 'declined', declined_at = NOW(), response_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$responseNotes !== '' ? $responseNotes : null, $contractId]);

        $stmt = $db->prepare("UPDATE applicants SET status = 'contract_declined', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$contract['applicant_id']]);

        logRecruitmentEvent('applicant', $contract['applicant_id'], 'contract_declined_by_applicant', [
            'previous_status' => 'contract_offered', 'new_status' => 'contract_declined', 'reason' => $responseNotes
        ]);

        $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1");
        $stmt->execute();
        foreach ($stmt->fetchAll() as $head) {
            createNotification($head['user_id'], 'contract_declined', "{$contract['first_name']} {$contract['applicant_last_name']} declined their Hired Contract.", "?page=hr_contracts");
        }

        $message = 'Contract declined.';
    }

    $db->commit();
    Response::success(['contract_id' => $contractId, 'action' => $action], $message);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('respond_to_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
