<?php
// app/handlers/finance/head/get_dashboard_stats.php

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

if (!function_exists('fh_dashboard_build_data')) {
/**
 * Builds the Finance Head dashboard data. Shared by the API endpoint
 * (for refresh) and the dashboard page itself (server-rendered first paint).
 */
function fh_dashboard_build_data(PDO $db, Budget $budgetModel): array {
    $calendarMonth = date('Y-m');
    $cutoffKey = CutoffPeriod::getCurrentKey();

    // Finance Head approval also happens on the requisition itself now, not
    // a PO status -- a PO isn't even created until this gate passes.
    $stmt = $db->query("SELECT COUNT(*) as c FROM requisitions WHERE status = 'pending_finance_head'");
    $pendingRequisitions = (int)$stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM po_payment_requests WHERE status = 'pending'");
    $pendingBatches = (int)$stmt->fetch()['c'];

    $pending = $pendingRequisitions + $pendingBatches;

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM purchase_orders WHERE approved_by IS NOT NULL AND DATE_FORMAT(approved_at, '%Y-%m') = ?");
    $stmt->execute([$calendarMonth]);
    $approvedThisMonth = (int)$stmt->fetch()['c'];

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'cancelled' AND rejection_reason IS NOT NULL AND DATE_FORMAT(updated_at, '%Y-%m') = ?");
    $stmt->execute([$calendarMonth]);
    $rejectedThisMonth = (int)$stmt->fetch()['c'];

    $departments = $budgetModel->getAllDepartmentsStatus($cutoffKey);
    $nearLimit = $budgetModel->getDepartmentsNearLimit($cutoffKey, 80.0);

    $allocatedSum = 0.0;
    $committedSum = 0.0;
    foreach ($departments as $d) {
        if ($d['allocated'] > 0) {
            $allocatedSum += $d['allocated'];
            $committedSum += $d['used'] + $d['reserved'];
        }
    }
    $overallUsedPercentage = $allocatedSum > 0 ? round(($committedSum / $allocatedSum) * 100, 1) : null;

    // The two approval queues behind the "Pending" stat, returned as rows so
    // the dashboard can show them as tables instead of only a count the Head
    // has to click into -- same treatment as the Store Manager dashboard.
    $stmt = $db->query("
        SELECT r.id, r.requisition_number, r.subtotal, r.created_at,
               d.name AS department_name,
               s.company_name AS supplier_name
        FROM requisitions r
        LEFT JOIN departments d ON d.id = r.department_id
        LEFT JOIN suppliers s ON s.id = r.preferred_supplier_id
        WHERE r.status = 'pending_finance_head'
        ORDER BY r.created_at ASC
        LIMIT 20
    ");
    $requisitionsToApprove = $stmt->fetchAll();

    $stmt = $db->query("
        SELECT pr.id, pr.amount, pr.created_at,
               po.po_number,
               s.company_name AS supplier_name,
               u.first_name, u.last_name
        FROM po_payment_requests pr
        LEFT JOIN purchase_orders po ON po.id = pr.po_id
        LEFT JOIN suppliers s ON s.id = po.supplier_id
        LEFT JOIN users u ON u.user_id = pr.requested_by
        WHERE pr.status = 'pending'
        ORDER BY pr.created_at ASC
        LIMIT 20
    ");
    $paymentsToApprove = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT po.id, po.status, po.updated_at, po.rejection_reason,
               r.requisition_number, po.total as requisition_total,
               s.company_name
        FROM purchase_orders po
        JOIN requisitions r ON po.requisition_id = r.id
        JOIN suppliers s ON po.supplier_id = s.id
        WHERE po.status NOT IN ('pending_budget_check', 'budget_rejected', 'pending_fh_approval')
        ORDER BY po.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll();

    return [
        'stats' => [
            'pending' => $pending,
            'approved_this_month' => $approvedThisMonth,
            'rejected_this_month' => $rejectedThisMonth,
            'budget_used_percentage' => $overallUsedPercentage,
            'month_year' => $cutoffKey
        ],
        'budget_departments' => $departments,
        'departments_near_limit' => $nearLimit,
        'requisitions_to_approve' => $requisitionsToApprove,
        'payments_to_approve' => $paymentsToApprove,
        'recent_activity' => $recentActivity
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login');
    }

    if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
        Response::forbidden('Access denied. Finance Head role required.');
    }

    try {
        $db = Database::getInstance()->getConnection();
        Response::success(fh_dashboard_build_data($db, new Budget()), 'Dashboard stats fetched');
    } catch (Exception $e) {
        error_log('finance/head/get_dashboard_stats.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
