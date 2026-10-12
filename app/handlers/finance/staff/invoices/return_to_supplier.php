<?php
// app/handlers/finance/staff/invoices/return_to_supplier.php
// Sends a price_hold/quantity_hold invoice back to the Supplier with a
// reason instead of Finance Staff overriding it themselves. Since
// invoices.po_id is UNIQUE, the invoice row isn't deleted -- it's marked
// 'rejected' and the Supplier revises it in place when they resubmit
// (see supplier/invoices/create.php's resubmission branch).

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Invoice.php';
require_once __DIR__ . '/../../../../models/PoEvent.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$invoiceId = isset($input['invoice_id']) ? intval($input['invoice_id']) : 0;
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($invoiceId <= 0) {
    Response::error('Invalid request', 400);
}
if ($reason === '') {
    Response::error('A reason is required so the Supplier knows what to fix.', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $invoiceModel = new Invoice();
    $invoice = $invoiceModel->getById($invoiceId);
    if (!$invoice) {
        Response::notFound('Invoice not found');
    }
    if (!in_array($invoice['match_status'], ['price_hold', 'quantity_hold'], true)) {
        Response::error('This invoice is not on hold. Current status: ' . $invoice['match_status'], 400);
    }

    $invoiceModel->setRejected($invoiceId, $reason);

    (new PoEvent())->log(
        $invoice['po_id'],
        'invoice_returned',
        "Finance Staff returned invoice {$invoice['invoice_number']} to the Supplier. Reason: $reason",
        'all',
        Auth::userId()
    );

    $stmt = $db->prepare("SELECT user_id FROM suppliers WHERE id = ?");
    $stmt->execute([$invoice['supplier_id']]);
    $supplierUserId = $stmt->fetchColumn();
    if ($supplierUserId) {
        createNotification(
            $supplierUserId,
            'invoice_returned',
            "Your invoice {$invoice['invoice_number']} for PO {$invoice['po_number']} was returned. Reason: $reason",
            "?page=supplier_invoices"
        );
    }

    Response::success(['invoice_id' => $invoiceId, 'match_status' => 'rejected'], 'Invoice returned to the Supplier.');
} catch (Exception $e) {
    error_log('return_to_supplier.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
