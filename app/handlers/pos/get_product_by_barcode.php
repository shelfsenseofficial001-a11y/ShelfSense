<?php
// app/handlers/pos/get_product_by_barcode.php

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

if (!Auth::isEmployee() && !Auth::isSuperAdmin() && !Auth::isStoreManager()) {
    Response::forbidden('Access denied. Employee role required.');
}

$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if (empty($barcode)) {
    Response::error('Barcode is required', 400);
}

try {
    $productModel = new Product();
    $product = $productModel->getByBarcode($barcode);

    if (!$product) {
        Response::error('Product not found', 404);
    }

    $product['image_url'] = $product['image_path'] 
        ? '/ShelfSense/public/' . $product['image_path'] 
        : '/ShelfSense/public/assets/images/placeholder-product.png';
    $product['stock_quantity'] = (int)$product['stock_quantity'];
    $product['price'] = (float)$product['price'];

    Response::success([
        'product' => $product
    ], 'Product found');

} catch (Exception $e) {
    error_log('get_product_by_barcode.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}