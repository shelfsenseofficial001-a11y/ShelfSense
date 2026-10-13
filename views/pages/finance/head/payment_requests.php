<?php
$title = 'Requisitions & Payments - Finance Head';
$pageTitle = 'Requisitions & Payments';
$activePage = 'head_payment_requests';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20261013000002"></script>'
    . '<script src="/ShelfSense/public/assets/js/finance/head/payment_requests.js?v=20261013210000"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs mb-3" id="fhTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" id="tabPendingPos" data-bs-toggle="tab" data-bs-target="#reqTab" type="button">Pending Requisitions</button></li>
    <li class="nav-item"><button class="nav-link" id="tabPendingPoPayments" data-bs-toggle="tab" data-bs-target="#poPaymentTab" type="button">Pending PO Payments</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="reqTab">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Requisition #</th><th>Suggested Supplier</th><th>Department</th><th>Total</th><th>Budget Available</th><th></th></tr></thead>
                <tbody id="fhReqTableBody"><tr><td colspan="6" class="text-center py-4">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="poPaymentTab">
        <p class="text-muted">Payment is only requested once the order has been delivered and the 3-way match reconciles.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Amount</th><th>Requested By</th><th></th></tr></thead>
                <tbody id="fhPoPaymentTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="approveReqModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Approve Requisition</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p id="approveReqSummary"></p>
                <div id="approveReqJustificationWrap" class="d-none">
                    <label class="form-label">Justification (required — over budget)</label>
                    <textarea id="approveReqJustification" class="form-control" rows="2" maxlength="500"></textarea>
                    <div class="invalid-feedback" id="approveReqJustificationError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-sm" id="confirmApproveReqBtn">Approve</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectReqModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Request Revision</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">Sends this back to the Store Manager with your reason -- they can change the suggested supplier/items and resubmit.</p>
                <textarea id="rejectReqReason" class="form-control" rows="3" placeholder="What should the Store Manager change?" maxlength="500"></textarea>
                <div class="invalid-feedback" id="rejectReqReasonError"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning btn-sm" id="confirmRejectReqBtn">Send</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approvePoPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Send Payment to Supplier</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p id="payApproveSummary"></p>
                <div class="mb-3">
                    <label class="form-label">Payment method</label>
                    <select id="payMethodSelect" class="form-select">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="cash">Cash</option>
                        <option value="paymongo_simulated">PayMongo (Simulated)</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div id="payReferenceWrap" class="mb-2">
                    <label class="form-label">Reference number (optional)</label>
                    <input type="text" id="payReferenceInput" class="form-control" placeholder="e.g. bank transaction #" maxlength="50">
                    <div class="invalid-feedback" id="payReferenceInputError"></div>
                </div>
                <div id="paySimulatedNotice" class="alert alert-warning small d-none mb-0">
                    <strong>Demo notice:</strong> ShelfSense has no registered business behind it, so PayMongo's real
                    supplier-payout product isn't available (it requires business verification). This simulates the
                    disbursement -- a reference number and receipt are recorded exactly like a real payout would be,
                    but no actual funds are transferred.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-sm" id="confirmApprovePoPaymentBtn">Approve & Send</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentReceiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success-subtle">
                <h5 class="modal-title"><i class="bi bi-check-circle-fill text-success"></i> Payment Sent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentReceiptBody"></div>
            <div class="modal-footer">
                <button class="btn btn-yellow-primary btn-sm" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectPoPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Reject Payment Request</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <textarea id="rejectPoPaymentReason" class="form-control" rows="3" placeholder="Reason..." maxlength="500"></textarea>
                <div class="invalid-feedback" id="rejectPoPaymentReasonError"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm" id="confirmRejectPoPaymentBtn">Reject</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fhReqDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Requisition Detail</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="fhReqDetailBody">Loading...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="fhPoDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Purchase Order Detail</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="fhPoDetailBody">Loading...</div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
