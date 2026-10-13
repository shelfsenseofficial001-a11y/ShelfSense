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

if (!function_exists('fs_dashboard_build_data')) {
/**
 * Builds the Finance Staff dashboard data. Shared by the API endpoint
 * (for refresh) and the dashboard page itself (server-rendered first paint).
 */
function fs_dashboard_build_data(PDO $db, Budget $budgetModel): array {

    // Requisition and Purchase Order are separate stages -- budget check
    // happens on the requisition itself, before any PO exists.
    // Joined rather than counted-only so the dashboard can show the actual
    // queue as a table, the same way the Store Manager dashboard previews
    // its requisitions -- not just a number the user has to click into.
    $stmt = $db->query("
        SELECT r.id, r.requisition_number, r.department_id, r.period_key, r.subtotal,
               r.needed_by_date, r.created_at,
               d.name AS department_name,
               s.company_name AS supplier_name
        FROM requisitions r
        LEFT JOIN departments d ON d.id = r.department_id
        LEFT JOIN suppliers s ON s.id = r.preferred_supplier_id
        WHERE r.status = 'pending_budget_check'
        ORDER BY r.created_at ASC
    ");
    $pendingRows = $stmt->fetchAll();
    $pendingCount = count($pendingRows);
    $exceededCount = 0;
    $toReview = [];
    $exceeded = [];
    foreach ($pendingRows as $row) {
        $bs = $budgetModel->getBudgetStatus($row['department_id'], $row['period_key'], (float)$row['subtotal']);
        $entry = [
            'id' => (int)$row['id'],
            'requisition_number' => $row['requisition_number'],
            'department' => $row['department_name'] ?? '',
            'supplier' => $row['supplier_name'] ?? '',
            'subtotal' => (float)$row['subtotal'],
            'available' => $bs['available'] ?? 0,
            'exceeded' => (bool)$bs['exceeded'],
            'created_at' => $row['created_at'],
        ];
        $toReview[] = $entry;
        if ($bs['exceeded']) {
            $exceededCount++;
            $exceeded[] = $entry;
        }
    }

    $stmt = $db->query("SELECT COUNT(*) as c FROM po_payment_requests WHERE status = 'pending'");
    $pendingBatches = (int)$stmt->fetch()['c'];

    // The payment requests this Finance Staff member raised themselves --
    // mirrors the "My Payment Requests" quick action as an inline table.
    $stmt = $db->prepare("
        SELECT pr.id, pr.amount, pr.status, pr.created_at, pr.rejection_reason,
               po.po_number,
               s.company_name AS supplier_name
        FROM po_payment_requests pr
        LEFT JOIN purchase_orders po ON po.id = pr.po_id
        LEFT JOIN suppliers s ON s.id = po.supplier_id
        WHERE pr.requested_by = ?
        ORDER BY pr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([Auth::userId()]);
    $myPaymentRequests = $stmt->fetchAll();

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

    return [
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
        'to_review' => $toReview,
        'budget_exceeded' => $exceeded,
        'my_payment_requests' => $myPaymentRequests,
        'recent_activity' => $recentActivity
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login');
    }

    if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
        Response::forbidden('Access denied. Finance Staff role required.');
    }

    try {
        $db = Database::getInstance()->getConnection();
        Response::success(fs_dashboard_build_data($db, new Budget()), 'Dashboard stats fetched');
    } catch (Exception $e) {
        error_log('get_dashboard_stats.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
