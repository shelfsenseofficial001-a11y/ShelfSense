<?php
// app/handlers/supplier/create_invoice.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
// Invoice date is always "today" per the workflow — the frontend field is readonly,
// but a readonly field is only a UI hint, not a security boundary, so any client-submitted
// invoice_date is ignored here and the server's own date is used instead.
$invoiceDate = date('Y-m-d');
$dueDate = isset($input['due_date']) ? trim($input['due_date']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($requisitionId <= 0) {
    Response::error('Invalid requisition ID', 400);
}
if (empty($dueDate)) {
    Response::error('Due date is required', 400);
}
$dueDateError = validateExpectedDeliveryDate($dueDate);
if ($dueDateError) {
    // Reusing the shared date-range validator (same today..+1 year rule as Store Manager's
    // expected delivery date) — its messages are worded for that field, so relabel for due_date.
    $dueDateError = str_replace('Expected delivery date', 'Due date', $dueDateError);
    Response::error($dueDateError, 400, ['due_date' => $dueDateError]);
}
if (strlen($notes) > 500) {
    Response::error('Notes cannot exceed 500 characters', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = Auth::userId();

    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    $stmt = $db->prepare("
        SELECT r.*, s.id as supplier_id, s.company_name, u.user_id as store_manager_id, u.first_name, u.last_name
        FROM store_requisitions r
        JOIN suppliers s ON r.supplier_id = s.id
        JOIN users u ON r.created_by = u.user_id
        WHERE r.id = ? AND r.supplier_id = ?
    ");
    $stmt->execute([$requisitionId, $supplierId]);
    $req = $stmt->fetch();

    if (!$req) {
        Response::notFound('Requisition not found or not assigned to you');
    }

    if (!in_array($req['status'], ['pending_supplier', 'sent_to_supplier'])) {
        Response::error('Requisition is not ready for invoicing. Current status: ' . $req['status']);
    }

    $year = date('Y');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM supplier_invoices WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $count = $stmt->fetch()['count'] + 1;
    $invoiceNumber = 'INV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("
        SELECT ri.*, sp.name as supplier_product_name
        FROM store_requisition_items ri
        JOIN supplier_products sp ON ri.supplier_product_id = sp.id
        WHERE ri.requisition_id = ?
    ");
    $stmt->execute([$requisitionId]);
    $items = $stmt->fetchAll();

    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['unit_price'] * $item['quantity'];
    }

    $stmt = $db->prepare("
        INSERT INTO supplier_invoices (
            invoice_number, requisition_id, supplier_id, invoice_date, due_date,
            subtotal, tax, total, notes, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $invoiceNumber,
        $requisitionId,
        $supplierId,
        $invoiceDate,
        $dueDate,
        $subtotal,
        0,
        $subtotal,
        $notes
    ]);
    $invoiceId = $db->lastInsertId();

    $stmt = $db->prepare("
        UPDATE store_requisitions SET status = 'supplier_processed', updated_at = NOW() WHERE id = ?
    ");
    $stmt->execute([$requisitionId]);

    createNotification(
        $req['store_manager_id'],
        'invoice_received',
        "Supplier has sent invoice for requisition #{$req['requisition_number']}. Please review and forward to Finance Staff.",
        "?page=store_manager_requisitions"
    );

    $stmt = $db->prepare("
        SELECT si.*, s.company_name
        FROM supplier_invoices si
        JOIN suppliers s ON si.supplier_id = s.id
        WHERE si.id = ?
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();

    Response::success([
        'invoice' => $invoice,
        'requisition_status' => 'supplier_processed'
    ], 'Invoice created successfully. Store Manager has been notified.');

} catch (Exception $e) {
    error_log('create_invoice.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}