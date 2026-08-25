<?php
// app/handlers/finance/head/get_dashboard_stats.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PaymentRequest.php';
require_once __DIR__ . '/../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PaymentRequest;
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
    $paymentRequestModel = new PaymentRequest();
    $budgetModel = new Budget();
    $monthYear = date('Y-m');

    $pending = (int)$paymentRequestModel->getCountByStatus('pending');

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM payment_requests WHERE status = 'approved' AND DATE_FORMAT(approved_at, '%Y-%m') = ?");
    $stmt->execute([$monthYear]);
    $approvedThisMonth = (int)$stmt->fetch()['c'];

    // No dedicated rejected_at column — updated_at reflects the moment status was
    // last changed, which for a rejected request is truthfully the rejection time.
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM payment_requests WHERE status = 'rejected' AND DATE_FORMAT(updated_at, '%Y-%m') = ?");
    $stmt->execute([$monthYear]);
    $rejectedThisMonth = (int)$stmt->fetch()['c'];

    // Real per-department budget status (same definitions as Finance Staff's budget model).
    $departments = $budgetModel->getAllDepartmentsStatus($monthYear);
    $nearLimit = $budgetModel->getDepartmentsNearLimit($monthYear, 80.0);

    $allocatedSum = 0.0;
    $committedSum = 0.0; // used + reserved, across departments that have an allocation
    foreach ($departments as $d) {
        if ($d['has_allocation']) {
            $allocatedSum += $d['allocated'];
            $committedSum += $d['used'] + $d['reserved'];
        }
    }
    $overallUsedPercentage = $allocatedSum > 0 ? round(($committedSum / $allocatedSum) * 100, 1) : null;

    // Recent activity: the most recently touched payment requests, regardless of
    // status — a truthful mix of pending/approved/rejected, exactly reflecting
    // real updated_at timestamps (no fabricated timeline).
    $stmt = $db->prepare("
        SELECT pr.id, pr.status, pr.approved_at, pr.rejection_reason, pr.updated_at, pr.requested_at,
               r.requisition_number, r.total as requisition_total,
               s.company_name,
               u2.first_name as approved_first, u2.last_name as approved_last
        FROM payment_requests pr
        JOIN store_requisitions r ON pr.requisition_id = r.id
        JOIN suppliers s ON r.supplier_id = s.id
        LEFT JOIN users u2 ON pr.approved_by = u2.user_id
        ORDER BY pr.updated_at DESC
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
            'month_year' => $monthYear
        ],
        'budget_departments' => $departments,
        'departments_near_limit' => $nearLimit,
        'recent_activity' => $recentActivity
    ], 'Dashboard stats fetched');

} catch (Exception $e) {
    error_log('finance/head/get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
