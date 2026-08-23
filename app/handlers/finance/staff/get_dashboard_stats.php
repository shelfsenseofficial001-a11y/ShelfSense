<?php
// app/handlers/finance/staff/get_dashboard_stats.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/StoreRequisition.php';
require_once __DIR__ . '/../../../models/PaymentRequest.php';
require_once __DIR__ . '/../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\StoreRequisition;
use App\Models\PaymentRequest;
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

    // Pending requisitions (awaiting finance staff), each evaluated against its own
    // real department/period budget to find how many currently exceed it.
    $stmt = $db->query("
        SELECT id, department, budget_month_year, total
        FROM store_requisitions
        WHERE status = 'awaiting_finance_staff'
    ");
    $pendingRows = $stmt->fetchAll();
    $pendingCount = count($pendingRows);
    $exceededCount = 0;
    foreach ($pendingRows as $row) {
        $bs = $budgetModel->getBudgetStatus($row['department'] ?: 'store', $row['budget_month_year'] ?: date('Y-m'), (float)$row['total']);
        if ($bs['exceeded']) $exceededCount++;
    }

    // Pending payment requests (awaiting finance head), across all finance staff
    $paymentRequestModel = new PaymentRequest();
    $pendingRequests = $paymentRequestModel->getCountByStatus('pending');

    // Budget for the primary department (store) this month — the dashboard's single
    // headline bar. Other departments are visible on the full Budget View page.
    $department = 'store';
    $monthYear = date('Y-m');
    $budgetStatus = $budgetModel->getBudgetStatus($department, $monthYear);

    // Recent activity (last 5 notifications for this user)
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([Auth::userId()]);
    $recentActivity = $stmt->fetchAll();

    Response::success([
        'stats' => [
            'pending_requisitions' => $pendingCount,
            'budget_exceeded_count' => $exceededCount,
            'pending_payment_requests' => (int)$pendingRequests,
            'budget_department' => $department,
            'budget_month_year' => $monthYear,
            'budget_allocated' => $budgetStatus['allocated'],
            'budget_used' => $budgetStatus['used'],
            'budget_reserved' => $budgetStatus['reserved'],
            'budget_available' => $budgetStatus['available'],
            'budget_used_percentage' => $budgetStatus['used_percentage'],
            'budget_has_allocation' => $budgetStatus['has_allocation']
        ],
        'recent_activity' => $recentActivity
    ], 'Dashboard stats fetched');

} catch (Exception $e) {
    error_log('get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
