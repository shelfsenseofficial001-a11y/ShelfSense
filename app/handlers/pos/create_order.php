<?php
// app/handlers/pos/create_order.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/OrderItem.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Register.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Register;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isEmployee() && !Auth::isSuperAdmin() && !Auth::isStoreManager()) {
    Response::forbidden('Access denied. Employee role required.');
}

$input = json_decode(file_get_contents('php://input'), true);

error_log('create_order input: ' . print_r($input, true));

if (!$input) {
    Response::error('Invalid request data. Please try again.', 400);
}

$items = $input['items'] ?? [];
$paymentMethod = $input['payment_method'] ?? '';
$amountPaid = isset($input['amount_paid']) ? floatval($input['amount_paid']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$paymentReference = isset($input['payment_reference']) ? trim($input['payment_reference']) : '';

if (empty($items) || !is_array($items)) {
    Response::error('Cart is empty', 400);
}

if (empty($paymentMethod) || !in_array($paymentMethod, ['cash', 'card', 'gcash', 'paymaya', 'other'])) {
    Response::error('Invalid payment method', 400);
}

if ($paymentMethod === 'cash' && $amountPaid <= 0) {
    Response::error('Amount tendered is required for cash payment', 400);
}

if (strlen($notes) > 500) {
    Response::error('Notes cannot exceed 500 characters', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $cashierId = Auth::userId();

    // Cashiers must have a register budget allocated before ringing up sales;
    // the order is tied to that allocation so cash-out totals stay scoped to
    // the current float and never bleed into a prior or future shift.
    $registerAllocationId = null;
    if (Auth::isEmployee()) {
        $registerModel = new Register();
        $allocation = $registerModel->getActiveAllocationForCashier($cashierId);
        if (!$allocation) {
            Response::error('No budget has been allocated to your register yet. Please see your Store Manager.', 400);
        }
        $registerAllocationId = $allocation['id'];
    }

    $db->beginTransaction();

    $orderModel = new Order();
    $orderItemModel = new OrderItem();
    $productModel = new Product();

    $orderNumber = $orderModel->generateOrderNumber();

    $subtotal = 0;
    $orderItems = [];

    foreach ($items as $item) {
        $productId = intval($item['product_id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            throw new \Exception('Invalid product or quantity');
        }

        if ($quantity > 999) {
            throw new \Exception('Quantity exceeds maximum allowed (999)');
        }

        $product = $productModel->getById($productId);
        if (!$product) {
            throw new \Exception('Product not found: ID ' . $productId);
        }

        if ($product['stock_quantity'] < $quantity) {
            throw new \Exception('Insufficient stock for ' . $product['name']);
        }

        $price = floatval($product['price']);
        $itemSubtotal = $price * $quantity;

        $orderItems[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $itemSubtotal
        ];

        $subtotal += $itemSubtotal;
        $productModel->reduceStock($productId, $quantity);
    }

    $total = $subtotal;

    $changeAmount = 0;
    if ($paymentMethod === 'cash') {
        $changeAmount = $amountPaid - $total;
        if ($changeAmount < 0) {
            throw new \Exception('Amount tendered is less than total');
        }
    } else {
        $amountPaid = $total;
    }

    $orderId = $orderModel->create([
        'order_number' => $orderNumber,
        'cashier_id' => $cashierId,
        'register_allocation_id' => $registerAllocationId,
        'subtotal' => $subtotal,
        'total' => $total,
        'amount_paid' => $amountPaid,
        'change_amount' => $changeAmount,
        'payment_method' => $paymentMethod,
        'payment_reference' => $paymentReference,
        'notes' => $notes
    ]);

    if (!$orderId) {
        throw new \Exception('Failed to create order');
    }

    foreach ($orderItems as $item) {
        $item['order_id'] = $orderId;
        $orderItemModel->create($item);
    }

    $db->commit();

    $order = $orderModel->getById($orderId);
    $order['items'] = $orderItemModel->getByOrderId($orderId);

    createNotification(
        Auth::userId(),
        'order_completed',
        "Order #{$orderNumber} completed. Total: ₱" . number_format($total, 2),
        "?page=pos_orders&view={$orderId}"
    );

    Response::success([
        'order' => $order,
        'change_amount' => $changeAmount
    ], 'Order completed successfully');

} catch (Exception $e) {
    $db->rollBack();
    error_log('create_order.php error: ' . $e->getMessage());
    Response::error($e->getMessage(), 400);
}