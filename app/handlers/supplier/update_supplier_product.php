<?php
// app/handlers/supplier/update_supplier_product.php

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

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$price = isset($input['price']) ? floatval($input['price']) : 0;
$isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

if ($id <= 0 || empty($name) || $price <= 0) {
    Response::error('ID, name, and price are required', 400);
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();

    // Get supplier ID to verify ownership
    $userId = Auth::userId();
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    // Verify product belongs to supplier
    $productModel = new SupplierProduct();
    $existing = $productModel->getById($id);
    if (!$existing || $existing['supplier_id'] != $supplierId) {
        Response::forbidden('You do not own this product');
    }

    $result = $productModel->update($id, [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'is_active' => $isActive
    ]);

    if (!$result) {
        Response::error('Failed to update supplier product', 500);
    }

    $product = $productModel->getById($id);

    Response::success([
        'product' => $product
    ], 'Supplier product updated successfully');

} catch (Exception $e) {
    error_log('update_supplier_product.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}