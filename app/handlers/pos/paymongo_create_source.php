<?php
// app/handlers/pos/paymongo_create_source.php
// Starts a GCash payment for the current cart total via PayMongo's
// Sources API. Returns a checkout_url the customer approves on their own
// phone -- the register shows it as a QR code and polls
// paymongo_source_status.php. PayMaya does NOT go through this endpoint
// -- it isn't a valid Sources type -- see paymongo_create_intent.php.

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/PayMongo.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\PayMongo;

header('Content-Type: application/json');

$isPosSession = Auth::posCheck();
if (!$isPosSession && !(Auth::check() && (Auth::isEmployee() || Auth::isSuperAdmin() || Auth::isStoreManager()))) {
    Response::unauthorized('Please login to access this resource');
}

$input = json_decode(file_get_contents('php://input'), true);
$amount = isset($input['amount']) ? round((float)$input['amount'], 2) : 0;
$type = isset($input['type']) ? (string)$input['type'] : '';

if ($amount <= 0) {
    Response::error('Invalid amount.', 400);
}
if ($type !== 'gcash') {
    Response::error('Invalid payment type.', 400);
}

try {
    $amountCentavos = (int)round($amount * 100);

    // The redirect the customer's *phone* lands on after tapping
    // approve/fail. HTTP_HOST
    // must therefore already be an address that phone can reach: if the
    // register itself is loaded via "localhost", this redirect can never
    // resolve on a separate device -- the register needs to be opened via
    // the host machine's LAN IP (e.g. http://192.168.x.x/ShelfSense/public/...)
    // for the QR to work when scanned with an actual phone.
    //
    // This must be a public, no-login page (pos_payment_result), never
    // pos_checkout itself -- the customer's phone has no POS session, so
    // sending it to a staff-only page just bounces it through the login
    // guard into the public landing page instead of a real confirmation.
    // The register (a completely separate session) is what actually
    // finalizes the payment, by polling the Source's status -- see
    // startPayMongoPolling() in pos.js -- so this redirect is purely
    // informational for whoever is holding the phone.
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirectBase = $scheme . '://' . $host . '/ShelfSense/public/?page=pos_payment_result';

    $source = PayMongo::createEwalletSource($amountCentavos, $type, $redirectBase, $redirectBase);

    Response::success([
        'source_id' => $source['id'],
        'checkout_url' => $source['checkout_url']
    ], 'Payment source created');

} catch (Exception $e) {
    error_log('paymongo_create_source.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
