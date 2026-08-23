<?php
// app/handlers/pos/get_order.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/OrderItem.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Order;
use App\Models\OrderItem;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isEmployee() && !Auth::isSuperAdmin() && !Auth::isStoreManager()) {
    Response::forbidden('Access denied. Employee role required.');
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$orderNumber = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

if ($orderId <= 0 && empty($orderNumber)) {
    Response::error('Order ID or order number required', 400);
}

try {
    $orderModel = new Order();
    $orderItemModel = new OrderItem();

    if ($orderId > 0) {
        $order = $orderModel->getById($orderId);
    } else {
        $order = $orderModel->getByNumber($orderNumber);
    }

    if (!$order) {
        Response::notFound('Order not found');
    }

    // Check if this employee owns the order
    if ($order['cashier_id'] != Auth::userId() && !Auth::isSuperAdmin() && !Auth::isStoreManager()) {
        Response::forbidden('You can only view your own orders');
    }

    $order['items'] = $orderItemModel->getByOrderId($order['id']);
    $order['item_count'] = count($order['items']);

    $order['subtotal'] = (float)$order['subtotal'];
    $order['total'] = (float)$order['total'];
    $order['amount_paid'] = (float)$order['amount_paid'];
    $order['change_amount'] = (float)$order['change_amount'];

    Response::success([
        'order' => $order
    ], 'Order fetched successfully');

} catch (Exception $e) {
    error_log('get_order.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage(), 500);
}