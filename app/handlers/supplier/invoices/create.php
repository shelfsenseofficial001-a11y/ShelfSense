<?php
// app/handlers/supplier/invoices/create.php
// multipart/form-data: po_id, invoice_date, due_date, items (JSON string),
// notes, and an optional 'file' upload (the invoice document).

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Invoice.php';
require_once __DIR__ . '/../../../models/InvoiceMatcher.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../models/PoDiscrepancy.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\InvoiceMatcher;
use App\Models\PoEvent;
use App\Models\PoDiscrepancy;

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
    $supplierId = Auth::supplierId();
    if (!$supplierId) {
        Response::forbidden('No supplier profile linked to this account.');
    }

    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? AND supplier_id = ?");
    $stmt->execute([$poId, $supplierId]);
    $po = $stmt->fetch();
    if (!$po) {
        Response::notFound('Purchase order not found for this supplier.');
    }
    // One invoice per PO (1:1) -- it always represents the complete, final
    // bill, so invoicing stays locked until the PO is fully, cleanly
    // received: not just partially shipped, and nothing still open on an
    // RMA/Backorder.
    $discModel = new PoDiscrepancy();
    if ($po['status'] !== 'received') {
        Response::error('This purchase order is not fully received yet, or still has an unresolved RMA/Backorder. Current status: ' . $po['status'], 400);
    }
    if ($discModel->hasBlockingDiscrepancy($poId)) {
        Response::error('This purchase order has unresolved discrepancies (missing items need a backorder, damaged items need an RMA). Resolve them in the Purchase Order detail before invoicing.', 400);
    }

    // One invoice per PO (1:1) -- but a 'rejected' invoice (Returned to
    // Supplier by Finance Staff) is revised in place here rather than
    // blocked, so the Supplier actually has a way to fix and resubmit it.
    $stmt = $db->prepare("SELECT id, match_status, file_path FROM invoices WHERE po_id = ?");
    $stmt->execute([$poId]);
    $existingInvoice = $stmt->fetch();
    $isResubmission = false;
    if ($existingInvoice) {
        if ($existingInvoice['match_status'] !== 'rejected') {
            Response::error('This purchase order already has an invoice. Only one invoice is allowed per purchase order.', 400);
        }
        $isResubmission = true;
    }

    // Billed quantity is NOT hard-capped here against the good-received
    // ceiling -- that's what InvoiceMatcher's 3-way match is for. A supplier
    // over-billing (honest mistake or otherwise) creates the invoice and
    // lands it in quantity_hold for Finance to review, exactly like a price
    // mismatch does. Only basic input sanity is enforced at this boundary.
    foreach ($items as $item) {
        $billedQty = intval($item['billed_quantity'] ?? -1);
        $billedPrice = floatval($item['billed_unit_price'] ?? -1);
        if ($billedQty < 0 || $billedPrice < 0) {
            Response::error('Billed quantity and price must be non-negative.', 400);
        }
    }

    $filePath = $isResubmission ? ($existingInvoice['file_path'] ?? null) : null;
    if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadFile($_FILES['file'], __DIR__ . '/../../../../public/uploads/invoices', ['pdf', 'jpg', 'jpeg', 'png']);
        if (!$uploadResult['success']) {
            Response::error($uploadResult['message'], 400);
        }
        $filePath = 'uploads/invoices/' . $uploadResult['filename'];
    }

    $db->beginTransaction();

    $invoiceModel = new Invoice();

    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += floatval($item['billed_quantity'] ?? 0) * floatval($item['billed_unit_price'] ?? 0);
    }

    if ($isResubmission) {
        $invoiceId = $existingInvoice['id'];
        $invoiceModel->prepareForResubmission($invoiceId, [
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'file_path' => $filePath,
            'notes' => $notes,
        ]);
        $invoiceModel->deleteItems($invoiceId);
        $stmt = $db->prepare("SELECT invoice_number FROM invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoiceNumber = $stmt->fetchColumn();
    } else {
        $invoiceNumber = $invoiceModel->generateNumber();
        $invoiceId = $invoiceModel->create([
            'invoice_number' => $invoiceNumber,
            'po_id' => $poId,
            'supplier_id' => $supplierId,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'file_path' => $filePath,
            'notes' => $notes,
        ]);
    }

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

    (new PoEvent())->log(
        $poId,
        'invoice_submitted',
        ($isResubmission ? 'Supplier resubmitted' : 'Supplier submitted') . " invoice {$invoiceNumber}. Reconciliation result: {$matchStatus}.",
        'not_store_manager',
        Auth::userId()
    );

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
    if ($e instanceof PDOException && (int)$e->getCode() === 23000) {
        Response::error('This purchase order already has an invoice. Only one invoice is allowed per purchase order.', 400);
    }
    error_log('invoices/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
