<?php
// app/handlers/pos/get_products.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Product.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Product;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Allow Employees and SuperAdmins
if (!Auth::isEmployee() && !Auth::isSuperAdmin() && !Auth::isStoreManager()) {
    Response::forbidden('Access denied. Employee role required.');
}

try {
    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? intval($_GET['category']) : 0;

    if (strlen($search) > 100) {
        Response::error('Search term cannot exceed 100 characters.', 400);
    }

    $filters = [];
    if (!empty($search)) {
        $filters['search'] = $search;
    }
    if ($category > 0) {
        $filters['category_id'] = $category;
    }

    $productModel = new Product();
    $result = $productModel->getAll($page, $limit, $filters);

    foreach ($result['products'] as &$product) {
        $product['image_url'] = $product['image_path'] 
            ? '/ShelfSense/public/' . $product['image_path'] 
            : '/ShelfSense/public/assets/images/placeholder-product.png';
        $product['stock_quantity'] = (int)$product['stock_quantity'];
        $product['price'] = (float)$product['price'];
    }

    Response::success([
        'products' => $result['products'],
        'pagination' => $result['pagination']
    ], 'Products fetched successfully');

} catch (Exception $e) {
    error_log('get_products.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}