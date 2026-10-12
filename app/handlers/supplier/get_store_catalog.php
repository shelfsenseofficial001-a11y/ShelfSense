<?php
// app/handlers/supplier/get_store_catalog.php
// Lets a supplier explicitly pick which store product their listing maps to,
// instead of that link being guessed from an exact name match
// (SupplierProduct::resolveStoreProductId()).

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, name, barcode FROM products WHERE is_active = 1 ORDER BY name");
    Response::success(['products' => $stmt->fetchAll()], 'Store catalog fetched successfully');
} catch (Exception $e) {
    error_log('get_store_catalog.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
