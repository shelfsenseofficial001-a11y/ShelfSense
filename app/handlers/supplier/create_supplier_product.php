<?php
// app/handlers/supplier/create_supplier_product.php

require_once __DIR__ . '/../../models/SupplierProduct.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\SupplierProduct;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? trim($input['name']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$price = isset($input['price']) ? floatval($input['price']) : 0;

if (empty($name) || $price <= 0) {
    Response::error('Name and price are required', 400);
}
if (strlen($name) > 100) {
    Response::error('Name cannot exceed 100 characters', 400);
}
if (strlen($description) > 500) {
    Response::error('Description cannot exceed 500 characters', 400);
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();

    // Get supplier ID
    $userId = Auth::userId();
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    $productModel = new SupplierProduct();
    $result = $productModel->create([
        'supplier_id' => $supplierId,
        'name' => $name,
        'description' => $description,
        'price' => $price
    ]);

    if (!$result) {
        Response::error('Failed to create supplier product', 500);
    }

    $id = \App\Core\Database::getInstance()->lastInsertId();
    $product = $productModel->getById($id);

    Response::success([
        'product' => $product
    ], 'Supplier product created successfully');

} catch (Exception $e) {
    error_log('create_supplier_product.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}