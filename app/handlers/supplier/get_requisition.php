<?php
// app/handlers/supplier/get_requisition.php

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
    Response::error('Invalid requisition ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    // Get supplier ID from the logged-in user
    $userId = Auth::userId();
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    // Fetch requisition details (scoped to this supplier — authorization boundary)
    $stmt = $db->prepare("
        SELECT r.*, s.company_name, u.first_name, u.last_name
        FROM store_requisitions r
        JOIN suppliers s ON r.supplier_id = s.id
        JOIN users u ON r.created_by = u.user_id
        WHERE r.id = ? AND r.supplier_id = ?
    ");
    $stmt->execute([$id, $supplierId]);
    $requisition = $stmt->fetch();

    if (!$requisition) {
        Response::notFound('Requisition not found or not assigned to you');
    }

    // Fetch items with store and supplier product details
    $stmt = $db->prepare("
        SELECT
            ri.*,
            p.id as store_product_id,
            p.name as store_product_name,
            p.barcode,
            sp.id as supplier_product_id,
            sp.name as supplier_product_name,
            sp.price as supplier_price
        FROM store_requisition_items ri
        JOIN products p ON ri.store_product_id = p.id
        JOIN supplier_products sp ON ri.supplier_product_id = sp.id
        WHERE ri.requisition_id = ?
        ORDER BY ri.created_at
    ");
    $stmt->execute([$id]);
    $requisition['items'] = $stmt->fetchAll();

    // Invoice this supplier created for this requisition, if any
    $stmt = $db->prepare("SELECT * FROM supplier_invoices WHERE requisition_id = ? AND supplier_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$id, $supplierId]);
    $requisition['invoice'] = $stmt->fetch() ?: null;

    // Payment request tied to that invoice (real timeline data, if it exists)
    $requisition['payment_request'] = null;
    if ($requisition['invoice']) {
        $stmt = $db->prepare("
            SELECT id, status, requested_at, approved_at, rejection_reason
            FROM payment_requests
            WHERE requisition_id = ?
            ORDER BY requested_at DESC
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $requisition['payment_request'] = $stmt->fetch() ?: null;
    }

    // Goods receipt(s), if the store manager has recorded receiving goods
    $stmt = $db->prepare("SELECT id, receipt_date, status, created_at FROM goods_receipts WHERE requisition_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$id]);
    $requisition['goods_receipt'] = $stmt->fetch() ?: null;

    Response::success([
        'requisition' => $requisition
    ], 'Requisition details fetched successfully');

} catch (Exception $e) {
    error_log('get_requisition.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
