<?php
// app/handlers/finance/head/approve_payment_request.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PaymentRequest.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../models/StoreRequisition.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PaymentRequest;
use App\Models\Budget;
use App\Models\StoreRequisition;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$paymentRequestId = isset($input['payment_request_id']) ? intval($input['payment_request_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

if ($paymentRequestId <= 0 || !in_array($action, ['approve', 'reject'])) {
    Response::error('Invalid request', 400);
}

if ($action === 'reject' && empty($reason)) {
    Response::error('Rejection reason is required', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $paymentRequestModel = new PaymentRequest();
    $request = $paymentRequestModel->getById($paymentRequestId);

    if (!$request || $request['status'] !== 'pending') {
        Response::error('Payment request not found or already processed.', 400);
    }

    $requisitionModel = new StoreRequisition();
    $requisition = $requisitionModel->getById($request['requisition_id']);

    if (!$requisition) {
        Response::error('Requisition not found.', 400);
    }

    if ($action === 'approve') {
        // 1. Update payment request status
        $paymentRequestModel->updateStatus($paymentRequestId, 'approved', Auth::userId());

        // 2. Get invoice
        $stmt = $db->prepare("SELECT * FROM supplier_invoices WHERE requisition_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$request['requisition_id']]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            throw new Exception('No invoice found for this requisition.');
        }

        // 3. Update invoice status to paid
        $stmt = $db->prepare("UPDATE supplier_invoices SET status = 'paid', paid_by = ?, paid_at = NOW() WHERE id = ?");
        $stmt->execute([Auth::userId(), $invoice['id']]);

        // 4. Insert payment record
        $stmt = $db->prepare("
            INSERT INTO payments (supplier_invoice_id, amount, payment_method, reference_number, paid_by, paid_at, notes)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $invoice['id'],
            $requisition['total'],
            'bank_transfer',
            'AUTO-' . date('YmdHis'),
            Auth::userId(),
            'Auto-recorded after Finance Head approval'
        ]);

        // ✅ 5. Update requisition status to PAID
        $requisitionModel->updateStatus($request['requisition_id'], 'paid');

        // 6. Update budget used — department comes from the requisition's own record,
        // never hard-coded, so this can never post usage against the wrong department.
        $budgetModel = new Budget();
        $department = $requisition['department'] ?? 'store';
        $monthYear = $requisition['budget_month_year'] ?: date('Y-m');
        $budgetModel->updateUsedBudget($department, $monthYear);

        // 7. Notify Supplier (Ship Goods button will appear)
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = (SELECT email FROM suppliers WHERE id = ?) AND is_active = 1");
        $stmt->execute([$requisition['supplier_id']]);
        $supplierUser = $stmt->fetch();
        if ($supplierUser) {
            createNotification(
                $supplierUser['user_id'],
                'payment_completed',
                "Payment for requisition #{$requisition['requisition_number']} has been completed. Please ship the goods.",
                "?page=supplier_requisitions"
            );
        }

        // 8. Notify Finance Staff
        createNotification(
            $request['requested_by'],
            'payment_request_approved',
            "Payment request for requisition #{$requisition['requisition_number']} has been approved and recorded.",
            "?page=finance_staff_payment_requests"
        );

        // 9. Notify Store Manager
        createNotification(
            $requisition['created_by'],
            'payment_approved',
            "Payment for requisition #{$requisition['requisition_number']} has been approved. The supplier will ship the goods.",
            "?page=store_manager_requisitions"
        );

        $db->commit();

        Response::success([
            'payment_request_id' => $paymentRequestId,
            'status' => 'approved',
            'requisition_status' => 'paid'
        ], 'Payment request approved. Payment recorded. Supplier notified.');

    } else { // reject
        $paymentRequestModel->updateStatus($paymentRequestId, 'rejected', null, $reason);
        $requisitionModel->updateStatus($request['requisition_id'], 'awaiting_finance_staff');

        createNotification(
            $request['requested_by'],
            'payment_request_rejected',
            "Payment request for requisition #{$requisition['requisition_number']} has been rejected. Reason: " . $reason,
            "?page=finance_staff_payment_requests"
        );

        $db->commit();

        Response::success([
            'payment_request_id' => $paymentRequestId,
            'status' => 'rejected'
        ], 'Payment request rejected.');
    }

} catch (Exception $e) {
    $db->rollBack();
    error_log('❌ approve_payment_request.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}