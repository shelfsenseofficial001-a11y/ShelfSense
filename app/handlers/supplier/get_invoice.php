<?php
// app/handlers/supplier/get_invoice.php

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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    Response::error('Invalid invoice ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = Auth::userId();

    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    $stmt = $db->prepare("
        SELECT si.*, s.company_name as supplier_name, r.requisition_number, r.id as requisition_id,
               u.first_name, u.last_name
        FROM supplier_invoices si
        JOIN suppliers s ON si.supplier_id = s.id
        JOIN store_requisitions r ON si.requisition_id = r.id
        JOIN users u ON r.created_by = u.user_id
        WHERE si.id = ? AND si.supplier_id = ?
    ");
    $stmt->execute([$id, $supplierId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        Response::notFound('Invoice not found or not assigned to you');
    }

    $stmt = $db->prepare("
        SELECT ri.quantity, ri.unit_price, ri.total, sp.name as product_name
        FROM store_requisition_items ri
        JOIN supplier_products sp ON ri.supplier_product_id = sp.id
        WHERE ri.requisition_id = ?
        ORDER BY ri.created_at
    ");
    $stmt->execute([$invoice['requisition_id']]);
    $invoice['items'] = $stmt->fetchAll();

    // Real payment timeline data — only events with actual timestamps
    $stmt = $db->prepare("
        SELECT id, status, requested_at, approved_at, rejection_reason
        FROM payment_requests
        WHERE requisition_id = ?
        ORDER BY requested_at DESC
        LIMIT 1
    ");
    $stmt->execute([$invoice['requisition_id']]);
    $invoice['payment_request'] = $stmt->fetch() ?: null;

    Response::success([
        'invoice' => $invoice
    ], 'Invoice details fetched successfully');

} catch (Exception $e) {
    error_log('get_invoice.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
