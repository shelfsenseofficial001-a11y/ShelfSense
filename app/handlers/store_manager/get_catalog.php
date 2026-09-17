<?php
// app/handlers/store_manager/get_catalog.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

if (!function_exists('sm_catalog_build_data')) {
/**
 * Builds a page of the product catalog + categories + deal stats. Shared
 * by the API endpoint (filter/sort/pagination) and the Catalog & Deals
 * page's first paint. Unlike Inventory, this is about pricing/discounts,
 * not stock levels -- no stock filter, no stock stats.
 *
 * Every field here is the *effective* value -- a Catalog & Deals override
 * (catalog_overrides) COALESCEd over the base product row -- since this
 * page both displays and edits that effective view. Inventory queries the
 * base products row directly and never sees these overrides (see
 * get_inventory.php); that's the whole point of the split.
 */
function sm_catalog_build_data(PDO $db, int $page, int $limit, string $search, int $category, string $dealStatus, string $sortBy, string $sortDir): array {
    // Whitelisted sort columns — never build ORDER BY from raw user input
    $sortableColumns = [
        'name' => 'name',
        'category' => 'category_name',
        'price' => 'price',
        'discount' => 'discount_value',
    ];
    if (!isset($sortableColumns[$sortBy])) {
        $sortBy = 'name';
    }
    $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

    $where = "p.is_active = 1";
    $params = [];

    if (!empty($search)) {
        $where .= " AND (COALESCE(co.name, p.name) LIKE ? OR p.barcode LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if ($category > 0) {
        $where .= " AND COALESCE(co.category_id, p.category_id) = ?";
        $params[] = $category;
    }

    if ($dealStatus === 'on_sale') {
        $where .= " AND COALESCE(co.discount_value, p.discount_value) > 0";
    } elseif ($dealStatus === 'regular') {
        $where .= " AND COALESCE(co.discount_value, p.discount_value) = 0";
    }

    $offset = ($page - 1) * $limit;

    // Count
    $countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN catalog_overrides co ON co.product_id = p.id WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    // Get products (effective values) with category name
    $orderColumn = $sortableColumns[$sortBy];
    $sql = "
        SELECT
            p.id,
            p.barcode,
            COALESCE(co.name, p.name) AS name,
            COALESCE(co.description, p.description) AS description,
            COALESCE(co.price, p.price) AS price,
            COALESCE(co.cost, p.cost) AS cost,
            COALESCE(co.discount_value, p.discount_value) AS discount_value,
            COALESCE(co.discount_type, p.discount_type) AS discount_type,
            COALESCE(co.image_path, p.image_path) AS image_path,
            c.name as category_name,
            c.id as category_id
        FROM products p
        LEFT JOIN catalog_overrides co ON co.product_id = p.id
        LEFT JOIN categories c ON c.id = COALESCE(co.category_id, p.category_id)
        WHERE $where
        ORDER BY $orderColumn $sortDir, name ASC
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

    // Deal breakdown (active products only, regardless of the current filter)
    $stmt = $db->query("
        SELECT
            COUNT(*) as total_products,
            SUM(CASE WHEN COALESCE(co.discount_value, p.discount_value) > 0 THEN 1 ELSE 0 END) as on_sale_count,
            SUM(CASE WHEN COALESCE(co.discount_value, p.discount_value) = 0 THEN 1 ELSE 0 END) as regular_count
        FROM products p
        LEFT JOIN catalog_overrides co ON co.product_id = p.id
        WHERE p.is_active = 1
    ");
    $dealStats = $stmt->fetch();

    return [
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
            'total_products' => (int)($dealStats['total_products'] ?? 0),
            'on_sale_count' => (int)($dealStats['on_sale_count'] ?? 0),
            'regular_count' => (int)($dealStats['regular_count'] ?? 0)
        ]
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login');
    }

    if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
        Response::forbidden('Access denied. Store Manager role required.');
    }

    try {
        $db = Database::getInstance()->getConnection();

        $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 30;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $category = isset($_GET['category']) ? intval($_GET['category']) : 0;
        $dealStatus = isset($_GET['deal_status']) ? trim($_GET['deal_status']) : '';
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';
        $sortDir = isset($_GET['sort_dir']) ? $_GET['sort_dir'] : 'asc';

        Response::success(
            sm_catalog_build_data($db, $page, $limit, $search, $category, $dealStatus, $sortBy, $sortDir),
            'Catalog fetched successfully'
        );
    } catch (Exception $e) {
        error_log('get_catalog.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
