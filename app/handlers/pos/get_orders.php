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

if (!function_exists('pos_orders_build_data')) {
/**
 * Builds a page of a cashier's order history. Shared by the API endpoint
 * (filter/pagination/search) and the Orders page's first paint.
 */
function pos_orders_build_data(PDO $db, int $cashierId, int $page, int $limit, array $filters): array {
    $orderModel = new Order();
    $result = $orderModel->getForCashier($cashierId, $page, $limit, $filters);

    foreach ($result['orders'] as &$order) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $count = $stmt->fetch();
        $order['item_count'] = (int)($count['count'] ?? 0);
    }

    return [
        'orders' => $result['orders'],
        'pagination' => $result['pagination']
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    // A cashier reaches this page either via a real staff login (isEmployee) or
    // via the register's POS PIN unlock (no user_id session of its own -- see
    // create_order.php, which already handles both) -- Order History has to
    // accept the same two paths or a POS-session cashier gets bounced to the
    // login screen just from clicking "View More".
    $isPosSession = Auth::posCheck();

    if (!$isPosSession && !(Auth::check() && (Auth::isEmployee() || Auth::isSuperAdmin() || Auth::isStoreManager()))) {
        Response::unauthorized('Please login to access this resource');
    }
    if ($isPosSession && !Auth::posCashierId()) {
        Response::forbidden('Select which cashier is ringing up sales first.');
    }

    $cashierId = $isPosSession ? Auth::posCashierId() : Auth::userId();

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

        $db = Database::getInstance()->getConnection();
        Response::success(pos_orders_build_data($db, $cashierId, $page, $limit, $filters), 'Orders fetched successfully');
    } catch (Exception $e) {
        error_log('get_orders.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}