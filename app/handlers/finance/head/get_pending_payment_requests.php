<?php
// app/handlers/finance/head/get_pending_payment_requests.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PaymentRequest.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PaymentRequest;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

try {
    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $status = isset($_GET['status']) ? trim($_GET['status']) : 'pending';

    $paymentRequestModel = new PaymentRequest();
    $db = Database::getInstance()->getConnection();

    $offset = ($page - 1) * $limit;

    // Get payment requests
    $countSql = "SELECT COUNT(*) as total FROM payment_requests WHERE status = ?";
    $stmt = $db->prepare($countSql);
    $stmt->execute([$status]);
    $total = $stmt->fetch()['total'];

    $sql = "
        SELECT pr.*, 
               r.requisition_number, r.total as requisition_total, r.order_date,
               s.company_name,
               u1.first_name as requested_first, u1.last_name as requested_last,
               si.invoice_number, si.total as invoice_total
        FROM payment_requests pr
        JOIN store_requisitions r ON pr.requisition_id = r.id
        JOIN suppliers s ON r.supplier_id = s.id
        JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
        JOIN users u1 ON pr.requested_by = u1.user_id
        WHERE pr.status = ?
        ORDER BY pr.requested_at ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$status, $limit, $offset]);
    $requests = $stmt->fetchAll();

    Response::success([
        'payment_requests' => $requests,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ]
    ], 'Payment requests fetched');

} catch (Exception $e) {
    error_log('get_pending_payment_requests.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}