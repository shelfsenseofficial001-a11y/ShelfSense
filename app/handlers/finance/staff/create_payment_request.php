<?php
// app/handlers/finance/staff/create_payment_request.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/StoreRequisition.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../models/PaymentRequest.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\StoreRequisition;
use App\Models\Budget;
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
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$justification = isset($input['justification']) ? trim($input['justification']) : '';

if ($requisitionId <= 0) {
    Response::error('Invalid requisition ID', 400);
}
if (strlen($notes) > 500) {
    Response::error('Notes cannot exceed 500 characters', 400);
}
if (strlen($justification) > 500) {
    Response::error('Justification cannot exceed 500 characters', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    $requisitionModel = new StoreRequisition();
    $requisition = $requisitionModel->getById($requisitionId);

    if (!$requisition) {
        Response::notFound('Requisition not found');
    }

    if ($requisition['status'] !== 'awaiting_finance_staff') {
        Response::error('Requisition is not awaiting finance staff. Current status: ' . $requisition['status'], 400);
    }

    // Check if payment request already exists
    $paymentRequestModel = new PaymentRequest();
    $existing = $paymentRequestModel->getForRequisition($requisitionId);
    if ($existing && $existing['status'] === 'pending') {
        Response::error('A payment request is already pending for this requisition.', 400);
    }

    // Get invoice
    $stmt = $db->prepare("SELECT * FROM supplier_invoices WHERE requisition_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$requisitionId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        Response::error('No invoice found for this requisition.', 400);
    }

    // Budget check — department comes from the requisition itself, never hard-coded,
    // and never trusted from the client. Amount is the authoritative requisition total
    // (server-computed at creation from real item totals), not anything submitted here.
    $budgetModel = new Budget();
    $department = $requisition['department'] ?? 'store';
    $monthYear = $requisition['budget_month_year'] ?: date('Y-m');
    $total = floatval($requisition['total']);

    // Recalculated fresh, right before creating the request, so a concurrently-created
    // reservation is accounted for. The unique DB constraint on payment_requests
    // (one pending request per requisition) is the hard guarantee against races; this
    // recalculation keeps the budget numbers shown to the user accurate at submit time.
    $budgetStatus = $budgetModel->getBudgetStatus($department, $monthYear, $total);
    $budgetExceeded = $budgetStatus['exceeded'];

    if ($budgetExceeded && empty($justification)) {
        Response::error(
            'This request exceeds the available budget. A justification is required before it can be sent for Finance Head approval.',
            400,
            ['justification' => 'Justification is required for budget-exceeded requests.']
        );
    }

    $budgetExceededReason = $budgetExceeded
        ? "The requisition total (₱" . number_format($total, 2) . ") exceeds the available budget (₱" . number_format($budgetStatus['available'], 2) . ") by ₱" . number_format($budgetStatus['shortfall'], 2) . "."
            . ($justification !== '' ? " Justification: {$justification}" : '')
        : null;

    // Create payment request. The unique key on active_requisition_lock (see schema)
    // makes this atomically safe against a concurrent duplicate — if one slips through
    // the earlier check-then-insert race, the INSERT itself fails instead of creating
    // two pending requests for the same requisition.
    $paymentRequestId = $paymentRequestModel->create([
        'requisition_id' => $requisitionId,
        'supplier_invoice_id' => $invoice['id'],
        'requested_by' => Auth::userId(),
        'notes' => $notes,
        'budget_checked' => 1,
        'budget_exceeded' => $budgetExceeded ? 1 : 0,
        'budget_exceeded_reason' => $budgetExceededReason
    ]);

    if (!$paymentRequestId) {
        Response::error('Failed to create payment request.', 500);
    }

    // Update requisition status to awaiting_finance (for Finance Head)
    $requisitionModel->updateStatus($requisitionId, 'awaiting_finance');

    // Notify Finance Head
    $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'finance_head' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $financeHead = $stmt->fetch();
    if ($financeHead) {
        createNotification(
            $financeHead['user_id'],
            'payment_request_pending',
            "Payment request for requisition #{$requisition['requisition_number']} is pending approval. Amount: ₱" . number_format($total, 2),
            "?page=finance_head_payment_requests"
        );
    }

    $db->commit();

    Response::success([
        'payment_request_id' => $paymentRequestId,
        'budget_exceeded' => $budgetExceeded,
        'budget_exceeded_reason' => $budgetExceededReason,
        'budget_available' => $budgetStatus['available']
    ], 'Payment request created. Awaiting Finance Head approval.' . ($budgetExceeded ? ' Budget exceeded.' : ''));

} catch (\PDOException $e) {
    $db->rollBack();
    if ($e->getCode() === '23000' && strpos($e->getMessage(), 'uniq_active_requisition') !== false) {
        // Another request beat this one to it between our check and the insert.
        Response::error('A payment request is already pending for this requisition.', 400);
    }
    error_log('create_payment_request.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
} catch (Exception $e) {
    $db->rollBack();
    error_log('create_payment_request.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}