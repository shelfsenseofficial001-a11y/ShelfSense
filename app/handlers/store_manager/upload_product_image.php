<?php
// app/handlers/store_manager/upload_product_image.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Product.php';

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

$productId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($productId <= 0) {
    Response::error('Product ID is required', 400);
}

$productModel = new Product();
$existing = $productModel->getById($productId);
if (!$existing) {
    Response::error('Product not found', 404);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['image']['error'] ?? 'unknown';
    Response::error('File upload error. Code: ' . $err, 400);
}

$file = $_FILES['image'];

if ($file['size'] > 3 * 1024 * 1024) {
    Response::error('Image must be under 3MB', 400);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowed)) {
    Response::error('Invalid file type. Use JPG, PNG, or WEBP.', 400);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
    Response::error('Invalid image file', 400);
}

$baseDir = __DIR__ . '/../../../public/uploads/products/';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$filename = 'product_' . $productId . '_' . time() . '.' . $ext;
$dest = $baseDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    Response::error('Failed to save image. Check permissions.', 500);
}

$relativePath = 'uploads/products/' . $filename;

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("UPDATE products SET image_path = ? WHERE id = ?");
if (!$stmt->execute([$relativePath, $productId])) {
    unlink($dest);
    Response::error('Database update failed', 500);
}

$oldPath = $existing['image_path'] ?? null;
if ($oldPath && $oldPath !== $relativePath) {
    $oldFile = __DIR__ . '/../../../public/' . $oldPath;
    if (is_file($oldFile)) {
        @unlink($oldFile);
    }
}

Response::success(['image_path' => $relativePath], 'Product image updated');
