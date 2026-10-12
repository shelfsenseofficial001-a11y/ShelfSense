<?php
$title = 'Requisitions - Finance Staff';
$pageTitle = 'Requisitions — Budget Check';
$activePage = 'staff_requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20261013000002"></script>'
    . '<script src="/ShelfSense/public/assets/js/finance/staff/requisitions.js?v=20261013000002"></script>';

$content = <<<'EOT'
<div class="row g-3">
    <div class="col-lg-4">
        <div class="modern-card p-3 h-100">
            <h6 class="fw-bold mb-1"><i class="bi bi-clipboard-check text-yellow me-2"></i>Pending Requisitions</h6>
            <p class="text-muted small mb-2">Awaiting a budget availability check before Finance Head review.</p>
            <input type="text" id="pendingSearchInput" class="form-control form-control-sm mb-2" placeholder="Search requisition #, supplier, department...">
            <div class="list-group" id="pendingListGroup" style="max-height:65vh; overflow-y:auto;">
                <div class="text-center text-muted py-4">Loading...</div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="modern-card p-3">
            <div id="fsReqDetailBody">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-arrow-left-circle fs-1 d-block mb-2"></i>
                    Select a requisition from the list to see its details.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Reject for Budget Reasons</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <textarea id="rejectReason" class="form-control" rows="3" placeholder="Reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm" id="confirmRejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
