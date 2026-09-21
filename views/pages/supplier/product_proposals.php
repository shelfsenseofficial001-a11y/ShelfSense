<?php
$title = 'Product Proposals - Supplier';
$pageTitle = 'Product Proposals';
$activePage = 'product_proposals';
$additional_js = '<script src="/ShelfSense/public/assets/js/supplier/product_proposals.js?v=20260919100000"></script>';

$content = <<<'EOT'
<p class="text-muted small mb-3">New products the store wants to carry from you. Confirm your own price, availability, and what you call it -- or decline. Once you confirm, it goes to the Owner for final approval.</p>

<div class="row g-2 mb-3">
    <div class="col-md-4"><div class="modern-card p-2 text-center"><small class="text-muted">Needs Your Response</small><h5 class="mb-0 text-warning" id="statPendingSupplier">0</h5></div></div>
    <div class="col-md-4"><div class="modern-card p-2 text-center"><small class="text-muted">Awaiting Owner</small><h5 class="mb-0 text-info" id="statPendingOwner">0</h5></div></div>
    <div class="col-md-4"><div class="modern-card p-2 text-center"><small class="text-muted">Approved</small><h5 class="mb-0 text-success" id="statApproved">0</h5></div></div>
</div>

<div class="modern-card">
    <div class="card-header"><i class="bi bi-lightbulb me-2"></i>Proposals</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Proposed Price</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="proposalsTableBody">
                    <tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail / Respond Modal -->
<div class="modal fade" id="proposalDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proposal Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="proposalDetailBody"></div>
        </div>
    </div>
</div>

<!-- Confirm Form Modal -->
<div class="modal fade" id="confirmFormModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm This Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Your Product Name *</label>
                    <input type="text" class="form-control" id="confirmName" maxlength="100" placeholder="What you call this in your own catalog">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Your Price *</label>
                        <input type="number" class="form-control" id="confirmPrice" min="0.01" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Available Quantity *</label>
                        <input type="number" class="form-control" id="confirmQuantity" min="0" step="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="submitConfirmBtn"><i class="bi bi-check-circle"></i> Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Decline Reason Modal -->
<div class="modal fade" id="declineReasonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Decline Proposal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Reason *</label>
                <textarea class="form-control" id="declineReasonInput" rows="3" placeholder="Why you can't carry this..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeclineBtn">Decline</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
