<?php
// app/handlers/finance/staff/invoices/list.php
// ?scope=holds (price_hold/quantity_hold) or ?scope=reconciled, default = all.
// Invoices are post-payment reconciliation records now -- there's no
// "payable" scope anymore, payment already happened at the PO level.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Invoice.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Invoice;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$scope = $_GET['scope'] ?? 'all';

$filters = [];
if ($scope === 'holds') {
    $filters['match_statuses'] = ['price_hold', 'quantity_hold'];
} elseif ($scope === 'reconciled') {
    $filters['match_status'] = 'reconciled';
} elseif (!empty($_GET['match_status'])) {
    $filters['match_status'] = $_GET['match_status'];
}

try {
    $invoiceModel = new Invoice();
    $result = $invoiceModel->getAll($page, $limit, $filters);
    Response::success($result, 'Invoices fetched successfully');
} catch (Exception $e) {
    error_log('finance/staff/invoices/list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
