<?php
// app/handlers/store_manager/get_product_suppliers.php
// Every supplier carrying one store product, under their own name/price --
// backs the Inventory product-detail summary (click a card to see it).

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/SupplierProduct.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\SupplierProduct;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($productId <= 0) {
    Response::error('Missing product id', 400);
}

try {
    $supplierProductModel = new SupplierProduct();
    Response::success([
        'suppliers' => $supplierProductModel->getByStoreProductId($productId)
    ], 'Suppliers fetched successfully');
} catch (Exception $e) {
    error_log('get_product_suppliers.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
