<?php
// app/handlers/supplier/get_invoices.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = Auth::userId();

    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $where = "si.supplier_id = ?";
    $params = [$supplierId];

    if (!empty($status) && $status !== 'all') {
        $where .= " AND si.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $where .= " AND (si.invoice_number LIKE ? OR r.requisition_number LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $countSql = "SELECT COUNT(*) as total FROM supplier_invoices si JOIN store_requisitions r ON si.requisition_id = r.id WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $offset = ($page - 1) * $limit;
    $sql = "
        SELECT si.*, s.company_name, r.requisition_number
        FROM supplier_invoices si
        JOIN suppliers s ON si.supplier_id = s.id
        JOIN store_requisitions r ON si.requisition_id = r.id
        WHERE $where
        ORDER BY si.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid
        FROM supplier_invoices
        WHERE supplier_id = ?
    ");
    $stmt->execute([$supplierId]);
    $counts = $stmt->fetch();

    Response::success([
        'invoices' => $invoices,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'tab_counts' => [
            'all' => (int)($counts['total'] ?? 0),
            'pending' => (int)($counts['pending'] ?? 0),
            'verified' => (int)($counts['verified'] ?? 0),
            'paid' => (int)($counts['paid'] ?? 0)
        ]
    ], 'Invoices fetched successfully');

} catch (Exception $e) {
    error_log('get_invoices.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}