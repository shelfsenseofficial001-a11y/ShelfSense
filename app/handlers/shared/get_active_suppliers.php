<?php
// app/handlers/shared/get_active_suppliers.php
// Minimal active-suppliers list, for the Product Proposal form's supplier picker.

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

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, company_name FROM suppliers WHERE is_active = 1 ORDER BY company_name");
    Response::success(['suppliers' => $stmt->fetchAll()], 'Suppliers fetched successfully');
} catch (Exception $e) {
    error_log('get_active_suppliers.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
