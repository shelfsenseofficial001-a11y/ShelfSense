<?php
// app/handlers/finance/staff/record_payment.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/StoreRequisition.php';
require_once __DIR__ . '/../../../models/PaymentRequest.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\StoreRequisition;
use App\Models\PaymentRequest;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
$paymentMethod = isset($input['payment_method']) ? trim($input['payment_method']) : 'bank_transfer';
$referenceNumber = isset($input['reference_number']) ? trim($input['reference_number']) : '';

if ($requisitionId <= 0) {
    Response::error('Invalid requisition ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $requisitionModel = new StoreRequisition();
    $requisition = $requisitionModel->getById($requisitionId);

    if (!$requisition) {
        Response::notFound('Requisition not found');
    }

    if ($requisition['status'] !== 'finance_approved') {
        Response::error('Requisition is not approved for payment. Current status: ' . $requisition['status'], 400);
    }

    // Get invoice
    $stmt = $db->prepare("SELECT * FROM supplier_invoices WHERE requisition_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$requisitionId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        Response::error('No invoice found for this requisition.', 400);
    }

    // Update requisition status to paid
    $requisitionModel->updateStatus($requisitionId, 'paid');

    // Update invoice status to paid
    $stmt = $db->prepare("UPDATE supplier_invoices SET status = 'paid', paid_by = ?, paid_at = NOW() WHERE id = ?");
    $stmt->execute([Auth::userId(), $invoice['id']]);

    // Insert payment record
    $stmt = $db->prepare("
        INSERT INTO payments (supplier_invoice_id, amount, payment_method, reference_number, paid_by, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $invoice['id'],
        $requisition['total'],
        $paymentMethod,
        $referenceNumber,
        Auth::userId(),
        'Payment recorded'
    ]);

    // Update payment request status to approved (if exists)
    $paymentRequestModel = new PaymentRequest();
    $paymentRequest = $paymentRequestModel->getForRequisition($requisitionId);
    if ($paymentRequest && $paymentRequest['status'] === 'approved') {
        // Already approved, nothing to do
    }

    // Notify Store Manager
    createNotification(
        $requisition['created_by'],
        'payment_recorded',
        "Payment has been recorded for requisition #{$requisition['requisition_number']}. The supplier has been notified to ship.",
        "?page=store_manager_requisitions"
    );

    $db->commit();

    Response::success([
        'requisition_id' => $requisitionId,
        'status' => 'paid'
    ], 'Payment recorded successfully. Supplier has been notified.');

} catch (Exception $e) {
    $db->rollBack();
    error_log('record_payment.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}