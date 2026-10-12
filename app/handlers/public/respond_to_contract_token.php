<?php
// app/handlers/public/respond_to_contract_token.php
// No login required -- the applicant/trainee accepts or declines straight
// from the link in their offer email. The random response_token (not the
// sequential contract id) is the only thing that proves this request is
// theirs; everyone else's security model (session + ownership check) simply
// doesn't apply to someone who was never asked to create an account yet.
// Shares the actual accept/decline logic with the logged-in path
// (respond_to_contract.php) via processContractResponse().

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$token = isset($input['token']) ? trim($input['token']) : '';
$action = isset($input['action']) ? trim($input['action']) : '';
$responseNotes = isset($input['response_notes']) ? trim($input['response_notes']) : '';

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token) || !in_array($action, ['accept', 'decline'], true)) {
    Response::error('Invalid request.', 400);
}
if (strlen($responseNotes) > 500) {
    Response::error('Notes cannot exceed 500 characters.', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT id FROM contracts WHERE response_token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        Response::notFound('This contract link is invalid or has expired.');
    }

    $result = processContractResponse($db, (int)$row['id'], $action, $responseNotes, null);

    if (!$result['ok']) {
        Response::error($result['message'], $result['code']);
    }

    Response::success(['action' => $action], $result['message']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('respond_to_contract_token.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
