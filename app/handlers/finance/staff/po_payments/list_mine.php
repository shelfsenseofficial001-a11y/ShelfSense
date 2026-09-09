<?php
// app/handlers/finance/staff/po_payments/list_mine.php
// Payment requests the current Finance Staff member has submitted, with
// their real outcome -- pending, approved (with the actual payment
// method/reference from the payments row), or rejected (with reason).
// Previously the only way to see this was the notification bell.

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
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$filters = ['requested_by' => Auth::userId()];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

try {
    $model = new PoPaymentRequest();
    $result = $model->getAll($page, $limit, $filters);
    Response::success($result, 'Payment requests fetched successfully');
} catch (Exception $e) {
    error_log('po_payments/list_mine.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
