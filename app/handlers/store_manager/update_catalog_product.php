<?php
// app/handlers/store_manager/update_catalog_product.php
// Upserts a Catalog & Deals override for a product -- name, description,
// category, price, cost, and discount only. This never touches the
// products row itself, so Inventory (which reads products directly) is
// never affected by an edit made here. Stock/reorder/status stay
// exclusively Inventory's job and aren't editable from this endpoint.

require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Product;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$categoryId = isset($input['category_id']) && intval($input['category_id']) > 0 ? intval($input['category_id']) : null;
$price = isset($input['price']) ? floatval($input['price']) : 0;
$cost = isset($input['cost']) && $input['cost'] !== '' ? floatval($input['cost']) : null;
$discountType = isset($input['discount_type']) && $input['discount_type'] === 'fixed' ? 'fixed' : 'percent';
$discountValue = isset($input['discount_value']) ? floatval($input['discount_value']) : 0;

if ($id <= 0 || empty($name) || $price <= 0) {
    Response::error('ID, name, and a price greater than zero are required', 400);
}
if ($discountValue < 0) {
    Response::error('Discount cannot be negative', 400);
}
if ($discountType === 'percent' && $discountValue > 100) {
    Response::error('Percentage discount cannot exceed 100', 400);
}
if ($discountType === 'fixed' && $discountValue > $price) {
    Response::error('Fixed discount cannot exceed the price', 400);
}

try {
    $productModel = new Product();
    $existing = $productModel->getById($id);
    if (!$existing) {
        Response::error('Product not found', 404);
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO catalog_overrides (product_id, name, description, category_id, price, cost, discount_value, discount_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            category_id = VALUES(category_id),
            price = VALUES(price),
            cost = VALUES(cost),
            discount_value = VALUES(discount_value),
            discount_type = VALUES(discount_type)
    ");
    $stmt->execute([$id, $name, $description, $categoryId, $price, $cost, $discountValue, $discountType]);

    $product = $productModel->getById($id);

    Response::success([
        'product' => $product
    ], 'Catalog listing updated successfully');

} catch (Exception $e) {
    error_log('update_catalog_product.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
