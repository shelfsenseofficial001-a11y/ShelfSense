<?php
// app/handlers/store_manager/update_product.php

require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
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
$stockQuantity = isset($input['stock_quantity']) ? intval($input['stock_quantity']) : 0;
$reorderLevel = isset($input['reorder_level']) ? intval($input['reorder_level']) : 5;
$isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

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
if ($stockQuantity < 0) {
    Response::error('Stock quantity cannot be negative', 400);
}
if ($reorderLevel < 0) {
    Response::error('Reorder level cannot be negative', 400);
}

try {
    $productModel = new Product();
    $existing = $productModel->getById($id);
    if (!$existing) {
        Response::error('Product not found', 404);
    }

    $result = $productModel->update($id, [
        'name' => $name,
        'description' => $description,
        'category_id' => $categoryId,
        'price' => $price,
        'cost' => $cost,
        'discount_value' => $discountValue,
        'discount_type' => $discountType,
        'stock_quantity' => $stockQuantity,
        'reorder_level' => $reorderLevel,
        'is_active' => $isActive
    ]);

    if (!$result) {
        Response::error('Failed to update product', 500);
    }

    $product = $productModel->getById($id);

    Response::success([
        'product' => $product
    ], 'Product updated successfully');

} catch (Exception $e) {
    error_log('update_product.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
