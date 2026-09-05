<?php
$title = 'Requisitions - Finance Staff';
$pageTitle = 'Requisitions — Budget Check';
$activePage = 'staff_requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260904"></script>'
    . '<script src="/ShelfSense/public/assets/js/finance/staff/requisitions.js?v=20260904"></script>';

$content = <<<'EOT'
<p class="text-muted">Requisitions submitted by Store Managers, awaiting a budget availability check before they go to Finance Head for approval.</p>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr>
            <th>Requisition #</th><th>Supplier</th><th>Department</th><th>Total</th><th>Budget Available</th><th>Status</th><th></th>
        </tr></thead>
        <tbody id="pendingTableBody"><tr><td colspan="7" class="text-center py-4">Loading...</td></tr></tbody>
    </table>
</div>
<div class="d-flex justify-content-between align-items-center">
    <small id="pendingPageInfo" class="text-muted"></small>
    <ul class="pagination pagination-sm mb-0" id="pendingPagination"></ul>
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
