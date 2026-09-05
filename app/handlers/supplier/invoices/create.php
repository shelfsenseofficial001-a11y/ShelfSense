<?php
// app/handlers/supplier/invoices/create.php
// multipart/form-data: po_id, invoice_date, due_date, items (JSON string),
// notes, and an optional 'file' upload (the invoice document).

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/Invoice.php';
require_once __DIR__ . '/../../../models/InvoiceMatcher.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Models\InvoiceMatcher;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$poId = isset($_POST['po_id']) ? intval($_POST['po_id']) : 0;
$invoiceDate = isset($_POST['invoice_date']) ? trim($_POST['invoice_date']) : '';
$dueDate = isset($_POST['due_date']) ? trim($_POST['due_date']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

if ($poId <= 0 || $invoiceDate === '' || $dueDate === '' || empty($items) || !is_array($items)) {
    Response::error('Purchase order, invoice date, due date, and at least one item are required', 400);
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
    // Payment already happened at PO-confirmation time (pay-before-delivery) --
    // this invoice is a post-delivery reconciliation record, so there has to be
    // a logged Goods Receipt to reconcile against before one can be submitted.
    if (!in_array($po['status'], ['partially_received', 'received'], true)) {
        Response::error('This purchase order has no logged goods receipt yet. Submit the invoice after the Store Manager receives at least part of the order. Current status: ' . $po['status'], 400);
    }

    $filePath = null;
    if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadFile($_FILES['file'], __DIR__ . '/../../../../public/uploads/invoices', ['pdf', 'jpg', 'jpeg', 'png']);
        if (!$uploadResult['success']) {
            Response::error($uploadResult['message'], 400);
        }
        $filePath = 'uploads/invoices/' . $uploadResult['filename'];
    }

    $db->beginTransaction();

    $invoiceModel = new Invoice();
    $invoiceNumber = $invoiceModel->generateNumber();

    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += floatval($item['billed_quantity'] ?? 0) * floatval($item['billed_unit_price'] ?? 0);
    }

    $invoiceId = $invoiceModel->create([
        'invoice_number' => $invoiceNumber,
        'po_id' => $poId,
        'supplier_id' => $supplier['id'],
        'invoice_date' => $invoiceDate,
        'due_date' => $dueDate,
        'subtotal' => $subtotal,
        'total' => $subtotal,
        'file_path' => $filePath,
        'notes' => $notes,
    ]);

    foreach ($items as $item) {
        $invoiceModel->addItem(
            $invoiceId,
            intval($item['po_item_id']),
            intval($item['billed_quantity']),
            floatval($item['billed_unit_price'])
        );
    }

    $matcher = new InvoiceMatcher();
    $matchStatus = $matcher->match($invoiceId);

    (new PoEvent())->log($poId, 'invoice_submitted', "Supplier submitted invoice {$invoiceNumber}. Reconciliation result: {$matchStatus}.", 'not_store_manager', Auth::userId());

    $db->commit();

    $stmt = $db->prepare("SELECT requested_by, requisition_number FROM requisitions WHERE id = ?");
    $stmt->execute([$po['requisition_id']]);
    $req = $stmt->fetch();

    if ($matchStatus === 'reconciled') {
        foreach (getUsersByRole('finance_staff') as $u) {
            createNotification($u['user_id'], 'invoice_reconciled', "Invoice {$invoiceNumber} for PO {$po['po_number']} matched cleanly and is reconciled.", "?page=finance_staff_payment_requests");
        }
    } else {
        foreach (getUsersByRole('finance_staff') as $u) {
            createNotification($u['user_id'], 'invoice_hold', "Invoice {$invoiceNumber} for PO {$po['po_number']} needs review ({$matchStatus}).", "?page=finance_staff_payment_requests");
        }
    }

    Response::success([
        'invoice_id' => $invoiceId,
        'invoice_number' => $invoiceNumber,
        'match_status' => $matchStatus,
    ], 'Invoice submitted. Match status: ' . $matchStatus);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('invoices/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
