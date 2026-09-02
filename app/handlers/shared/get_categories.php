<?php
// app/handlers/shared/get_categories.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::posCheck() && !Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();

    Response::success([
        'categories' => $categories
    ], 'Categories fetched successfully');

} catch (Exception $e) {
    error_log('get_categories.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}