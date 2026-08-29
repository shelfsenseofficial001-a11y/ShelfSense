<?php
$title = 'Pending Requisitions - Finance Staff';
$pageTitle = 'Pending Requisitions';
$activePage = 'staff_requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/staff/requisitions.js?v=20260829181340"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs fn-tabs mb-3" id="reqTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-tab-key="to_review" type="button">To Review <span class="badge bg-secondary ms-1" id="countToReview">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-tab-key="budget_exceeded" type="button">Budget Exceeded <span class="badge bg-secondary ms-1" id="countExceeded">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-tab-key="awaiting_approval" type="button">Awaiting Approval <span class="badge bg-secondary ms-1" id="countAwaiting">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-tab-key="my_history" type="button">My History <span class="badge bg-secondary ms-1" id="countHistory">0</span></button></li>
</ul>

<div class="row g-2 mb-3">
    <div class="col-md-8">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search by requisition # or supplier...">
        </div>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<div id="fn-cards-container" class="fn-card-grid">
    <div class="text-center py-4" style="grid-column:1/-1;">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading...</p>
    </div>
</div>
<div class="d-flex justify-content-between align-items-center mt-3">
    <span class="text-muted small" id="tableInfo">Loading...</span>
    <nav><ul class="pagination pagination-sm mb-0" id="paginationContainer"></ul></nav>
</div>

<!-- Requisition Detail Modal -->
<div class="modal fade" id="requisitionDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Requisition Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requisitionDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer" id="requisitionDetailFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Payment Request Modal -->
<div class="modal fade" id="paymentRequestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Payment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentRequestForm">
                <input type="hidden" id="paymentRequisitionId" value="">
                <div class="modal-body">
                    <div id="paymentRequestSummary"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes <small class="text-muted">(optional)</small></label>
                        <textarea id="paymentRequestNotes" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3" id="justificationGroup" style="display:none;">
                        <label class="form-label fw-semibold">Over-Budget Justification <span class="text-danger">*</span></label>
                        <textarea id="paymentRequestJustification" class="form-control" rows="2" maxlength="500" placeholder="Explain why this payment should proceed despite exceeding the available budget."></textarea>
                        <div class="invalid-feedback">A justification is required for budget-exceeded requests.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm"><i class="bi bi-send"></i> Submit for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
