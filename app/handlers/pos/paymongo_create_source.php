<?php
// app/handlers/pos/paymongo_create_source.php
// Starts a GCash/PayMaya payment for the current cart total. Returns a
// checkout_url the customer approves on their own phone -- the register
// shows it as a QR code and polls paymongo_source_status.php.

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
if (!in_array($type, ['gcash', 'paymaya'], true)) {
    Response::error('Invalid payment type.', 400);
}

try {
    $amountCentavos = (int)round($amount * 100);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirectBase = $scheme . '://' . $host . '/ShelfSense/?page=pos_checkout';

    $source = PayMongo::createEwalletSource($amountCentavos, $type, $redirectBase, $redirectBase);

    Response::success([
        'source_id' => $source['id'],
        'checkout_url' => $source['checkout_url']
    ], 'Payment source created');

} catch (Exception $e) {
    error_log('paymongo_create_source.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
