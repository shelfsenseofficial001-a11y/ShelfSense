<?php
// app/handlers/store_manager/get_products_for_requisition.php

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

try {
    $db = Database::getInstance()->getConnection();

    // All active suppliers, for the supplier-selection dropdown
    $stmt = $db->query("SELECT id, company_name FROM suppliers WHERE is_active = 1 ORDER BY company_name");
    $suppliers = $stmt->fetchAll();

    $requestedSupplierId = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
    $supplierId = null;

    if ($requestedSupplierId > 0) {
        foreach ($suppliers as $s) {
            if ((int)$s['id'] === $requestedSupplierId) {
                $supplierId = $requestedSupplierId;
                break;
            }
        }
    }
    // Fall back to the first active supplier if none was requested (or the request was invalid)
    if ($supplierId === null && !empty($suppliers)) {
        $supplierId = (int)$suppliers[0]['id'];
    }

    if (!$supplierId) {
        Response::success([
            'products' => [],
            'supplier' => null,
            'suppliers' => $suppliers
        ], 'No supplier found. Please add a supplier first.');
        exit;
    }

    // Store products with THIS supplier's matching product info
    // (name-based matching is the existing linkage used across the app between
    // store products and supplier products — preserved as-is, just filtered by supplier now)
    $stmt = $db->prepare("
        SELECT
            p.id as store_product_id,
            p.name,
            p.barcode,
            p.stock_quantity,
            p.reorder_level,
            p.price as store_price,
            p.image_path,
            sp.id as supplier_product_id,
            sp.price as supplier_price,
            sp.supplier_id
        FROM products p
        LEFT JOIN supplier_products sp ON p.name = sp.name AND sp.supplier_id = ? AND sp.is_active = 1
        WHERE p.is_active = 1
        ORDER BY p.name
    ");
    $stmt->execute([$supplierId]);
    $products = $stmt->fetchAll();

    $supplierName = null;
    foreach ($suppliers as $s) {
        if ((int)$s['id'] === $supplierId) {
            $supplierName = $s['company_name'];
            break;
        }
    }

    Response::success([
        'products' => $products,
        'supplier' => [
            'id' => $supplierId,
            'company_name' => $supplierName
        ],
        'suppliers' => $suppliers
    ], 'Products fetched successfully');

} catch (Exception $e) {
    error_log('get_products_for_requisition.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
