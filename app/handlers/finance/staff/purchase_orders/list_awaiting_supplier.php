<?php
// app/handlers/finance/staff/purchase_orders/list_awaiting_supplier.php
// POs dispatched to the supplier that haven't been confirmed yet, or came
// back as a counter-proposal -- view-only visibility for Finance Staff
// into the gap between dispatch and the supplier's response.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PurchaseOrder;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;

try {
    $poModel = new PurchaseOrder();
    $result = $poModel->getAll($page, $limit, ['statuses' => ['pending_confirmation', 'supplier_counter_proposed']]);
    Response::success($result, 'Purchase orders fetched successfully');
} catch (Exception $e) {
    error_log('purchase_orders/list_awaiting_supplier.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
