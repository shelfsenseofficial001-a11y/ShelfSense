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

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

try {
    $db = Database::getInstance()->getConnection();
    $budgetModel = new Budget();
    $calendarMonth = date('Y-m');
    $cutoffKey = CutoffPeriod::getCurrentKey();

    $stmt = $db->query("SELECT COUNT(*) as c FROM requisitions WHERE status = 'pending_finance_head'");
    $pendingRequisitions = (int)$stmt->fetch()['c'];

    $stmt = $db->query("SELECT COUNT(*) as c FROM payment_batches WHERE status = 'pending_approval'");
    $pendingBatches = (int)$stmt->fetch()['c'];

    $pending = $pendingRequisitions + $pendingBatches;

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM requisitions WHERE status = 'converted_to_po' AND DATE_FORMAT(updated_at, '%Y-%m') = ?");
    $stmt->execute([$calendarMonth]);
    $approvedThisMonth = (int)$stmt->fetch()['c'];

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM requisitions WHERE status = 'rejected' AND DATE_FORMAT(updated_at, '%Y-%m') = ?");
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
        SELECT r.id, r.status, r.updated_at, r.rejected_reason,
               r.requisition_number, r.subtotal as requisition_total,
               s.company_name
        FROM requisitions r
        JOIN suppliers s ON r.preferred_supplier_id = s.id
        WHERE r.status IN ('converted_to_po', 'rejected')
        ORDER BY r.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll();

    Response::success([
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
    ], 'Dashboard stats fetched');

} catch (Exception $e) {
    error_log('finance/head/get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
