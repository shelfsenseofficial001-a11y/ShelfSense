<?php
$title = 'Requisitions & Payments - Finance Head';
$pageTitle = 'Requisitions & Payments';
$activePage = 'head_payment_requests';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260907"></script>'
    . '<script src="/ShelfSense/public/assets/js/finance/head/payment_requests.js?v=20260907"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs mb-3" id="fhTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#reqTab" type="button">Pending Purchase Orders</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#poPaymentTab" type="button">Pending PO Payments</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="reqTab">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>PO # (Requisition #)</th><th>Supplier</th><th>Department</th><th>Total</th><th>Budget Available</th><th></th></tr></thead>
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
                    <textarea id="approveReqJustification" class="form-control" rows="2"></textarea>
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
            <div class="modal-header"><h5 class="modal-title">Reject Requisition</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><textarea id="rejectReqReason" class="form-control" rows="3" placeholder="Reason..."></textarea></div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm" id="confirmRejectReqBtn">Reject</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectPoPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Reject Payment Request</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><textarea id="rejectPoPaymentReason" class="form-control" rows="3" placeholder="Reason..."></textarea></div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm" id="confirmRejectPoPaymentBtn">Reject</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
