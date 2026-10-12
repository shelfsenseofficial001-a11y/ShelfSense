<?php
// app/handlers/trainee/respond_to_contract.php
// The trainee/applicant accepts or declines their own Hired Contract while
// logged in. Ownership is verified against the logged-in user's own
// user_id. The actual accept/decline logic is shared with the no-login,
// token-based email link (respond_to_contract_token.php) via
// processContractResponse() in helpers/functions.php.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

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
    $result = processContractResponse($db, $contractId, $action, $responseNotes, Auth::userId());

    if (!$result['ok']) {
        Response::error($result['message'], $result['code']);
    }

    // The accepting user is always the currently logged-in session
    // (enforced by processContractResponse's ownership check), so refresh
    // it immediately for a Hired Contract -- otherwise Auth::role()/
    // isTrainee() keep evaluating against the pre-hire 'trainee' role for
    // the rest of this login, even though the users row itself is already
    // correct.
    if ($action === 'accept' && $result['contract']['contract_type'] !== 'trainee') {
        $stmt = $db->prepare("SELECT role, permission_level, employee_number, is_first_login FROM users WHERE user_id = ?");
        $stmt->execute([Auth::userId()]);
        if ($refreshed = $stmt->fetch()) {
            $_SESSION['role'] = $refreshed['role'];
            $_SESSION['permission_level'] = (int)$refreshed['permission_level'];
            $_SESSION['employee_number'] = $refreshed['employee_number'];
            $_SESSION['is_first_login'] = $refreshed['is_first_login'];
        }
    }

    Response::success(['contract_id' => $contractId, 'action' => $action], $result['message']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('respond_to_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
