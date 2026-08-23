<?php
// app/handlers/pos/void_order.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/OrderItem.php';
require_once __DIR__ . '/../../models/Product.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only Employee, Store Manager, or SuperAdmin can void
if (!Auth::isEmployee() && !Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Employee role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($orderId <= 0) {
    Response::error('Order ID required', 400);
}

if (empty($reason)) {
    Response::error('Void reason is required', 400);
}

if (strlen($reason) > 255) {
    Response::error('Reason cannot exceed 255 characters', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $orderModel = new Order();
    $orderItemModel = new OrderItem();
    $productModel = new Product();

    $order = $orderModel->getById($orderId);
    if (!$order) {
        Response::notFound('Order not found');
    }

    // Check if this employee owns the order
    if ($order['cashier_id'] != Auth::userId() && !Auth::isStoreManager() && !Auth::isSuperAdmin()) {
        Response::forbidden('You can only void your own orders');
    }

    if ($order['status'] === 'voided') {
        Response::error('Order is already voided', 400);
    }

    $items = $orderItemModel->getByOrderId($orderId);

    foreach ($items as $item) {
        $productModel->increaseStock($item['product_id'], $item['quantity']);
    }

    $orderModel->updateStatus($orderId, 'voided', $reason, Auth::userId());

    $db->commit();

    Response::success([
        'order_id' => $orderId,
        'status' => 'voided',
        'items_refunded' => count($items)
    ], 'Order voided successfully. Stock has been refunded.');

} catch (Exception $e) {
    $db->rollBack();
    error_log('void_order.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}