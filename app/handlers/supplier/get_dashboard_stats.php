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

    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_requisitions,
            SUM(CASE WHEN status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_requisitions,
            SUM(CASE WHEN status IN ('partially_received','received') THEN 1 ELSE 0 END) as invoiced_requisitions,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as ready_to_ship
        FROM purchase_orders
        WHERE supplier_id = ?
    ");
    $stmt->execute([$supplierId]);
    $stats = $stmt->fetch();

    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM invoices
        WHERE supplier_id = ? AND DATE_FORMAT(invoice_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $stmt->execute([$supplierId]);
    $revenue = $stmt->fetch();

    $stmt = $db->prepare("
        SELECT
            SUM(CASE WHEN po.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as received_30d,
            SUM(CASE WHEN po.status <> 'pending_confirmation' AND po.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as processed_30d,
            SUM(CASE WHEN po.status IN ('shipped','partially_received','received','paid') AND po.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as shipped_30d
        FROM purchase_orders po
        WHERE po.supplier_id = ?
    ");
    $stmt->execute([$supplierId]);
    $activity = $stmt->fetch();

    $stmt = $db->prepare("
        SELECT
            po.id, po.po_number as requisition_number, po.status, po.order_date, po.expected_delivery_date as expected_delivery, po.total,
            CONCAT(u.first_name, ' ', u.last_name) as first_name,
            (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.po_id = po.id) as item_count
        FROM purchase_orders po
        JOIN users u ON po.created_by = u.user_id
        WHERE po.supplier_id = ? AND po.status = 'pending_confirmation'
        ORDER BY po.dispatched_at ASC
        LIMIT 5
    ");
    $stmt->execute([$supplierId]);
    $pendingRequisitions = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT po.id, po.po_number, po.status, po.total
        FROM purchase_orders po
        WHERE po.supplier_id = ? AND po.status = 'confirmed'
        ORDER BY po.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$supplierId]);
    $readyToShip = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT inv.id, inv.invoice_number, inv.match_status, inv.total, po.po_number
        FROM invoices inv
        JOIN purchase_orders po ON po.id = inv.po_id
        WHERE inv.supplier_id = ?
        ORDER BY inv.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$supplierId]);
    $recentInvoices = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT id, name, price
        FROM supplier_products
        WHERE supplier_id = ? AND is_active = 1
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$supplierId]);
    $activeProducts = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT id, po_number, status, total
        FROM purchase_orders
        WHERE supplier_id = ?
        ORDER BY order_date DESC
        LIMIT 5
    ");
    $stmt->execute([$supplierId]);
    $recentPos = $stmt->fetchAll();

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
        'pending_requisitions' => $pendingRequisitions,
        'ready_to_ship_pos' => $readyToShip,
        'recent_invoices' => $recentInvoices,
        'active_products' => $activeProducts,
        'recent_pos' => $recentPos
    ], 'Supplier dashboard stats fetched');

} catch (Exception $e) {
    error_log('get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
