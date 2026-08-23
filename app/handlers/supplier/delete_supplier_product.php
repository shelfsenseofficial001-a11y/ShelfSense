<?php
// app/handlers/supplier/delete_supplier_product.php

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

if ($id <= 0) {
    Response::error('Invalid product ID', 400);
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();

    // Get supplier ID to verify ownership
    $userId = Auth::userId();
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    $productModel = new SupplierProduct();
    $existing = $productModel->getById($id);
    if (!$existing || $existing['supplier_id'] != $supplierId) {
        Response::forbidden('You do not own this product');
    }

    $result = $productModel->delete($id);

    if (!$result) {
        Response::error('Failed to delete supplier product', 500);
    }

    Response::success([], 'Supplier product deleted successfully');

} catch (Exception $e) {
    error_log('delete_supplier_product.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}