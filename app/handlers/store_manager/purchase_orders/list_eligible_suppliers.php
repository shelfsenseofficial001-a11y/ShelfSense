<?php
// app/handlers/store_manager/purchase_orders/list_eligible_suppliers.php
// Given the store products (and quantities) added so far to a resupply
// request, returns only the suppliers who carry every one of them with
// enough quantity on file -- the list narrows live as items are added.
// GET ?items=[{"store_product_id":1,"quantity":5}, ...]

require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/SupplierProduct.php';

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

$raw = isset($_GET['items']) ? $_GET['items'] : '[]';
$items = json_decode($raw, true);
if (!is_array($items)) {
    Response::error('Invalid items payload', 400);
}

$storeProductQuantities = [];
foreach ($items as $item) {
    $storeProductId = intval($item['store_product_id'] ?? 0);
    $quantity = intval($item['quantity'] ?? 0);
    if ($storeProductId > 0 && $quantity > 0) {
        $storeProductQuantities[$storeProductId] = ['quantity' => $quantity];
    }
}

try {
    $model = new SupplierProduct();
    $eligible = $model->getEligibleSuppliers($storeProductQuantities);

    Response::success(['suppliers' => $eligible], count($eligible) . ' eligible supplier(s) found');
} catch (Exception $e) {
    error_log('list_eligible_suppliers.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
