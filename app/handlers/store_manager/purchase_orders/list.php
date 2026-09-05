<?php
// app/handlers/store_manager/purchase_orders/list.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PurchaseOrder;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$filters = [];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
} else {
    $filters['statuses'] = ['confirmed', 'partially_received', 'received'];
}

try {
    $poModel = new PurchaseOrder();
    $result = $poModel->getAll($page, $limit, $filters);
    Response::success($result, 'Purchase orders fetched successfully');
} catch (Exception $e) {
    error_log('store_manager/purchase_orders/list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
