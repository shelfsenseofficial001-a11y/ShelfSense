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

    // Requisition counts grouped the same way the Requisitions tabs group them.
    // "This week" sub-metrics per card use the closest honest timestamp signal
    // available for that bucket: created_at for counts anchored to creation,
    // updated_at (the app's existing approximation for status transitions,
    // per the note below) for counts anchored to reaching a later stage.
    $stmt = $db->query("
        SELECT
            COUNT(*) as total_requisitions,
            SUM(CASE WHEN status IN ('draft','pending_supplier','sent_to_supplier') THEN 1 ELSE 0 END) as pending_supplier,
            SUM(CASE WHEN status IN ('draft','pending_supplier','sent_to_supplier') AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as pending_supplier_this_week,
            SUM(CASE WHEN status IN ('supplier_processed','awaiting_finance_staff','awaiting_finance','finance_approved') THEN 1 ELSE 0 END) as awaiting_finance,
            SUM(CASE WHEN status IN ('supplier_processed','awaiting_finance_staff','awaiting_finance','finance_approved') AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as awaiting_finance_this_week,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as created_this_week
        FROM store_requisitions
    ");
    $stats = $stmt->fetch();

    // Low stock products (strictly: above 0, at or below reorder level — matches the inventory badge rules)
    $stmt = $db->query("
        SELECT
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_count,
            COUNT(*) as active_product_count
        FROM products
        WHERE is_active = 1
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

    // Status breakdown for the "Requisitions by Status" donut -- the raw enum
    // has 8+ granular workflow states, grouped here into 4 slices a donut can
    // actually read: still with the supplier, in finance review, completed,
    // or rejected. Every raw status lands in exactly one bucket (no overlap,
    // no gaps), so the 4 numbers always sum to the total.
    $stmt = $db->query("
        SELECT
            SUM(CASE WHEN status IN ('draft','pending_supplier','sent_to_supplier') THEN 1 ELSE 0 END) as pending_supplier,
            SUM(CASE WHEN status IN ('supplier_processed','awaiting_finance_staff','awaiting_finance','finance_approved','paid','shipped','partial_received') THEN 1 ELSE 0 END) as in_finance_review,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'finance_rejected' THEN 1 ELSE 0 END) as rejected
        FROM store_requisitions
    ");
    $statusBreakdown = $stmt->fetch();

    // Daily trend for the last 14 days -- requisitions created per day, and
    // requisitions that reached 'completed' per day (via updated_at, the
    // same closest-available-timestamp approach used above).
    $stmt = $db->prepare("
        SELECT DATE(created_at) as d, COUNT(*) as c
        FROM store_requisitions
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
        FROM store_requisitions
        WHERE status = 'completed' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
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

    // Business Insights -- only facts that are honestly computable from the
    // data actually stored (no invented revenue/customer figures).
    $stmt = $db->query("SELECT COUNT(*) as c FROM store_requisitions WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $thisMonthCount = (int)$stmt->fetch()['c'];

    $stmt = $db->query("
        SELECT COUNT(*) as c FROM store_requisitions
        WHERE created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
          AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')
    ");
    $lastMonthCount = (int)$stmt->fetch()['c'];

    $monthChangePct = $lastMonthCount > 0
        ? round((($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100, 1)
        : ($thisMonthCount > 0 ? 100.0 : 0.0);

    $stmt = $db->query("
        SELECT s.company_name, COUNT(*) as req_count
        FROM store_requisitions r
        JOIN suppliers s ON r.supplier_id = s.id
        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY r.supplier_id
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
            'total_requisitions' => (int)($stats['total_requisitions'] ?? 0),
            'pending_supplier' => (int)($stats['pending_supplier'] ?? 0),
            'pending_supplier_this_week' => (int)($stats['pending_supplier_this_week'] ?? 0),
            'awaiting_finance' => (int)($stats['awaiting_finance'] ?? 0),
            'awaiting_finance_this_week' => (int)($stats['awaiting_finance_this_week'] ?? 0),
            'low_stock_count' => (int)($lowStock['low_stock_count'] ?? 0),
            'active_product_count' => (int)($lowStock['active_product_count'] ?? 0),
            'created_this_week' => (int)($stats['created_this_week'] ?? 0)
        ],
        'activity_30d' => [
            'created' => (int)($activity['created_30d'] ?? 0),
            'sent' => (int)($activity['sent_30d'] ?? 0),
            'completed' => (int)($activity['completed_30d'] ?? 0)
        ],
        'status_breakdown' => [
            'pending_supplier' => (int)($statusBreakdown['pending_supplier'] ?? 0),
            'in_finance_review' => (int)($statusBreakdown['in_finance_review'] ?? 0),
            'completed' => (int)($statusBreakdown['completed'] ?? 0),
            'rejected' => (int)($statusBreakdown['rejected'] ?? 0)
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
