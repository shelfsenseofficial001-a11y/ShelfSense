<?php
// app/handlers/store_manager/supplier_ratings/create.php
// Stage 9, Close & Review: once a PO is fully paid, the Store Manager rates
// the supplier's performance on this order (on-time delivery, fill rate,
// quality) -- this is what actually closes the PO (status -> 'closed', the
// one terminal status that previously had no code path reaching it).

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/PoEvent.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
$onTime = !empty($input['on_time']);
$fillRate = isset($input['fill_rate_percent']) ? floatval($input['fill_rate_percent']) : 100;
$quality = isset($input['quality_rating']) ? intval($input['quality_rating']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($poId <= 0) {
    Response::error('Invalid purchase order', 400);
}
if ($quality < 1 || $quality > 5) {
    Response::error('Quality rating must be between 1 and 5', 400);
}
if ($fillRate < 0 || $fillRate > 100) {
    Response::error('Fill rate must be between 0 and 100', 400);
}
if (strlen($notes) > 500) {
    Response::error('Notes cannot exceed 500 characters', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$poId]);
    $po = $stmt->fetch();

    if (!$po) {
        $db->rollBack();
        Response::error('Purchase order not found', 404);
    }
    if ($po['status'] !== 'paid') {
        $db->rollBack();
        Response::error('This purchase order can only be rated once it has been paid. Current status: ' . $po['status'], 400);
    }

    $stmt = $db->prepare("SELECT id FROM supplier_ratings WHERE po_id = ?");
    $stmt->execute([$poId]);
    if ($stmt->fetch()) {
        $db->rollBack();
        Response::error('This purchase order has already been rated.', 400);
    }

    $stmt = $db->prepare("
        INSERT INTO supplier_ratings (po_id, supplier_id, rated_by, on_time, fill_rate_percent, quality_rating, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$poId, $po['supplier_id'], Auth::userId(), $onTime ? 1 : 0, $fillRate, $quality, $notes !== '' ? $notes : null]);

    $poModel = new PurchaseOrder();
    $poModel->updateStatus($poId, 'closed');

    (new PoEvent())->log($poId, 'po_closed', "Purchase Order {$po['po_number']} closed. Supplier rated " . str_repeat('★', $quality) . str_repeat('☆', 5 - $quality) . ($onTime ? ', on-time' : ', late') . ", {$fillRate}% fill rate." . ($notes !== '' ? " Notes: $notes" : ''), 'all', Auth::userId());

    $db->commit();

    Response::success(['po_id' => $poId, 'status' => 'closed'], 'Rating submitted. Purchase Order closed.');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('supplier_ratings/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
