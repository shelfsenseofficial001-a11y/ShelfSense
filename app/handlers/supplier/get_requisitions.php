<?php
// app/handlers/supplier/get_requisitions.php

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

// Status groupings for the Incoming Requisitions tabs.
$statusGroups = [
    'pending' => ['pending_supplier', 'sent_to_supplier'],
    'invoiced' => ['supplier_processed'],
    'paid' => ['paid'],
    'shipped' => ['shipped', 'completed', 'partial_received'],
];

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
    $group = isset($_GET['group']) ? trim($_GET['group']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $where = "r.supplier_id = ?";
    $params = [$supplierId];

    if (!empty($group) && isset($statusGroups[$group])) {
        $placeholders = implode(',', array_fill(0, count($statusGroups[$group]), '?'));
        $where .= " AND r.status IN ($placeholders)";
        foreach ($statusGroups[$group] as $s) {
            $params[] = $s;
        }
    } elseif (!empty($status) && $status !== 'all') {
        $where .= " AND r.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $where .= " AND (r.requisition_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $countSql = "SELECT COUNT(*) as total FROM store_requisitions r JOIN users u ON r.created_by = u.user_id WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $offset = ($page - 1) * $limit;
    $sql = "
        SELECT
            r.*, u.first_name, u.last_name,
            (SELECT COUNT(*) FROM store_requisition_items ri WHERE ri.requisition_id = r.id) as item_count
        FROM store_requisitions r
        JOIN users u ON r.created_by = u.user_id
        WHERE $where
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $requisitions = $stmt->fetchAll();

    // Tab counts for the current supplier, independent of the active filter
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('pending_supplier','sent_to_supplier') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'supplier_processed' THEN 1 ELSE 0 END) as invoiced,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
            SUM(CASE WHEN status IN ('shipped','completed','partial_received') THEN 1 ELSE 0 END) as shipped
        FROM store_requisitions
        WHERE supplier_id = ?
    ");
    $stmt->execute([$supplierId]);
    $counts = $stmt->fetch();

    Response::success([
        'requisitions' => $requisitions,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'tab_counts' => [
            'pending' => (int)($counts['pending'] ?? 0),
            'invoiced' => (int)($counts['invoiced'] ?? 0),
            'paid' => (int)($counts['paid'] ?? 0),
            'shipped' => (int)($counts['shipped'] ?? 0),
            'all' => (int)($counts['total'] ?? 0)
        ]
    ], 'Requisitions fetched successfully');

} catch (Exception $e) {
    error_log('get_requisitions.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
