<?php
// app/handlers/pos/paymongo_create_intent.php
// Starts a PayMaya payment for the current cart total via PayMongo's
// Payment Intent workflow (Sources doesn't support paymaya as a type --
// see app/core/PayMongo.php's class doc comment). Runs all three setup
// steps -- create the Intent, create a paymaya Payment Method, attach
// them -- in one request, and returns the redirect_url the customer
// approves on their own phone. The register shows it as a QR code and
// polls paymongo_intent_status.php.

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

if ($amount <= 0) {
    Response::error('Invalid amount.', 400);
}

try {
    $amountCentavos = (int)round($amount * 100);

    // Same public, no-login landing page the GCash Sources flow uses --
    // see paymongo_create_source.php for why it can never be a
    // staff-only page like pos_checkout.
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $returnUrl = $scheme . '://' . $host . '/ShelfSense/public/?page=pos_payment_result';

    $intent = PayMongo::createPaymentIntent($amountCentavos, 'ShelfSense POS sale');
    $method = PayMongo::createPaymentMethod('paymaya');
    $attached = PayMongo::attachPaymentMethod($intent['id'], $method['id'], $intent['client_key'], $returnUrl);

    if (empty($attached['redirect_url'])) {
        // Nothing to show the customer -- PayMongo didn't hand back a
        // redirect, so this attempt can't proceed as an e-wallet payment.
        Response::error('PayMaya did not return an approval link. Please try again.');
    }

    Response::success([
        'intent_id' => $intent['id'],
        'checkout_url' => $attached['redirect_url']
    ], 'Payment intent created');

} catch (Exception $e) {
    error_log('paymongo_create_intent.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
