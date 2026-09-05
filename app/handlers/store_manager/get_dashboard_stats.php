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

    // "Pending supplier" = a PO that's still waiting on the supplier's side
    // (dispatched but not yet confirmed, or under counter-negotiation).
    $stmt = $db->query("
        SELECT
            SUM(CASE WHEN status IN ('pending_dispatch','pending_confirmation','supplier_counter_proposed') THEN 1 ELSE 0 END) as pending_supplier,
            SUM(CASE WHEN status IN ('pending_dispatch','pending_confirmation','supplier_counter_proposed') AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as pending_supplier_this_week
        FROM purchase_orders
    ");
    $poStats = $stmt->fetch();

    $stmt = $db->query("
        SELECT
            COUNT(*) as total_requisitions,
            SUM(CASE WHEN status IN ('pending_budget_check','pending_finance_head') THEN 1 ELSE 0 END) as awaiting_finance,
            SUM(CASE WHEN status IN ('pending_budget_check','pending_finance_head') AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as awaiting_finance_this_week,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as created_this_week
        FROM requisitions
    ");
    $reqStats = $stmt->fetch();

    $stmt = $db->query("
        SELECT
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_count,
            COUNT(*) as active_product_count
        FROM products
        WHERE is_active = 1
    ");
    $lowStock = $stmt->fetch();

    $stmt = $db->query("SELECT COUNT(*) as c FROM requisitions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $created30d = (int)$stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM purchase_orders WHERE dispatched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $sent30d = (int)$stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'closed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $completed30d = (int)$stmt->fetch()['c'];

    $stmt = $db->query("
        SELECT
            r.id, r.requisition_number, r.status, r.order_date, r.needed_by_date as expected_delivery, r.subtotal as total, r.created_at,
            s.company_name,
            (SELECT COUNT(*) FROM requisition_items ri WHERE ri.requisition_id = r.id) as item_count,
            (SELECT MAX(gr.receipt_date) FROM purchase_orders po2 JOIN goods_receipts gr ON gr.po_id = po2.id WHERE po2.requisition_id = r.id) as actual_delivery_date
        FROM requisitions r
        JOIN suppliers s ON r.preferred_supplier_id = s.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $recentRequisitions = $stmt->fetchAll();

    $stmt = $db->query("
        SELECT
            SUM(CASE WHEN status IN ('pending_budget_check','pending_finance_head') THEN 1 ELSE 0 END) as in_finance_review,
            SUM(CASE WHEN status IN ('budget_rejected','rejected','cancelled') THEN 1 ELSE 0 END) as rejected
        FROM requisitions
    ");
    $reqBreakdown = $stmt->fetch();

    $stmt = $db->query("
        SELECT
            SUM(CASE WHEN status IN ('pending_dispatch','pending_confirmation','supplier_counter_proposed') THEN 1 ELSE 0 END) as pending_supplier,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as rejected
        FROM purchase_orders
    ");
    $poBreakdown = $stmt->fetch();

    $stmt = $db->prepare("
        SELECT DATE(created_at) as d, COUNT(*) as c
        FROM requisitions
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY DATE(created_at)
    ");
    $stmt->execute();
    $createdByDay = [];
    foreach ($stmt->fetchAll() as $row) {
        $createdByDay[$row['d']] = (int)$row['c'];
    }

    $stmt = $db->prepare("
        SELECT DATE(updated_at) as d, COUNT(*) as c
        FROM purchase_orders
        WHERE status = 'closed' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY DATE(updated_at)
    ");
    $stmt->execute();
    $completedByDay = [];
    foreach ($stmt->fetchAll() as $row) {
        $completedByDay[$row['d']] = (int)$row['c'];
    }

    $dailyTrend = [];
    for ($i = 13; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dailyTrend[] = [
            'date' => $date,
            'created' => $createdByDay[$date] ?? 0,
            'completed' => $completedByDay[$date] ?? 0
        ];
    }

    $stmt = $db->query("SELECT COUNT(*) as c FROM requisitions WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $thisMonthCount = (int)$stmt->fetch()['c'];

    $stmt = $db->query("
        SELECT COUNT(*) as c FROM requisitions
        WHERE created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
          AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')
    ");
    $lastMonthCount = (int)$stmt->fetch()['c'];

    $monthChangePct = $lastMonthCount > 0
        ? round((($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100, 1)
        : ($thisMonthCount > 0 ? 100.0 : 0.0);

    $stmt = $db->query("
        SELECT s.company_name, COUNT(*) as req_count
        FROM requisitions r
        JOIN suppliers s ON r.preferred_supplier_id = s.id
        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY r.preferred_supplier_id
        ORDER BY req_count DESC
        LIMIT 1
    ");
    $topSupplier = $stmt->fetch();

    $stmt = $db->query("
        SELECT name, stock_quantity, reorder_level
        FROM products
        WHERE is_active = 1 AND stock_quantity > 0 AND stock_quantity <= reorder_level
        ORDER BY (stock_quantity / reorder_level) ASC
        LIMIT 1
    ");
    $mostUrgentLowStock = $stmt->fetch();

    Response::success([
        'stats' => [
            'total_requisitions' => (int)($reqStats['total_requisitions'] ?? 0),
            'pending_supplier' => (int)($poStats['pending_supplier'] ?? 0),
            'pending_supplier_this_week' => (int)($poStats['pending_supplier_this_week'] ?? 0),
            'awaiting_finance' => (int)($reqStats['awaiting_finance'] ?? 0),
            'awaiting_finance_this_week' => (int)($reqStats['awaiting_finance_this_week'] ?? 0),
            'low_stock_count' => (int)($lowStock['low_stock_count'] ?? 0),
            'active_product_count' => (int)($lowStock['active_product_count'] ?? 0),
            'created_this_week' => (int)($reqStats['created_this_week'] ?? 0)
        ],
        'activity_30d' => [
            'created' => $created30d,
            'sent' => $sent30d,
            'completed' => $completed30d
        ],
        'status_breakdown' => [
            'pending_supplier' => (int)($poBreakdown['pending_supplier'] ?? 0),
            'in_finance_review' => (int)($reqBreakdown['in_finance_review'] ?? 0),
            'completed' => (int)($poBreakdown['completed'] ?? 0),
            'rejected' => (int)($reqBreakdown['rejected'] ?? 0) + (int)($poBreakdown['rejected'] ?? 0)
        ],
        'daily_trend' => $dailyTrend,
        'recent_requisitions' => $recentRequisitions,
        'insights' => [
            'requisitions_this_month' => $thisMonthCount,
            'requisitions_last_month' => $lastMonthCount,
            'month_change_pct' => $monthChangePct,
            'top_supplier' => $topSupplier ? [
                'name' => $topSupplier['company_name'],
                'count' => (int)$topSupplier['req_count']
            ] : null,
            'most_urgent_low_stock' => $mostUrgentLowStock ? [
                'name' => $mostUrgentLowStock['name'],
                'stock_quantity' => (int)$mostUrgentLowStock['stock_quantity'],
                'reorder_level' => (int)$mostUrgentLowStock['reorder_level']
            ] : null
        ]
    ], 'Store Manager dashboard stats fetched');

} catch (Exception $e) {
    error_log('get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
