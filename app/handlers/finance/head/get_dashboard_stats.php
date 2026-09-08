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

    $stmt = $db->query("SELECT COUNT(*) as c FROM purchase_orders WHERE status = 'pending_fh_approval'");
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
