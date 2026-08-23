<?php
// app/handlers/finance/staff/get_payment_requests.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = Auth::userId();

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

    $where = "pr.requested_by = ?";
    $params = [$userId];

    if (!empty($status) && $status !== 'all') {
        $where .= " AND pr.status = ?";
        $params[] = $status;
    }
    if (!empty($search)) {
        $where .= " AND (r.requisition_number LIKE ? OR si.invoice_number LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    if (!empty($dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where .= " AND DATE(pr.requested_at) >= ?";
        $params[] = $dateFrom;
    }
    if (!empty($dateTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where .= " AND DATE(pr.requested_at) <= ?";
        $params[] = $dateTo;
    }

    $offset = ($page - 1) * $limit;

    $countSql = "SELECT COUNT(*) as total FROM payment_requests pr
                 JOIN store_requisitions r ON pr.requisition_id = r.id
                 JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
                 WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $sql = "
        SELECT pr.*,
               r.requisition_number, r.total as requisition_total,
               si.invoice_number, si.total as invoice_total,
               u.first_name, u.last_name,
               ua.first_name as approved_first, ua.last_name as approved_last
        FROM payment_requests pr
        JOIN store_requisitions r ON pr.requisition_id = r.id
        JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
        JOIN users u ON pr.requested_by = u.user_id
        LEFT JOIN users ua ON pr.approved_by = ua.user_id
        WHERE $where
        ORDER BY pr.requested_at DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM payment_requests
        WHERE requested_by = ?
    ");
    $stmt->execute([$userId]);
    $counts = $stmt->fetch();

    Response::success([
        'payment_requests' => $requests,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'tab_counts' => [
            'all' => (int)($counts['total'] ?? 0),
            'pending' => (int)($counts['pending'] ?? 0),
            'approved' => (int)($counts['approved'] ?? 0),
            'rejected' => (int)($counts['rejected'] ?? 0)
        ]
    ], 'Payment requests fetched');

} catch (Exception $e) {
    error_log('get_payment_requests.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
