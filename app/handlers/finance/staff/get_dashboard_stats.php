<?php
// app/handlers/finance/staff/get_dashboard_stats.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

try {
    $db = Database::getInstance()->getConnection();
    $budgetModel = new Budget();

    $stmt = $db->query("
        SELECT po.id, r.department_id, r.period_key, po.total as subtotal
        FROM purchase_orders po
        JOIN requisitions r ON po.requisition_id = r.id
        WHERE po.status = 'pending_budget_check'
    ");
    $pendingRows = $stmt->fetchAll();
    $pendingCount = count($pendingRows);
    $exceededCount = 0;
    foreach ($pendingRows as $row) {
        $bs = $budgetModel->getBudgetStatus($row['department_id'], $row['period_key'], (float)$row['subtotal']);
        if ($bs['exceeded']) $exceededCount++;
    }

    $stmt = $db->query("SELECT COUNT(*) as c FROM po_payment_requests WHERE status = 'pending'");
    $pendingBatches = (int)$stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'pending_dispatch'");
    $pendingDispatch = (int)$stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM invoices WHERE match_status IN ('price_hold','quantity_hold')");
    $invoiceHolds = (int)$stmt->fetch()['c'];

    $department = $budgetModel->getDepartmentByName('store');
    $monthYear = CutoffPeriod::getCurrentKey();
    $budgetStatus = $department ? $budgetModel->getBudgetStatus($department['id'], $monthYear) : null;

    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([Auth::userId()]);
    $recentActivity = $stmt->fetchAll();

    Response::success([
        'stats' => [
            'pending_requisitions' => $pendingCount,
            'budget_exceeded_count' => $exceededCount,
            'pending_payment_requests' => $pendingBatches,
            'pending_dispatch' => $pendingDispatch,
            'invoice_holds' => $invoiceHolds,
            'budget_department' => 'store',
            'budget_month_year' => $monthYear,
            'budget_allocated' => $budgetStatus['allocated'] ?? 0,
            'budget_used' => $budgetStatus['used'] ?? 0,
            'budget_reserved' => $budgetStatus['reserved'] ?? 0,
            'budget_available' => $budgetStatus['available'] ?? 0,
            'budget_used_percentage' => $budgetStatus['used_percentage'] ?? null,
            'budget_has_allocation' => $budgetStatus ? $budgetStatus['allocated'] > 0 : false
        ],
        'recent_activity' => $recentActivity
    ], 'Dashboard stats fetched');

} catch (Exception $e) {
    error_log('get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
