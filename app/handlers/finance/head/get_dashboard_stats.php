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
    $paymentRequestModel = new PaymentRequest();
    $budgetModel = new Budget();

    // Stats
    $pending = $paymentRequestModel->getCountByStatus('pending');
    $approved = $paymentRequestModel->getCountByStatus('approved');
    $rejected = $paymentRequestModel->getCountByStatus('rejected');

    // Budget stats (store department, current month)
    $department = 'store';
    $monthYear = date('Y-m');
    $budget = $budgetModel->getOrCreate($department, $monthYear);
    $usedPercentage = $budget['allocated_budget'] > 0 
        ? round(($budget['used_budget'] / $budget['allocated_budget']) * 100) 
        : 0;

    // Recent approvals (last 5 approved/rejected by this head)
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT pr.*, 
               r.requisition_number,
               u.first_name, u.last_name
        FROM payment_requests pr
        JOIN store_requisitions r ON pr.requisition_id = r.id
        JOIN users u ON pr.requested_by = u.user_id
        WHERE pr.approved_by = ? OR pr.status IN ('approved', 'rejected')
        ORDER BY pr.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([Auth::userId()]);
    $recentActivity = $stmt->fetchAll();

    Response::success([
        'stats' => [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'budget_used_percentage' => $usedPercentage,
            'budget_remaining' => $budget['allocated_budget'] - $budget['used_budget'],
            'budget_used' => $budget['used_budget'],
            'budget_total' => $budget['allocated_budget']
        ],
        'recent_activity' => $recentActivity
    ], 'Dashboard stats fetched');

} catch (Exception $e) {
    error_log('get_dashboard_stats.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}