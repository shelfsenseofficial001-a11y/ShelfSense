<?php
// app/handlers/pos/get_daily_sales.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Order.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Order;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isEmployee() && !Auth::isSuperAdmin() && !Auth::isStoreManager()) {
    Response::forbidden('Access denied. Employee role required.');
}

try {
    $cashierId = Auth::userId();
    $orderModel = new Order();

    $todaySales = $orderModel->getTodaySales($cashierId);
    $topProduct = $orderModel->getTopProduct($cashierId);
    $recentTransactions = $orderModel->getRecentTransactions($cashierId, 10);

    Response::success([
        'today' => [
            'total_sales' => (float)($todaySales['total_sales'] ?? 0),
            'transaction_count' => (int)($todaySales['transaction_count'] ?? 0),
            'total_paid' => (float)($todaySales['total_paid'] ?? 0)
        ],
        'top_product' => $topProduct ? [
            'name' => $topProduct['name'],
            'quantity' => (int)$topProduct['total_quantity'],
            'revenue' => (float)$topProduct['total_revenue']
        ] : null,
        'recent_transactions' => $recentTransactions
    ], 'Daily sales fetched successfully');

} catch (Exception $e) {
    error_log('get_daily_sales.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}