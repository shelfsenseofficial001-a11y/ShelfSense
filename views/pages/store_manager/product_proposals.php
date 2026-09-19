<?php
$title = 'Product Proposals - Store Manager';
$pageTitle = 'Product Proposals';
$activePage = 'product_proposals';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/product_proposals.js?v=20260919100000"></script>';

$content = <<<'EOT'
<p class="text-muted small mb-3">Propose a new product to carry. Once the supplier confirms their price and availability, it goes to the Owner for final approval -- only then does it appear in Inventory, at zero stock, ready for a normal Requisition.</p>

<div class="row g-2 mb-3">
    <div class="col-md-3"><div class="modern-card p-2 text-center"><small class="text-muted">Awaiting Supplier</small><h5 class="mb-0 text-warning" id="statPendingSupplier">0</h5></div></div>
    <div class="col-md-3"><div class="modern-card p-2 text-center"><small class="text-muted">Awaiting Owner</small><h5 class="mb-0 text-info" id="statPendingOwner">0</h5></div></div>
    <div class="col-md-3"><div class="modern-card p-2 text-center"><small class="text-muted">Approved</small><h5 class="mb-0 text-success" id="statApproved">0</h5></div></div>
    <div class="col-md-3"><div class="modern-card p-2 text-center"><small class="text-muted">Rejected</small><h5 class="mb-0 text-danger" id="statRejected">0</h5></div></div>
</div>

<div class="modern-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-lightbulb me-2"></i>Proposals</span>
        <button class="btn btn-sm btn-yellow-primary" id="newProposalBtn"><i class="bi bi-plus-circle"></i> Propose New Product</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Proposed Price</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="proposalsTableBody">
                    <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Proposal Modal -->
<div class="modal fade" id="newProposalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Propose New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Name *</label>
                    <input type="text" class="form-control" id="ppName" maxlength="100" placeholder="What this will be called in our Catalog">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Barcode *</label>
                        <input type="text" class="form-control" id="ppBarcode" maxlength="50">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Category</label>
                        <select class="form-select searchable-select" id="ppCategory" data-placeholder="Select category..."></select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Selling Price *</label>
                        <input type="number" class="form-control" id="ppPrice" min="0.01" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Supplier *</label>
                        <select class="form-select searchable-select" id="ppSupplier" data-placeholder="Select supplier..."></select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" id="ppDescription" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-yellow-primary btn-sm" id="submitProposalBtn"><i class="bi bi-send"></i> Send to Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="proposalDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proposal Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="proposalDetailBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
