<?php
// app/handlers/store_manager/send_requisition_to_supplier.php

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

if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    Response::error('Invalid requisition ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT status FROM store_requisitions WHERE id = ?");
    $stmt->execute([$id]);
    $status = $stmt->fetch()['status'] ?? '';

    if (!in_array($status, ['draft', 'pending_supplier'])) {
        Response::error('Requisition cannot be sent to supplier. Current status: ' . $status);
    }

    $stmt = $db->prepare("
        UPDATE store_requisitions SET status = 'sent_to_supplier', updated_at = NOW() WHERE id = ?
    ");
    $stmt->execute([$id]);

    Response::success([
        'requisition_id' => $id,
        'status' => 'sent_to_supplier'
    ], 'Requisition sent to supplier successfully');

} catch (Exception $e) {
    error_log('send_requisition_to_supplier.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}