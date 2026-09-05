<?php
// app/handlers/finance/head/po_payments/list_pending.php

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/PoPaymentRequest.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PoPaymentRequest;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$status = $_GET['status'] ?? 'pending';

try {
    $model = new PoPaymentRequest();
    $result = $model->getAll($page, $limit, ['status' => $status]);
    Response::success($result, 'Payment requests fetched successfully');
} catch (Exception $e) {
    error_log('po_payments/list_pending.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
