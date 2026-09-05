<?php
// app/handlers/supplier/purchase_orders/list.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
$stmt->execute([Auth::userId()]);
$supplier = $stmt->fetch();
if (!$supplier) {
    Response::success(['purchase_orders' => [], 'pagination' => ['currentPage' => 1, 'perPage' => 20, 'totalRecords' => 0, 'totalPages' => 0]], 'No supplier profile linked to this account.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$filters = ['supplier_id' => $supplier['id']];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

try {
    $poModel = new PurchaseOrder();
    $result = $poModel->getAll($page, $limit, $filters);
    Response::success($result, 'Purchase orders fetched successfully');
} catch (Exception $e) {
    error_log('supplier/purchase_orders/list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
