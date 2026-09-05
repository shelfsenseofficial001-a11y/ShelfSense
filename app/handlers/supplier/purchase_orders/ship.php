<?php
// app/handlers/supplier/purchase_orders/ship.php
// Supplier marks a paid PO as shipped -- only available once payment has
// actually been approved and sent.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : null;

if ($poId <= 0) {
    Response::error('Invalid purchase order id', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([Auth::userId()]);
    $supplier = $stmt->fetch();
    if (!$supplier) {
        Response::forbidden('No supplier profile linked to this account.');
    }

    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$poId, $supplier['id']]);
    $po = $stmt->fetch();
    if (!$po) {
        Response::notFound('Purchase order not found for this supplier.');
    }
    if ($po['status'] !== 'paid') {
        Response::error('This purchase order is not paid yet. Current status: ' . $po['status'], 400);
    }

    $poModel = new PurchaseOrder();
    $poModel->updateStatus($poId, 'shipped');

    $eventModel = new PoEvent();
    $eventModel->log($poId, 'shipped', 'Supplier marked the order as shipped.' . ($notes ? " Note: $notes" : ''), 'all', Auth::userId());

    $stmt = $db->prepare("SELECT requested_by, requisition_number FROM requisitions WHERE id = ?");
    $stmt->execute([$po['requisition_id']]);
    $req = $stmt->fetch();
    if ($req) {
        createNotification($req['requested_by'], 'po_shipped', "PO {$po['po_number']} has been shipped by the supplier.", "?page=store_manager_requisitions");
    }

    Response::success(['po_id' => $poId, 'status' => 'shipped'], 'Marked as shipped.');
} catch (Exception $e) {
    error_log('purchase_orders/ship.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
