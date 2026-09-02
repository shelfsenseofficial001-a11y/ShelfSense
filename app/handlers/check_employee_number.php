<?php
// app/handlers/check_employee_number.php
// Public (pre-login) endpoint backing the Staff Portal landing-page gate.
// Confirms an employee number exists -- nothing more. No password is
// checked here; that still happens on the actual Login page.

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/PortalGate.php';

use App\Core\Database;
use App\Core\Response;
use App\Core\PortalGate;

header('Content-Type: application/json');

if (PortalGate::isLockedOut()) {
    $seconds = PortalGate::lockedOutSecondsRemaining();
    Response::error(
        'Too many attempts. Try again in ' . ceil($seconds / 60) . ' minute' . (ceil($seconds / 60) === 1 ? '' : 's') . '.',
        429,
        ['locked_out' => true, 'seconds_remaining' => $seconds]
    );
}

$input = json_decode(file_get_contents('php://input'), true);
$employeeNumber = isset($input['employee_number']) ? trim($input['employee_number']) : '';

if ($employeeNumber === '') {
    Response::error('Please enter an employee number.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT employee_number FROM users WHERE employee_number = ? AND is_active = 1");
    $stmt->execute([$employeeNumber]);
    $found = $stmt->fetch();

    if (!$found) {
        PortalGate::recordFailure();
        $lockedOutNow = PortalGate::isLockedOut();
        Response::error(
            $lockedOutNow ? 'Too many attempts. Try again shortly.' : 'Employee number not found.',
            404,
            [
                'locked_out' => $lockedOutNow,
                'attempts_remaining' => PortalGate::attemptsRemaining(),
                'seconds_remaining' => $lockedOutNow ? PortalGate::lockedOutSecondsRemaining() : 0
            ]
        );
    }

    PortalGate::recordSuccess();
    PortalGate::pass($found['employee_number']);

    Response::success([
        'employee_number' => $found['employee_number'],
        'redirect' => '?page=login'
    ], 'Employee number verified');

} catch (Exception $e) {
    error_log('check_employee_number.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
