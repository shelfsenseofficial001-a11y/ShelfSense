<?php
// app/handlers/store_manager/get_dashboard_stats.php

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

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

try {
    $db = Database::getInstance()->getConnection();

    // Requisition counts grouped the same way the Requisitions tabs group them
    $stmt = $db->query("
        SELECT
            COUNT(*) as total_requisitions,
            SUM(CASE WHEN status IN ('draft','pending_supplier','sent_to_supplier') THEN 1 ELSE 0 END) as pending_supplier,
            SUM(CASE WHEN status IN ('supplier_processed','awaiting_finance_staff','awaiting_finance','finance_approved') THEN 1 ELSE 0 END) as awaiting_finance
        FROM store_requisitions
    ");
    $stats = $stmt->fetch();

    // Low stock products (strictly: above 0, at or below reorder level — matches the inventory badge rules)
    $stmt = $db->query("
        SELECT COUNT(*) as low_stock_count
        FROM products
        WHERE is_active = 1 AND stock_quantity > 0 AND stock_quantity <= reorder_level
    ");
    $lowStock = $stmt->fetch();

    // Requisition activity in the last 30 days.
    // "created" uses created_at (exact). "sent" and "completed" use updated_at as the closest
    // real timestamp available, since the schema has no dedicated per-transition timestamps
    // (see final report for the proposed audit-log schema that would make this exact).
    $stmt = $db->query("
        SELECT
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as created_30d,
            SUM(CASE WHEN status NOT IN ('draft','pending_supplier') AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as sent_30d,
            SUM(CASE WHEN status = 'completed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as completed_30d
        FROM store_requisitions
    ");
    $activity = $stmt->fetch();

    // Recent requisitions (last 5) with item count and, if available, the actual receipt date
    $stmt = $db->query("
        SELECT
            r.id, r.requisition_number, r.status, r.order_date, r.expected_delivery, r.total, r.created_at,
            s.company_name,
            (SELECT COUNT(*) FROM store_requisition_items ri WHERE ri.requisition_id = r.id) as item_count,
            (SELECT MAX(gr.receipt_date) FROM goods_receipts gr WHERE gr.requisition_id = r.id) as actual_delivery_date
        FROM store_requisitions r
        JOIN suppliers s ON r.supplier_id = s.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $recentRequisitions = $stmt->fetchAll();

    Response::success([
        'stats' => [
            'total_requisitions' => (int)($stats['total_requisitions'] ?? 0),
            'pending_supplier' => (int)($stats['pending_supplier'] ?? 0),
            'awaiting_finance' => (int)($stats['awaiting_finance'] ?? 0),
            'low_stock_count' => (int)($lowStock['low_stock_count'] ?? 0)
        ],
        'activity_30d' => [
            'created' => (int)($activity['created_30d'] ?? 0),
            'sent' => (int)($activity['sent_30d'] ?? 0),
            'completed' => (int)($activity['completed_30d'] ?? 0)
        ],
        'recent_requisitions' => $recentRequisitions
    ], 'Store Manager dashboard stats fetched');

} catch (Exception $e) {
    error_log('get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
