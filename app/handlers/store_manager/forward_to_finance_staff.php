<?php
// app/handlers/store_manager/forward_to_finance_staff.php

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

    // Check requisition exists and is in correct state
    $stmt = $db->prepare("
        SELECT r.*, s.company_name 
        FROM store_requisitions r
        JOIN suppliers s ON r.supplier_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if (!$req) {
        Response::notFound('Requisition not found');
    }

    if ($req['status'] !== 'supplier_processed') {
        Response::error('Requisition must be processed by supplier before forwarding. Current status: ' . $req['status']);
    }

    // ✅ Update status to awaiting_finance_staff (now valid in ENUM)
    $stmt = $db->prepare("
        UPDATE store_requisitions 
        SET status = 'awaiting_finance_staff', updated_at = NOW() 
        WHERE id = ?
    ");
    $result = $stmt->execute([$id]);

    if (!$result || $stmt->rowCount() === 0) {
        error_log('❌ Failed to update requisition #' . $req['requisition_number'] . ' to awaiting_finance_staff');
        Response::error('Failed to update requisition status. Please try again.', 500);
    }

    error_log('✅ Requisition #' . $req['requisition_number'] . ' forwarded to Finance Staff');

    // ✅ Notify ALL ACTIVE FINANCE STAFF
    $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'finance_staff' AND is_active = 1");
    $stmt->execute();
    $financeStaff = $stmt->fetchAll();

    foreach ($financeStaff as $staff) {
        createNotification(
            $staff['user_id'],
            'invoice_forwarded',
            "Invoice for requisition #{$req['requisition_number']} has been forwarded. Supplier: {$req['company_name']}",
            "?page=finance_staff_requisitions"
        );
    }

    // Notify Store Manager that forwarding was successful
    createNotification(
        Auth::userId(),
        'invoice_forwarded_success',
        "Invoice for requisition #{$req['requisition_number']} has been forwarded to Finance Staff.",
        "?page=store_manager_requisitions"
    );

    Response::success([
        'requisition_id' => $id,
        'status' => 'awaiting_finance_staff'
    ], 'Invoice forwarded to Finance Staff.');

} catch (Exception $e) {
    error_log('❌ forward_to_finance_staff.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}