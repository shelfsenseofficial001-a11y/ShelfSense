<?php
// app/handlers/finance/staff/invoices/resolve_variance.php
// Resolves a price_hold/quantity_hold invoice one of two ways. Payment
// already happened when the PO was confirmed, so neither action moves any
// money -- this is purely closing out the post-delivery reconciliation record:
//  - action=override: Finance Staff marks it reconciled despite the variance,
//    with a mandatory note (acts as the justification record).
//  - action=adjust_po_price: corrects the PO line's recorded unit price (e.g.
//    a data-entry error caught at reconciliation) and re-runs the 3-way match.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Invoice.php';
require_once __DIR__ . '/../../../../models/InvoiceMatcher.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\InvoiceMatcher;
use App\Models\PurchaseOrder;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$invoiceId = isset($input['invoice_id']) ? intval($input['invoice_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$note = isset($input['note']) ? trim($input['note']) : '';

if ($invoiceId <= 0 || !in_array($action, ['override', 'adjust_po_price'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'override' && $note === '') {
    Response::error('A justification note is required to override a hold', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $invoiceModel = new Invoice();
    $invoice = $invoiceModel->getWithItems($invoiceId);
    if (!$invoice) {
        Response::notFound('Invoice not found');
    }
    if (!in_array($invoice['match_status'], ['price_hold', 'quantity_hold'], true)) {
        Response::error('This invoice is not on hold. Current status: ' . $invoice['match_status'], 400);
    }

    if ($action === 'override') {
        $invoiceModel->setMatchStatus($invoiceId, 'reconciled');
        $stmt = $db->prepare("UPDATE invoices SET notes = CONCAT(COALESCE(notes,''), '\n[OVERRIDE] ', ?) WHERE id = ?");
        $stmt->execute([$note, $invoiceId]);

        Response::success(['invoice_id' => $invoiceId, 'match_status' => 'reconciled'], 'Hold overridden. Invoice marked reconciled.');
    } else {
        $poItemId = isset($input['po_item_id']) ? intval($input['po_item_id']) : 0;
        $newPrice = isset($input['new_unit_price']) ? floatval($input['new_unit_price']) : -1;
        if ($poItemId <= 0 || $newPrice < 0) {
            Response::error('A valid po_item_id and new_unit_price are required', 400);
        }

        $poModel = new PurchaseOrder();
        $stmt = $db->prepare("SELECT quantity FROM purchase_order_items WHERE id = ? AND po_id = ?");
        $stmt->execute([$poItemId, $invoice['po_id']]);
        $poItem = $stmt->fetch();
        if (!$poItem) {
            Response::error('That line item does not belong to this invoice\'s purchase order', 400);
        }
        $poModel->updateItemQuantityPrice($poItemId, $poItem['quantity'], $newPrice);
        $poModel->recalculateTotals($invoice['po_id']);

        $matcher = new InvoiceMatcher();
        $matchStatus = $matcher->match($invoiceId);

        Response::success(['invoice_id' => $invoiceId, 'match_status' => $matchStatus], 'PO price updated. Invoice re-matched: ' . $matchStatus);
    }
} catch (Exception $e) {
    error_log('resolve_variance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
