<?php
// app/handlers/pos/paymongo_source_status.php
// Polled by the register while showing the GCash/PayMaya QR, waiting for
// the customer to approve (or fail/cancel) on their own phone.

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

$sourceId = isset($_GET['source_id']) ? (string)$_GET['source_id'] : '';
if ($sourceId === '') {
    Response::error('Missing source_id.', 400);
}

try {
    $source = PayMongo::getSource($sourceId);
    Response::success(['status' => $source['status']]);
} catch (Exception $e) {
    error_log('paymongo_source_status.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
