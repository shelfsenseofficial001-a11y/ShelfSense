<?php
// app/handlers/pos/paymongo_intent_status.php
// Polled by the register while showing the PayMaya QR, waiting for the
// customer to approve (or fail/cancel) on their own phone. Once status is
// "succeeded", also hands back the settled payment's own id (pay_...) so
// the register can record it as the order's payment_reference -- the
// Payment Intent workflow's equivalent of the GCash/Sources flow's
// separate charge-source call, just arriving as part of the same object.

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

$intentId = isset($_GET['intent_id']) ? (string)$_GET['intent_id'] : '';
if ($intentId === '') {
    Response::error('Missing intent_id.', 400);
}

try {
    $intent = PayMongo::getPaymentIntent($intentId);
    Response::success([
        'status' => $intent['status'],
        'payment_id' => $intent['payment_id']
    ]);
} catch (Exception $e) {
    error_log('paymongo_intent_status.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
