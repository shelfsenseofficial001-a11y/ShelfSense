<?php
// app/handlers/pos/get_orders.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Order.php';

use App\Core\Auth;
use App\Core\Database;
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
    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';

    if (strlen($search) > 50) {
        Response::error('Search term cannot exceed 50 characters', 400);
    }
    if (!empty($date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        Response::error('Invalid date format', 400);
    }
    if (!empty($status) && !in_array($status, ['completed', 'voided'])) {
        Response::error('Invalid status', 400);
    }

    $filters = [];
    if (!empty($search)) $filters['search'] = $search;
    if (!empty($status)) $filters['status'] = $status;
    if (!empty($date)) $filters['date'] = $date;

    $orderModel = new Order();
    $result = $orderModel->getForCashier(Auth::userId(), $page, $limit, $filters);

    $db = Database::getInstance()->getConnection();
    foreach ($result['orders'] as &$order) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $count = $stmt->fetch();
        $order['item_count'] = (int)($count['count'] ?? 0);
    }

    Response::success([
        'orders' => $result['orders'],
        'pagination' => $result['pagination']
    ], 'Orders fetched successfully');

} catch (Exception $e) {
    error_log('get_orders.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}