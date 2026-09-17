<?php
// app/handlers/store_manager/create_deal.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Deal.php';
require_once __DIR__ . '/../../models/Product.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Deal;
use App\Models\Product;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? trim($input['name']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$price = isset($input['price']) ? floatval($input['price']) : 0;
$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

if (empty($name)) {
    Response::error('Deal name is required', 400);
}
if ($price <= 0) {
    Response::error('Deal price must be greater than zero', 400);
}
if (count($items) < 2) {
    Response::error('A bundle needs at least 2 products', 400);
}

$cleanItems = [];
$seenProductIds = [];
foreach ($items as $item) {
    $productId = isset($item['product_id']) ? intval($item['product_id']) : 0;
    $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;

    if ($productId <= 0) {
        Response::error('Invalid product in bundle', 400);
    }
    if ($quantity <= 0 || $quantity > 99) {
        Response::error('Quantity must be between 1 and 99', 400);
    }
    if (in_array($productId, $seenProductIds, true)) {
        Response::error('Each product can only appear once in a bundle', 400);
    }
    $seenProductIds[] = $productId;
    $cleanItems[] = ['product_id' => $productId, 'quantity' => $quantity];
}

try {
    $productModel = new Product();
    foreach ($cleanItems as $item) {
        if (!$productModel->getById($item['product_id'])) {
            Response::error('One of the selected products no longer exists', 400);
        }
    }

    $dealModel = new Deal();
    $dealId = $dealModel->create([
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'is_active' => 1,
        'created_by' => Auth::userId()
    ], $cleanItems);

    Response::success(['deal' => $dealModel->getById($dealId)], 'Deal created successfully');

} catch (Exception $e) {
    error_log('create_deal.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
