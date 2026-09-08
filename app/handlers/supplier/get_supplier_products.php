<?php
// app/handlers/supplier/get_supplier_products.php

require_once __DIR__ . '/../../models/SupplierProduct.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\SupplierProduct;

if (!function_exists('supplier_products_build_data')) {
/**
 * Builds a page of a supplier's product catalog + stats. Shared by the
 * API endpoint (filter/pagination/search) and the Products page's first
 * paint.
 */
function supplier_products_build_data(PDO $db, int $supplierId, int $page, int $limit, string $search, string $statusFilter): array {
    $productModel = new SupplierProduct();
    $result = $productModel->getAll($supplierId, $page, $limit, $search, $statusFilter);

    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        FROM supplier_products
        WHERE supplier_id = ?
    ");
    $stmt->execute([$supplierId]);
    $stats = $stmt->fetch();

    return [
        'products' => $result['products'],
        'pagination' => $result['pagination'],
        'stats' => [
            'total' => (int)($stats['total'] ?? 0),
            'active' => (int)($stats['active'] ?? 0),
            'inactive' => (int)($stats['inactive'] ?? 0)
        ]
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login to access this resource');
    }

    if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
        Response::forbidden('Access denied. Supplier role required.');
    }

    try {
        $db = \App\Core\Database::getInstance()->getConnection();

        $userId = Auth::userId();
        $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
        $stmt->execute([$userId]);
        $supplier = $stmt->fetch();
        $supplierId = $supplier ? $supplier['id'] : $userId;

        $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

        Response::success(
            supplier_products_build_data($db, $supplierId, $page, $limit, $search, $statusFilter),
            'Supplier products fetched successfully'
        );
    } catch (Exception $e) {
        error_log('get_supplier_products.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}