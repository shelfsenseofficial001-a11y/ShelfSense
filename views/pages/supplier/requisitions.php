<?php
$title = 'Purchase Orders - Supplier';
$pageTitle = 'Purchase Orders';
$activePage = 'requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260907"></script>'
    . '<script src="/ShelfSense/public/assets/js/supplier/requisitions.js?v=20260907"></script>';

$content = <<<'EOT'
<div class="modern-card p-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-check text-yellow me-2"></i>Purchase Orders</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-3">
            <thead><tr><th>PO #</th><th>Order Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody id="spPoTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <small id="spPoPageInfo" class="text-muted"></small>
        <ul class="pagination pagination-sm mb-0" id="spPoPagination"></ul>
    </div>
</div>

<div class="modal fade" id="spPoDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Purchase Order Detail</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="spPoDetailBody">Loading...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="spCounterModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Propose Quantity Change</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">Price is fixed at the PO's contracted rate — only quantity can be revised (e.g. limited stock).</p>
                <table class="table table-sm" id="spCounterItemsTable">
                    <thead><tr><th>Product</th><th>Ordered Qty</th><th>Proposed Qty</th><th>Price</th></tr></thead>
                    <tbody id="spCounterItemsBody"></tbody>
                </table>
                <label class="form-label">Reason</label>
                <textarea id="spCounterReason" class="form-control" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-yellow-primary btn-sm" id="spSubmitCounterBtn">Submit Proposal</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
