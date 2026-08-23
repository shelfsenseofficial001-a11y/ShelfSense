<?php
// app/handlers/supplier/get_dashboard_stats.php

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

try {
    $db = Database::getInstance()->getConnection();
    $userId = Auth::userId();

    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    // Requisition counts, grouped the same way the Requisitions tabs group them
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_requisitions,
            SUM(CASE WHEN status IN ('pending_supplier','sent_to_supplier') THEN 1 ELSE 0 END) as pending_requisitions,
            SUM(CASE WHEN status = 'supplier_processed' THEN 1 ELSE 0 END) as invoiced_requisitions,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as ready_to_ship
        FROM store_requisitions
        WHERE supplier_id = ?
    ");
    $stmt->execute([$supplierId]);
    $stats = $stmt->fetch();

    // This month's revenue: real invoice totals, invoiced this calendar month
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM supplier_invoices
        WHERE supplier_id = ? AND DATE_FORMAT(invoice_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $stmt->execute([$supplierId]);
    $revenue = $stmt->fetch();

    // Activity in the last 30 days: real counts by real timestamps
    $stmt = $db->prepare("
        SELECT
            SUM(CASE WHEN r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as received_30d,
            SUM(CASE WHEN r.status <> 'pending_supplier' AND r.status <> 'sent_to_supplier' AND r.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as processed_30d,
            SUM(CASE WHEN r.status IN ('shipped','completed','partial_received') AND r.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as shipped_30d
        FROM store_requisitions r
        WHERE r.supplier_id = ?
    ");
    $stmt->execute([$supplierId]);
    $activity = $stmt->fetch();

    // Recent requisitions needing action (pending), with item count
    $stmt = $db->prepare("
        SELECT
            r.id, r.requisition_number, r.status, r.order_date, r.expected_delivery, r.total,
            u.first_name, u.last_name,
            (SELECT COUNT(*) FROM store_requisition_items ri WHERE ri.requisition_id = r.id) as item_count
        FROM store_requisitions r
        JOIN users u ON r.created_by = u.user_id
        WHERE r.supplier_id = ? AND r.status IN ('pending_supplier', 'sent_to_supplier')
        ORDER BY r.created_at ASC
        LIMIT 5
    ");
    $stmt->execute([$supplierId]);
    $pendingRequisitions = $stmt->fetchAll();

    Response::success([
        'stats' => [
            'total_requisitions' => (int)($stats['total_requisitions'] ?? 0),
            'pending_requisitions' => (int)($stats['pending_requisitions'] ?? 0),
            'invoiced_requisitions' => (int)($stats['invoiced_requisitions'] ?? 0),
            'ready_to_ship' => (int)($stats['ready_to_ship'] ?? 0),
            'month_revenue' => (float)($revenue['revenue'] ?? 0)
        ],
        'activity_30d' => [
            'received' => (int)($activity['received_30d'] ?? 0),
            'processed' => (int)($activity['processed_30d'] ?? 0),
            'shipped' => (int)($activity['shipped_30d'] ?? 0)
        ],
        'pending_requisitions' => $pendingRequisitions
    ], 'Supplier dashboard stats fetched');

} catch (Exception $e) {
    error_log('get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
