<?php
// app/handlers/supplier/process_requisition.php
//
// Rejects an incoming requisition. "Accepting" a requisition is realized by
// creating an invoice for it (see create_invoice.php) — that is the workflow
// transition actually wired end-to-end in this app (pending_supplier /
// sent_to_supplier -> supplier_processed). There is no separate accept step
// here to avoid a duplicate/competing transition to the same status.
//
// NOTE ON SCHEMA: store_requisitions.status has no valid "rejected by
// supplier" value (only 'finance_rejected' exists, which means something
// different). Until that's added, a rejection is recorded as a note on the
// requisition plus a notification to the Store Manager, and the requisition's
// status is left unchanged. See the project report for the proposed schema
// addition that would let this set a real status instead.

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

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($id <= 0) {
    Response::error('Invalid requisition ID', 400);
}

if (empty($reason)) {
    Response::error('A rejection reason is required.', 400, ['reason' => 'A rejection reason is required.']);
}

if (strlen($reason) > 500) {
    Response::error('Rejection reason cannot exceed 500 characters.', 400);
}

const REJECTION_MARKER = '[SUPPLIER REJECTED]';

try {
    $db = Database::getInstance()->getConnection();

    $userId = Auth::userId();
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([$userId]);
    $supplier = $stmt->fetch();
    $supplierId = $supplier ? $supplier['id'] : $userId;

    $stmt = $db->prepare("
        SELECT r.*, u.user_id as store_manager_id
        FROM store_requisitions r
        JOIN users u ON r.created_by = u.user_id
        WHERE r.id = ? AND r.supplier_id = ?
    ");
    $stmt->execute([$id, $supplierId]);
    $req = $stmt->fetch();

    if (!$req) {
        Response::notFound('Requisition not found or not assigned to you');
    }

    if (!in_array($req['status'], ['pending_supplier', 'sent_to_supplier'])) {
        Response::error('This requisition can no longer be rejected. Current status: ' . $req['status'], 400);
    }

    if (strpos((string)$req['notes'], REJECTION_MARKER) !== false) {
        Response::error('This requisition has already been rejected.', 400);
    }

    $note = REJECTION_MARKER . ' ' . $reason;
    $newNotes = trim(($req['notes'] ? $req['notes'] . "\n" : '') . $note);

    $stmt = $db->prepare("UPDATE store_requisitions SET notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newNotes, $id]);

    createNotification(
        $req['store_manager_id'],
        'requisition_rejected',
        "Supplier rejected requisition #{$req['requisition_number']}. Reason: {$reason}",
        "?page=store_manager_requisitions"
    );

    Response::success([
        'requisition_id' => $id,
        'rejected' => true
    ], 'Requisition rejected. The Store Manager has been notified.');

} catch (Exception $e) {
    error_log('process_requisition.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
