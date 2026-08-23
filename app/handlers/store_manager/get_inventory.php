<?php
// app/handlers/store_manager/get_inventory.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

// Whitelisted sort columns — never build ORDER BY from raw user input
$sortableColumns = [
    'name' => 'p.name',
    'category' => 'c.name',
    'price' => 'p.price',
    'stock' => 'p.stock_quantity',
];

try {
    $db = Database::getInstance()->getConnection();

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 30;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? intval($_GET['category']) : 0;
    $lowStock = isset($_GET['low_stock']) ? intval($_GET['low_stock']) : 0;
    $stockStatus = isset($_GET['stock_status']) ? trim($_GET['stock_status']) : '';
    $sortBy = isset($_GET['sort_by']) && isset($sortableColumns[$_GET['sort_by']]) ? $_GET['sort_by'] : 'name';
    $sortDir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'desc' ? 'DESC' : 'ASC';

    $where = "p.is_active = 1";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (p.name LIKE ? OR p.barcode LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if ($category > 0) {
        $where .= " AND p.category_id = ?";
        $params[] = $category;
    }

    // stock_status is the richer 3-way filter; low_stock=1 is kept for backward compatibility
    if ($stockStatus === 'out') {
        $where .= " AND p.stock_quantity = 0";
    } elseif ($stockStatus === 'low') {
        $where .= " AND p.stock_quantity > 0 AND p.stock_quantity <= p.reorder_level";
    } elseif ($stockStatus === 'in') {
        $where .= " AND p.stock_quantity > p.reorder_level";
    } elseif ($lowStock == 1) {
        $where .= " AND p.stock_quantity <= p.reorder_level";
    }

    $offset = ($page - 1) * $limit;

    // Count
    $countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    // Get products with category name
    $orderColumn = $sortableColumns[$sortBy];
    $sql = "
        SELECT
            p.id,
            p.barcode,
            p.name,
            p.description,
            p.price,
            p.stock_quantity,
            p.reorder_level,
            p.image_path,
            c.name as category_name,
            c.id as category_id
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $where
        ORDER BY $orderColumn $sortDir, p.name ASC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Get categories for filter
    $stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();

    // Stock status breakdown (real data, matches the badge rules used on every card)
    $stmt = $db->query("
        SELECT
            COUNT(*) as total_products,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN stock_quantity > reorder_level THEN 1 ELSE 0 END) as in_stock_count
        FROM products
        WHERE is_active = 1
    ");
    $stockStats = $stmt->fetch();

    Response::success([
        'products' => $products,
        'categories' => $categories,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'sort' => [
            'sort_by' => $sortBy,
            'sort_dir' => strtolower($sortDir)
        ],
        'stats' => [
            'total_products' => (int)($stockStats['total_products'] ?? 0),
            'in_stock_count' => (int)($stockStats['in_stock_count'] ?? 0),
            'low_stock_count' => (int)($stockStats['low_stock_count'] ?? 0),
            'out_of_stock_count' => (int)($stockStats['out_of_stock_count'] ?? 0)
        ]
    ], 'Inventory fetched successfully');

} catch (Exception $e) {
    error_log('get_inventory.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
