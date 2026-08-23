<?php
// app/handlers/store_manager/get_requisitions.php

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

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

// Status groupings used by the Requisitions tabs. Kept in one place so every
// tab and the dashboard agree on what "pending supplier" / "awaiting finance" mean.
$statusGroups = [
    'pending_supplier' => ['draft', 'pending_supplier', 'sent_to_supplier'],
    'awaiting_finance'  => ['supplier_processed', 'awaiting_finance_staff', 'awaiting_finance', 'finance_approved'],
    'history'           => ['paid', 'shipped', 'completed', 'partial_received', 'finance_rejected'],
];

$sortableColumns = [
    'created_at' => 'r.created_at',
    'order_date' => 'r.order_date',
    'total' => 'r.total',
    'status' => 'r.status',
    'requisition_number' => 'r.requisition_number',
];

try {
    $db = Database::getInstance()->getConnection();

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $group = isset($_GET['group']) ? trim($_GET['group']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $scope = isset($_GET['scope']) ? trim($_GET['scope']) : 'all';
    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    $sortBy = isset($_GET['sort_by']) && isset($sortableColumns[$_GET['sort_by']]) ? $_GET['sort_by'] : 'created_at';
    $sortDir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc' ? 'ASC' : 'DESC';

    $where = "1=1";
    $params = [];

    if ($scope === 'mine') {
        $where .= " AND r.created_by = ?";
        $params[] = Auth::userId();
    }

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
        $where .= " AND (r.requisition_number LIKE ? OR s.company_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if (!empty($dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where .= " AND r.order_date >= ?";
        $params[] = $dateFrom;
    }
    if (!empty($dateTo) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where .= " AND r.order_date <= ?";
        $params[] = $dateTo;
    }

    $countSql = "SELECT COUNT(*) as total FROM store_requisitions r JOIN suppliers s ON r.supplier_id = s.id WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $offset = ($page - 1) * $limit;
    $orderColumn = $sortableColumns[$sortBy];
    $sql = "
        SELECT
            r.*, s.company_name, u.first_name, u.last_name,
            (SELECT COUNT(*) FROM store_requisition_items ri WHERE ri.requisition_id = r.id) as item_count,
            (SELECT MAX(gr.receipt_date) FROM goods_receipts gr WHERE gr.requisition_id = r.id) as actual_delivery_date
        FROM store_requisitions r
        JOIN suppliers s ON r.supplier_id = s.id
        JOIN users u ON r.created_by = u.user_id
        WHERE $where
        ORDER BY $orderColumn $sortDir
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $requisitions = $stmt->fetchAll();

    // Summary counts for the current scope (used by tab stat cards), independent of the group/status filter
    $summaryWhere = "1=1";
    $summaryParams = [];
    if ($scope === 'mine') {
        $summaryWhere .= " AND r.created_by = ?";
        $summaryParams[] = Auth::userId();
    }
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('draft','pending_supplier','sent_to_supplier') THEN 1 ELSE 0 END) as pending_supplier,
            SUM(CASE WHEN status IN ('supplier_processed','awaiting_finance_staff','awaiting_finance','finance_approved') THEN 1 ELSE 0 END) as awaiting_finance,
            SUM(CASE WHEN status IN ('paid','shipped','completed','partial_received','finance_rejected') THEN 1 ELSE 0 END) as history,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'finance_rejected' THEN 1 ELSE 0 END) as rejected,
            COALESCE(SUM(CASE WHEN status IN ('paid','shipped','completed','partial_received') THEN total ELSE 0 END), 0) as total_spent
        FROM store_requisitions r
        WHERE $summaryWhere
    ");
    $stmt->execute($summaryParams);
    $summary = $stmt->fetch();

    Response::success([
        'requisitions' => $requisitions,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'summary' => [
            'total' => (int)($summary['total'] ?? 0),
            'pending_supplier' => (int)($summary['pending_supplier'] ?? 0),
            'awaiting_finance' => (int)($summary['awaiting_finance'] ?? 0),
            'history' => (int)($summary['history'] ?? 0),
            'completed' => (int)($summary['completed'] ?? 0),
            'rejected' => (int)($summary['rejected'] ?? 0),
            'total_spent' => (float)($summary['total_spent'] ?? 0)
        ]
    ], 'Requisitions fetched successfully');

} catch (Exception $e) {
    error_log('get_requisitions.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
