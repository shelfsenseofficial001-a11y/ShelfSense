<?php
$title = 'Payment Requests - Finance Head';
$pageTitle = 'Approve Payments';
$activePage = 'head_payment_requests';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/head/payment_requests.js?v=20260830030000"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs fn-tabs mb-3" id="reqTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-tab-key="pending" type="button">⏳ Pending <span class="badge bg-secondary ms-1" id="countPending">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-tab-key="approved" type="button">✅ Approved <span class="badge bg-secondary ms-1" id="countApproved">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-tab-key="rejected" type="button">❌ Rejected <span class="badge bg-secondary ms-1" id="countRejected">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-tab-key="all" type="button">📋 All <span class="badge bg-secondary ms-1" id="countAll">0</span></button></li>
</ul>

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search requisition #, supplier, invoice #...">
        </div>
    </div>
    <div class="col-md-2">
        <select id="budgetStatusFilter" class="form-select searchable-select" data-placeholder="Budget status...">
            <option value="">All Budget Statuses</option>
            <option value="within_budget">Within Budget</option>
            <option value="exceeded">Budget Exceeded</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" id="dateFrom" class="form-control" title="Requested from">
    </div>
    <div class="col-md-2">
        <input type="date" id="dateTo" class="form-control" title="Requested to">
    </div>
    <div class="col-md-2 text-end">
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<div id="fn-cards-container" class="fn-card-grid">
    <div class="text-center py-4" style="grid-column:1/-1;">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading payment requests...</p>
    </div>
</div>
<div class="d-flex justify-content-between align-items-center mt-3">
    <span class="text-muted small" id="tableInfo">Loading...</span>
    <nav><ul class="pagination pagination-sm mb-0" id="paginationContainer"></ul></nav>
</div>

<!-- Requisition / Payment Request Detail Modal -->
<div class="modal fade" id="requestDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer" id="requestDetailFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalTitle">✅ Approve Payment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="approveModalSummary"></div>
                <div class="mb-2 mt-3" id="approveNotesGroup">
                    <label class="form-label fw-semibold" id="approveNotesLabel">Approval Notes (Optional)</label>
                    <textarea id="approveNotes" class="form-control" rows="3" placeholder="Approved. All documents verified."></textarea>
                    <div class="invalid-feedback" id="approveNotesError">A justification is required to approve an over-budget request.</div>
                </div>
                <div class="fn-doc-box small mt-2">
                    <div class="fn-doc-title">This action will:</div>
                    <div id="approveConsequences"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="confirmApproveBtn">Confirm Approval</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">❌ Reject Payment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="rejectModalSummary"></div>
                <div class="mb-2 mt-3">
                    <label class="form-label fw-semibold">Reason for Rejection (Required)</label>
                    <textarea id="rejectReason" class="form-control" rows="3" placeholder="Invoice amounts do not match PO. Please correct and resubmit."></textarea>
                    <div class="invalid-feedback" id="rejectReasonError">A rejection reason is required.</div>
                </div>
                <div class="fn-doc-box small mt-2">
                    <div class="fn-doc-title">This action will:</div>
                    <div>❌ Reject the payment request<br>📨 Notify Finance Staff with the reason<br>🔄 Return the requisition to "Awaiting Finance Staff"<br>🔧 Finance Staff can correct and resubmit</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmRejectBtn">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
