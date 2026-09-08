<?php
// app/handlers/shared/get_categories.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

if (!function_exists('shared_categories_build_data')) {
/**
 * Builds the active category list. Shared by the API endpoint and any
 * page's first paint that needs it (POS Checkout, Inventory, ...).
 */
function shared_categories_build_data(PDO $db): array {
    $stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    return ['categories' => $stmt->fetchAll()];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::posCheck() && !Auth::check()) {
        Response::unauthorized('Please login to access this resource');
    }

    try {
        $db = Database::getInstance()->getConnection();
        Response::success(shared_categories_build_data($db), 'Categories fetched successfully');
    } catch (Exception $e) {
        error_log('get_categories.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}