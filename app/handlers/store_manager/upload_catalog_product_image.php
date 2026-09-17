<?php
// app/handlers/store_manager/upload_catalog_product_image.php
// Uploads a Catalog & Deals-only image override -- stored in
// catalog_overrides, never touches products.image_path (Inventory keeps
// showing the original image regardless of what Catalog sets here).

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

$baseDir = __DIR__ . '/../../../public/uploads/catalog/';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$filename = 'catalog_' . $productId . '_' . time() . '.' . $ext;
$dest = $baseDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    Response::error('Failed to save image. Check permissions.', 500);
}

$relativePath = 'uploads/catalog/' . $filename;

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT image_path FROM catalog_overrides WHERE product_id = ?");
    $stmt->execute([$productId]);
    $oldOverridePath = $stmt->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO catalog_overrides (product_id, image_path) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE image_path = VALUES(image_path)
    ");
    if (!$stmt->execute([$productId, $relativePath])) {
        unlink($dest);
        Response::error('Database update failed', 500);
    }

    if ($oldOverridePath && $oldOverridePath !== $relativePath) {
        $oldFile = __DIR__ . '/../../../public/' . $oldOverridePath;
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    Response::success(['image_path' => $relativePath], 'Catalog image updated');
} catch (Exception $e) {
    error_log('upload_catalog_product_image.php error: ' . $e->getMessage());
    @unlink($dest);
    Response::error('Error: ' . $e->getMessage());
}
