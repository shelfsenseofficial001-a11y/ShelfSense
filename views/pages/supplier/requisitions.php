<?php
$title = 'Requisitions - Supplier';
$pageTitle = 'Incoming Requisitions';
$activePage = 'requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/supplier/requisitions.js"></script>';

$content = <<<'EOT'
<!-- Tabs -->
<ul class="nav nav-tabs sp-tabs mb-3" id="requisitionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pendingTab" data-tab-key="pending" type="button" role="tab">
            <i class="bi bi-inbox"></i> Pending
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invoicedTab" data-tab-key="invoiced" type="button" role="tab">
            <i class="bi bi-receipt"></i> Invoiced
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#paidTab" data-tab-key="paid" type="button" role="tab">
            <i class="bi bi-truck"></i> Paid / Ready to Ship
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#shippedTab" data-tab-key="shipped" type="button" role="tab">
            <i class="bi bi-check-circle"></i> Shipped
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#allTab" data-tab-key="all" type="button" role="tab">
            <i class="bi bi-list"></i> All
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pendingTab" role="tabpanel" data-tab-panel="pending">
        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition # or store...">
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="sp-card-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>

    <div class="tab-pane fade" id="invoicedTab" role="tabpanel" data-tab-panel="invoiced">
        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition # or store...">
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="sp-card-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>

    <div class="tab-pane fade" id="paidTab" role="tabpanel" data-tab-panel="paid">
        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition # or store...">
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="sp-card-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>

    <div class="tab-pane fade" id="shippedTab" role="tabpanel" data-tab-panel="shipped">
        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition # or store...">
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="sp-card-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>

    <div class="tab-pane fade" id="allTab" role="tabpanel" data-tab-panel="all">
        <div class="row g-2 mb-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition # or store...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select searchable-select" data-filter="status" data-placeholder="Filter by status...">
                    <option value="">All Status</option>
                    <option value="pending_supplier">Pending Supplier</option>
                    <option value="sent_to_supplier">Sent to Supplier</option>
                    <option value="supplier_processed">Invoiced</option>
                    <option value="awaiting_finance_staff">Awaiting Finance Staff</option>
                    <option value="awaiting_finance">Awaiting Finance</option>
                    <option value="paid">Paid</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="partial_received">Partially Received</option>
                </select>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="sp-card-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>
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

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectRequisitionId" value="">
                <label class="form-label fw-semibold">Reason for rejection <span class="text-danger">*</span></label>
                <textarea id="rejectReason" class="form-control" rows="3" maxlength="500" placeholder="e.g. Item out of stock. Please revise order." required></textarea>
                <div class="invalid-feedback" id="rejectReasonFeedback">A rejection reason is required.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmRejectBtn"><i class="bi bi-x-circle"></i> Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="invoiceForm">
                <input type="hidden" id="invoiceRequisitionId" value="">
                <div class="modal-body">
                    <div id="invoiceRequisitionSummary" class="mb-3"></div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Invoice Date</label>
                            <input type="date" id="invoiceDate" class="form-control" readonly>
                            <small class="text-muted">Auto-set to today. Not editable.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span> <small class="text-muted">(confirmed delivery date)</small></label>
                            <input type="date" id="invoiceDueDate" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Notes <small class="text-muted">(forwarded to Finance Staff)</small></label>
                        <textarea id="invoiceNotes" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mt-3">
                        <h6>Items (pre-filled from requisition)</h6>
                        <div id="invoiceItemsPreview" class="table-responsive">
                            <p class="text-muted small">Loading items...</p>
                        </div>
                    </div>
                    <div class="mt-2 text-end">
                        <strong>Total: <span id="invoiceTotalDisplay">₱0.00</span></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm"><i class="bi bi-send"></i> Send Invoice to Store Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ship Goods Modal -->
<div class="modal fade" id="shipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ship Goods</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="shipModalBody">
                <p>✅ Payment has been confirmed by Finance.</p>
                <label class="form-label fw-semibold">Tracking # <small class="text-muted">(optional)</small></label>
                <input type="text" id="shipTrackingNumber" class="form-control mb-2" maxlength="100" placeholder="e.g. LBC-123456789">
                <label class="form-label fw-semibold">Shipping Notes <small class="text-muted">(optional)</small></label>
                <textarea id="shipNotes" class="form-control" rows="2" maxlength="500"></textarea>
                <p class="text-muted small mt-2">This will notify the Store Manager that goods are ready for receipt.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="confirmShipBtn"><i class="bi bi-truck"></i> Confirm Shipment</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
