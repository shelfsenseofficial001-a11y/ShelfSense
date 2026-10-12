<?php
// app/handlers/finance/staff/invoices/resolve_variance.php
// Resolves a price_hold/quantity_hold invoice by correcting the PO line's
// recorded unit price (e.g. a data-entry error caught at reconciliation)
// and re-running the 3-way match. Payment already happened when the PO was
// confirmed, so this never moves any money -- it's purely closing out the
// post-delivery reconciliation record. A mismatch Finance Staff can't fix
// this way (e.g. a real quantity discrepancy) goes through Return to
// Supplier instead (see return_to_supplier.php) -- there's no "force it
// reconciled anyway" escape hatch anymore.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Invoice.php';
require_once __DIR__ . '/../../../../models/InvoiceMatcher.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

function notifyReadyForPayment($db, $invoice)
{
    $stmt = $db->prepare("SELECT po_number FROM purchase_orders WHERE id = ?");
    $stmt->execute([$invoice['po_id']]);
    $po = $stmt->fetch();
    if (!$po) {
        return;
    }
    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification($u['user_id'], 'ready_for_payment_request', "Invoice {$invoice['invoice_number']} for PO {$po['po_number']} is now reconciled and ready for a payment request.", "?page=finance_staff_payment_requests");
    }
}

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

if ($invoiceId <= 0 || $action !== 'adjust_po_price') {
    Response::error('Invalid request', 400);
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
    if ($matchStatus === 'reconciled') {
        notifyReadyForPayment($db, $invoice);
    }

    Response::success(['invoice_id' => $invoiceId, 'match_status' => $matchStatus], 'PO price updated. Invoice re-matched: ' . $matchStatus);
} catch (Exception $e) {
    error_log('resolve_variance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
