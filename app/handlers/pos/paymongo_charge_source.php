<?php
// app/handlers/pos/paymongo_charge_source.php
// Called once the source is chargeable (customer approved on their
// phone) -- this is the step that actually moves money. Returns a
// payment_id the front-end passes as payment_reference to the existing
// api_create_order, same as any other payment method.

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
$sourceId = isset($input['source_id']) ? (string)$input['source_id'] : '';
$amount = isset($input['amount']) ? round((float)$input['amount'], 2) : 0;

if ($sourceId === '' || $amount <= 0) {
    Response::error('Missing source_id or amount.', 400);
}

try {
    $source = PayMongo::getSource($sourceId);
    if ($source['status'] !== 'chargeable') {
        Response::error('This payment has not been approved yet (status: ' . $source['status'] . ').', 400);
    }

    $amountCentavos = (int)round($amount * 100);
    $payment = PayMongo::createPaymentFromSource($sourceId, $amountCentavos, 'ShelfSense POS order');

    Response::success([
        'payment_id' => $payment['id'],
        'status' => $payment['status']
    ], 'Payment charged');

} catch (Exception $e) {
    error_log('paymongo_charge_source.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
