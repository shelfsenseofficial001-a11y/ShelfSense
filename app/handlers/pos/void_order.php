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

// See get_orders.php -- a cashier can reach this either via a real staff
// login or via the register's POS PIN unlock (no user_id session of its own).
$isPosSession = Auth::posCheck();

if (!$isPosSession && !(Auth::check() && (Auth::isEmployee() || Auth::isStoreManager() || Auth::isSuperAdmin()))) {
    Response::unauthorized('Please login to access this resource');
}
if ($isPosSession && !Auth::posCashierId()) {
    Response::forbidden('Select which cashier is ringing up sales first.');
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$managerIdentifier = isset($input['manager_employee_number']) ? trim($input['manager_employee_number']) : '';
$managerPassword = isset($input['manager_password']) ? (string)$input['manager_password'] : '';

if ($orderId <= 0) {
    Response::error('Order ID required', 400);
}

if (empty($reason)) {
    Response::error('Void reason is required', 400);
}

if (strlen($reason) > 255) {
    Response::error('Reason cannot exceed 255 characters', 400);
}

if ($managerIdentifier === '' || $managerPassword === '') {
    Response::error('Store Manager approval is required to void an order.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    // Voiding always requires a Store Manager to authorize it -- verified
    // fresh here against their own login, independent of whoever is
    // currently ringing up sales on this register (a POS terminal session
    // has no manager account of its own to check against).
    $stmt = $db->prepare("SELECT user_id, password FROM users WHERE (employee_number = ? OR email = ?) AND role = 'store_manager' AND is_active = 1");
    $stmt->execute([$managerIdentifier, $managerIdentifier]);
    $manager = $stmt->fetch();

    if (!$manager || !password_verify($managerPassword, $manager['password'])) {
        Response::error('Invalid Store Manager credentials.', 401);
    }

    $db->beginTransaction();

    $orderModel = new Order();
    $orderItemModel = new OrderItem();
    $productModel = new Product();

    $order = $orderModel->getById($orderId);
    if (!$order) {
        Response::notFound('Order not found');
    }

    if ($order['status'] === 'voided') {
        Response::error('Order is already voided', 400);
    }

    $items = $orderItemModel->getByOrderId($orderId);

    foreach ($items as $item) {
        $productModel->increaseStock($item['product_id'], $item['quantity']);
    }

    $orderModel->updateStatus($orderId, 'voided', $reason, (int)$manager['user_id']);

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