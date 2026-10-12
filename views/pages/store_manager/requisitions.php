<?php
$title = 'Requisitions - Store Manager';
$pageTitle = 'Requisitions';
$activePage = 'requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20261013000002"></script>'
    . '<script src="/ShelfSense/public/assets/js/store_manager/requisitions.js?v=20261013000004"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs sm-tabs mb-3" id="requisitionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="mine-tab" data-bs-toggle="tab" data-bs-target="#mineTab" data-tab-key="mine" type="button" role="tab">
            <i class="bi bi-list"></i> My Requisitions
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#createTab" data-tab-key="create" type="button" role="tab">
            <i class="bi bi-cart-plus"></i> Create Requisition
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="po-tab" data-bs-toggle="tab" data-bs-target="#poTab" data-tab-key="po" type="button" role="tab">
            <i class="bi bi-truck"></i> Purchase Orders
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="disc-tab" data-bs-toggle="tab" data-bs-target="#discTab" data-tab-key="discrepancies" type="button" role="tab">
            <i class="bi bi-exclamation-triangle"></i> Backorders &amp; RMA
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="mineTab" role="tabpanel">
        <div class="modern-card p-3 sm-fill-card">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <select id="mineStatusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending_budget_check">Pending Budget Check</option>
                        <option value="budget_rejected">Budget Rejected</option>
                        <option value="pending_finance_head">Pending Finance Head</option>
                        <option value="revision_requested">Revision Requested</option>
                        <option value="converted_to_po">Converted to PO</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr>
                        <th>Requisition #</th><th>Supplier</th><th>Department</th><th>Total</th><th>Status</th><th>Created</th><th></th>
                    </tr></thead>
                    <tbody id="mineTableBody"><tr><td colspan="7" class="text-center py-4">Loading...</td></tr></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small id="minePageInfo" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="minePagination"></ul>
            </div>
        </div>

        <a href="?page=store_manager_requisitions&tab=create" class="sm-fab" id="mineCreateFab" title="Create Requisition">
            <span class="sm-fab-icon"><i class="bi bi-plus-lg"></i></span>
            <span class="sm-fab-label">Create Requisition</span>
        </a>
    </div>

    <div class="tab-pane fade" id="createTab" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-8 col-xl-7 mx-auto">
                <div class="modern-card p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Order Date</label>
                            <input type="text" id="createOrderDate" class="form-control" disabled readonly tabindex="-1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Needed By (optional)</label>
                            <input type="date" id="createNeededBy" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea id="createNotes" class="form-control sm-notes-input" rows="2" maxlength="500"></textarea>
                    </div>

                    <div class="sm-section-divider"></div>
                    <h6 class="sm-section-title"><i class="bi bi-box-seam"></i> Items Needed</h6>
                    <p class="text-muted small">Pick the products to resupply and how much of each. The supplier list below narrows automatically to only suppliers who carry every product you've added.</p>
                    <div class="sm-item-table">
                        <div class="sm-item-list-header">
                            <span>Product</span><span>Qty</span><span></span>
                        </div>
                        <div id="createItemsBody" class="sm-item-list"></div>
                        <button type="button" class="sm-add-item-btn" id="addItemRowBtn"><i class="bi bi-plus-lg"></i> Add Item</button>
                    </div>

                    <div class="sm-section-divider"></div>
                    <h6 class="sm-section-title"><i class="bi bi-truck"></i> Choose a Supplier</h6>
                    <div id="eligibleSuppliersPanel" class="mb-3">
                        <p class="text-muted small">Add at least one item to see which suppliers can fulfill this request.</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Why this supplier? (optional, seen by Finance Head)</label>
                        <textarea id="createSupplierNote" class="form-control" rows="2" maxlength="500" placeholder="e.g. Better quality, faster lead time..."></textarea>
                    </div>

                    <div class="sm-requisition-summary">
                        <div class="sm-requisition-total">
                            <span class="text-muted small">Total</span>
                            <span class="fw-bold fs-5" id="createSubtotal">₱0.00</span>
                        </div>
                        <button type="button" class="btn btn-yellow-primary" id="submitRequisitionBtn" disabled><i class="bi bi-send"></i> Submit for Budget Check</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="poTab" role="tabpanel">
        <div class="modern-card p-3 sm-po-card">
            <h6 id="poSectionHeading"><i class="bi bi-truck"></i> Purchase Orders</h6>
            <p class="text-muted small">Purchase orders needing your response (supplier counter-proposals) or ready for goods receipt.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>PO #</th><th>Supplier</th><th>Total</th><th>Status</th><th></th></tr></thead>
                    <tbody id="poTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="discTab" role="tabpanel">
        <div class="modern-card p-3">
            <h6><i class="bi bi-exclamation-triangle"></i> Backorders &amp; RMA</h6>
            <p class="text-muted small">Missing and damaged lines raised on goods receipts, and what the supplier has done about each.</p>
            <div class="btn-group btn-group-sm mb-3" role="group">
                <button type="button" class="btn btn-outline-secondary active" id="discFilterAll" onclick="setDiscrepancyFilter('')">All</button>
                <button type="button" class="btn btn-outline-secondary" id="discFilterBackorder" onclick="setDiscrepancyFilter('missing')">Backorders</button>
                <button type="button" class="btn btn-outline-secondary" id="discFilterRma" onclick="setDiscrepancyFilter('damaged')">RMA</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>PO #</th><th>Supplier</th><th>Product</th><th>Type</th><th>Qty</th><th>Status</th><th>Detail</th><th></th></tr></thead>
                    <tbody id="discTableBody"><tr><td colspan="8" class="text-center py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rateSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Stage 9, Close & Review -- this closes the Purchase Order.</p>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="rateOnTime" checked>
                    <label class="form-check-label" for="rateOnTime">Delivered on time</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fill Rate (%)</label>
                    <input type="number" id="rateFillRate" class="form-control" min="0" max="100" value="100">
                    <div class="form-text">% of the ordered quantity actually delivered.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quality Rating</label>
                    <select id="rateQuality" class="form-select">
                        <option value="5">★★★★★ Excellent</option>
                        <option value="4">★★★★☆ Good</option>
                        <option value="3" selected>★★★☆☆ Satisfactory</option>
                        <option value="2">★★☆☆☆ Poor</option>
                        <option value="1">★☆☆☆☆ Very Poor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (optional)</label>
                    <textarea id="rateNotes" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-yellow-primary btn-sm" id="confirmRateSupplierBtn">Submit & Close PO</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="poDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purchase Order Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="poDetailBody">Loading...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="reqDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Requisition Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reqDetailBody">Loading...</div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
