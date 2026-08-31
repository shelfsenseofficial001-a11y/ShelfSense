<?php
// app/handlers/finance/head/approve_payment_request.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PaymentRequest.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../models/StoreRequisition.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
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
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($paymentRequestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'reject' && $reason === '') {
    Response::error('Rejection reason is required', 400);
}
if (strlen($reason) > 500 || strlen($notes) > 500) {
    Response::error('Notes/reason cannot exceed 500 characters', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // Row-lock the payment request for the duration of this transaction so a
    // double-click, page refresh, or genuinely concurrent request from another
    // tab cannot both pass the "still pending" check before either commits.
    $stmt = $db->prepare("SELECT * FROM payment_requests WHERE id = ? FOR UPDATE");
    $stmt->execute([$paymentRequestId]);
    $request = $stmt->fetch();

    if (!$request) {
        $db->rollBack();
        Response::error('Payment request not found.', 404);
    }
    if ($request['status'] !== 'pending') {
        $db->rollBack();
        Response::error('This payment request has already been ' . $request['status'] . ' — it cannot be processed again.', 400);
    }

    $requisitionModel = new StoreRequisition();
    $requisition = $requisitionModel->getById($request['requisition_id']);

    if (!$requisition) {
        $db->rollBack();
        Response::error('Requisition not found.', 400);
    }
    if ($requisition['status'] !== 'awaiting_finance') {
        // Defense in depth: the requisition's own status should always track the
        // payment request's, but never trust that without checking.
        $db->rollBack();
        Response::error('Requisition is not currently awaiting Finance Head approval. Current status: ' . $requisition['status'], 400);
    }

    $department = $requisition['department'] ?: 'store';
    $monthYear = $requisition['budget_month_year'] ?: CutoffPeriod::getCurrentKey();
    $amount = (float)$requisition['total'];

    if ($action === 'approve') {
        // Recalculated fresh, right now, under the row lock — never trust the
        // budget_exceeded flag the client (or even the original request) carried.
        // Exclude this same requisition's own (still-pending) reservation from
        // "reserved" so its amount isn't subtracted from availability twice.
        $budgetModel = new Budget();
        $budgetStatus = $budgetModel->getBudgetStatus($department, $monthYear, $amount, $request['requisition_id']);

        if ($budgetStatus['exceeded'] && $notes === '') {
            $db->rollBack();
            Response::error(
                'This request exceeds the available budget (short by ₱' . number_format($budgetStatus['shortfall'], 2) . '). '
                    . 'A Finance Head justification is required before approving an over-budget request.',
                400,
                ['notes' => 'Justification is required for over-budget approval.']
            );
        }

        // 1. Update payment request status + approval notes/justification.
        $stmt = $db->prepare("
            UPDATE payment_requests
            SET status = 'approved', approved_by = ?, approved_at = NOW(), approval_notes = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([Auth::userId(), $notes !== '' ? $notes : null, $paymentRequestId]);
        if ($stmt->rowCount() === 0) {
            // Someone else resolved it between our lock check and this update — should be
            // impossible under FOR UPDATE, but fail safely instead of proceeding.
            $db->rollBack();
            Response::error('This payment request has already been processed.', 400);
        }

        // 2. Get invoice
        $stmt = $db->prepare("SELECT * FROM supplier_invoices WHERE id = ?");
        $stmt->execute([$request['supplier_invoice_id']]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            throw new Exception('No invoice found for this requisition.');
        }

        // 3. Update invoice status to paid
        $stmt = $db->prepare("UPDATE supplier_invoices SET status = 'paid', paid_by = ?, paid_at = NOW() WHERE id = ?");
        $stmt->execute([Auth::userId(), $invoice['id']]);

        // 4. Insert payment record. uniq_invoice_payment (UNIQUE on supplier_invoice_id)
        // is the hard DB-level guarantee against a duplicate payment for this invoice —
        // if a race slipped past the row lock above, this INSERT throws and the whole
        // transaction (including the status update above) rolls back.
        $stmt = $db->prepare("
            INSERT INTO payments (supplier_invoice_id, amount, payment_method, reference_number, paid_by, paid_at, notes)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $invoice['id'],
            $amount,
            'bank_transfer',
            'AUTO-' . date('YmdHis'),
            Auth::userId(),
            'Auto-recorded after Finance Head approval'
        ]);

        // 5. Update requisition status to PAID
        $requisitionModel->updateStatus($request['requisition_id'], 'paid');

        // 6. Convert reservation to used budget. Reserved is computed live from
        // pending payment_requests, so now that this one is 'approved' it naturally
        // drops out of "reserved" and updateUsedBudget() picks it up as "used" via
        // the requisition's now-'paid' status — no double count possible.
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
            'requisition_status' => 'paid',
            'budget_exceeded' => $budgetStatus['exceeded']
        ], 'Payment request approved. Payment recorded. Supplier notified.' . ($budgetStatus['exceeded'] ? ' Approved over budget.' : ''));

    } else { // reject
        $stmt = $db->prepare("
            UPDATE payment_requests
            SET status = 'rejected', rejection_reason = ?
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$reason, $paymentRequestId]);
        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            Response::error('This payment request has already been processed.', 400);
        }

        // Return to Finance Staff for correction. No invoice/payment change — and the
        // rejected request immediately stops counting toward "reserved" budget since
        // that figure is always computed live from status = 'pending' rows.
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
        ], 'Payment request rejected. Returned to Finance Staff for correction.');
    }

} catch (\PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($e->getCode() === '23000') {
        error_log('approve_payment_request.php duplicate-key race: ' . $e->getMessage());
        Response::error('This payment has already been recorded for this invoice.', 400);
    }
    error_log('approve_payment_request.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('approve_payment_request.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
