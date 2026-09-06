<?php
$title = 'Requisitions - Store Manager';
$pageTitle = 'Requisitions';
$activePage = 'requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260908150000"></script>'
    . '<script src="/ShelfSense/public/assets/js/store_manager/requisitions.js?v=20260908"></script>';

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
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="mineTab" role="tabpanel">
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

    <div class="tab-pane fade" id="createTab" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Order Date</label>
                <input type="date" id="createOrderDate" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Needed By (optional)</label>
                <input type="date" id="createNeededBy" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Department</label>
                <select id="createDepartment" class="form-select"></select>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label">Notes (optional)</label>
            <textarea id="createNotes" class="form-control" rows="2" maxlength="500"></textarea>
        </div>

        <hr>
        <h6>Items Needed</h6>
        <p class="text-muted small">Pick the products to resupply and how much of each. The supplier list below narrows automatically to only suppliers who carry every product you've added.</p>
        <div class="table-responsive">
            <table class="table table-sm" id="createItemsTable">
                <thead><tr><th>Product</th><th style="width:110px">Qty</th><th></th></tr></thead>
                <tbody id="createItemsBody"></tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="addItemRowBtn"><i class="bi bi-plus"></i> Add Item</button>

        <hr>
        <h6>Choose a Supplier</h6>
        <div id="eligibleSuppliersPanel" class="mb-3">
            <p class="text-muted small">Add at least one item to see which suppliers can fulfill this request.</p>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="fw-bold">Total: <span id="createSubtotal">₱0.00</span></div>
            <button type="button" class="btn btn-yellow-primary" id="submitRequisitionBtn" disabled><i class="bi bi-send"></i> Submit for Budget Check</button>
        </div>
    </div>

    <div class="tab-pane fade" id="poTab" role="tabpanel">
        <p class="text-muted small">Purchase orders needing your response (supplier counter-proposals) or ready for goods receipt.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody id="poTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
            </table>
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
