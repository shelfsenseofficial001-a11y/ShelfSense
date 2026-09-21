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

    // Every active store product is requestable regardless of which
    // supplier(s) carry it -- the actual supplier match/pricing happens
    // afterward, per the picked items, via api_sm_list_eligible_suppliers
    // (SupplierProduct::getEligibleSuppliers()). This used to also try to
    // pre-filter by one supplier via a name match against
    // supplier_products, which both duplicated that later step and broke
    // outright once a store product's display name diverged from its
    // linked supplier_products.name (see SupplierProduct::create()/
    // update(), which link the two by store_product_id, not by name).
    $stmt = $db->query("
        SELECT id as store_product_id, name, barcode, stock_quantity, reorder_level, price as store_price, image_path
        FROM products
        WHERE is_active = 1
        ORDER BY name
    ");
    $products = $stmt->fetchAll();

    Response::success([
        'products' => $products
    ], 'Products fetched successfully');

} catch (Exception $e) {
    error_log('get_products_for_requisition.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
