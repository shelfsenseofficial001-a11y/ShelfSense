<?php
// app/handlers/supplier/invoices/list.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Invoice.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Invoice;

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
    Response::success(['invoices' => [], 'pagination' => ['currentPage' => 1, 'perPage' => 20, 'totalRecords' => 0, 'totalPages' => 0]], 'No supplier profile linked to this account.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;

try {
    $invoiceModel = new Invoice();
    $result = $invoiceModel->getAll($page, $limit, ['supplier_id' => $supplier['id']]);
    Response::success($result, 'Invoices fetched successfully');
} catch (Exception $e) {
    error_log('supplier/invoices/list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
