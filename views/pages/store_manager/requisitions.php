<?php
$title = 'Requisitions - Store Manager';
$pageTitle = 'Requisitions';
$activePage = 'requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260908150000"></script>'
    . '<script src="/ShelfSense/public/assets/js/store_manager/requisitions.js?v=20260908410000"></script>';

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
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="mineTab" role="tabpanel">
        <div class="modern-card p-3 sm-fill-card">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <select id="mineStatusFilter" class="form-select">
                        <option value="">All Status</option>
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
            <div class="col-lg-7">
                <div class="modern-card p-3 sm-po-card">
                    <h6 id="poSectionHeading"><i class="bi bi-truck"></i> Purchase Orders</h6>
                    <p class="text-muted small">Purchase orders needing your response (supplier counter-proposals) or ready for goods receipt.</p>
                    <div class="sm-po-scroll">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>PO #</th><th>Supplier</th><th>Total</th><th>Status</th><th></th></tr></thead>
                            <tbody id="poTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="modern-card p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Order Date</label>
                            <input type="date" id="createOrderDate" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Needed By (optional)</label>
                            <input type="date" id="createNeededBy" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Department</label>
                            <select id="createDepartment" class="form-select"></select>
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
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
