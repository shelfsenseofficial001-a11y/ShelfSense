<?php
$title = 'Requisitions - Store Manager';
$pageTitle = 'Requisitions';
$activePage = 'requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/requisitions.js?v=20260902232209"></script>';

$content = <<<'EOT'
<!-- Requisition Tabs -->
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
        <button class="nav-link" id="pending-supplier-tab" data-bs-toggle="tab" data-bs-target="#pendingSupplierTab" data-tab-key="pending-supplier" type="button" role="tab">
            <i class="bi bi-hourglass-split"></i> Pending Supplier
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="awaiting-finance-tab" data-bs-toggle="tab" data-bs-target="#awaitingFinanceTab" data-tab-key="awaiting-finance" type="button" role="tab">
            <i class="bi bi-cash-coin"></i> Awaiting Finance
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#historyTab" data-tab-key="history" type="button" role="tab">
            <i class="bi bi-clock-history"></i> History
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- ============================================ -->
    <!-- TAB 1: MY REQUISITIONS -->
    <!-- ============================================ -->
    <div class="tab-pane fade show active" id="mineTab" role="tabpanel" data-tab-panel="mine">
        <div class="sm-stats-grid" data-stats-container></div>
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" id="mineSearchInput" class="form-control" data-filter="search" placeholder="Search by requisition #...">
            </div>
            <div class="col-md-3">
                <select id="mineStatusFilter" class="form-select searchable-select" data-filter="status" data-placeholder="Filter by status...">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="pending_supplier">Pending Supplier</option>
                    <option value="sent_to_supplier">Sent to Supplier</option>
                    <option value="supplier_processed">Supplier Processed</option>
                    <option value="awaiting_finance_staff">Awaiting Finance Staff</option>
                    <option value="awaiting_finance">Awaiting Finance</option>
                    <option value="paid">Paid</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="partial_received">Partially Received</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="mineSortBy" class="form-select searchable-select" data-filter="sort_by" data-placeholder="Sort by...">
                    <option value="created_at">Sort: Newest First</option>
                    <option value="order_date">Sort: Order Date</option>
                    <option value="total">Sort: Total Amount</option>
                    <option value="requisition_number">Sort: Requisition #</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="button" class="sm-view-toggle-btn" data-view-toggle title="Switch to row view">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </button>
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="active-filter-chips" id="mineFilterChips"></div>
        <div class="modern-card p-3 sm-fill-card">
            <div class="sm-requisition-grid" data-cards-container></div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small" data-info></span>
                <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TAB 2: CREATE REQUISITION (Cart Style) -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="createTab" role="tabpanel">
        <div class="row g-3 sm-create-row">
            <!-- Left: Product Grid -->
            <div class="col-lg-8 d-flex">
                <div class="modern-card p-3 sm-fill-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-box-seam text-yellow me-2"></i>Select Products to Restock</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="sm-view-toggle-btn" id="restockViewToggle" title="Switch to row view">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                            </button>
                            <button class="btn btn-yellow-outline btn-sm" id="refreshProductsBtn">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <select id="createSupplierSelect" class="form-select searchable-select" data-placeholder="Select supplier...">
                                <option value="">Loading suppliers...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="productSearchInput" class="form-control" placeholder="Search products...">
                        </div>
                    </div>
                    <div id="productGrid" class="sm-restock-grid">
                        <div class="text-center py-4" style="grid-column:1/-1;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading products...</p>
                        </div>
                    </div>
                    <div class="text-muted small mt-3" id="productInfo">Loading...</div>
                </div>
            </div>

            <!-- Right: Cart Panel -->
            <div class="col-lg-4 d-flex">
                <div class="modern-card p-0 sm-cart-card">
                    <div class="sm-cart-header">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-cart-fill text-yellow me-2"></i>
                            Requisition Cart <span class="badge bg-primary" id="cartCount">0</span>
                        </h6>
                    </div>
                    <div id="cartPanel" class="sm-cart-body">
                        <div class="sm-cart-empty" id="emptyCartMessage">
                            <i class="bi bi-cart3"></i>
                            <p>No products added</p>
                            <span>Click a product on the left to add it here</span>
                        </div>
                        <div id="cartItems" style="display:none;"></div>
                    </div>
                    <div class="sm-cart-footer">
                        <div class="sm-cart-total-row">
                            <span class="sm-cart-total-label">Total</span>
                            <span class="sm-cart-total-amount" id="cartTotal">₱0.00</span>
                        </div>
                        <div class="sm-cart-fields">
                            <div>
                                <label for="orderDate">Order Date</label>
                                <input type="date" id="orderDate" class="form-control form-control-sm" readonly>
                            </div>
                            <div>
                                <label for="expectedDelivery">Expected Delivery</label>
                                <input type="date" id="expectedDelivery" class="form-control form-control-sm">
                            </div>
                            <div class="sm-field-full">
                                <label for="requisitionNotes">Notes <span class="text-muted">(optional)</span></label>
                                <textarea id="requisitionNotes" class="form-control form-control-sm" rows="2" placeholder="Add any notes for this requisition..."></textarea>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-danger" id="clearCartBtn">
                                <i class="bi bi-trash"></i> Clear
                            </button>
                            <button class="btn btn-yellow-primary flex-grow-1" id="sendRequisitionBtn" disabled>
                                <i class="bi bi-send"></i> Create Requisition
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TAB 3: PENDING SUPPLIER -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="pendingSupplierTab" role="tabpanel" data-tab-panel="pending-supplier">
        <div class="row g-2 mb-3">
            <div class="col-md-7">
                <input type="text" id="pendingSupplierSearchInput" class="form-control" data-filter="search" placeholder="Search by requisition # or supplier...">
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="button" class="sm-view-toggle-btn" data-view-toggle title="Switch to row view">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </button>
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="active-filter-chips" id="pendingSupplierFilterChips"></div>
        <div class="modern-card p-3 sm-fill-card">
            <div class="sm-requisition-grid" data-cards-container></div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small" data-info></span>
                <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TAB 4: AWAITING FINANCE -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="awaitingFinanceTab" role="tabpanel" data-tab-panel="awaiting-finance">
        <div class="row g-2 mb-3">
            <div class="col-md-7">
                <input type="text" id="awaitingFinanceSearchInput" class="form-control" data-filter="search" placeholder="Search by requisition # or supplier...">
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="button" class="sm-view-toggle-btn" data-view-toggle title="Switch to row view">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </button>
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="active-filter-chips" id="awaitingFinanceFilterChips"></div>
        <div class="modern-card p-3 sm-fill-card">
            <div class="sm-requisition-grid" data-cards-container></div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small" data-info></span>
                <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TAB 5: HISTORY -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="historyTab" role="tabpanel" data-tab-panel="history">
        <div class="sm-stats-grid" data-history-stats></div>
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" id="historySearchInput" class="form-control" data-filter="search" placeholder="Search...">
            </div>
            <div class="col-md-2">
                <select id="historyStatusFilter" class="form-select searchable-select" data-filter="status" data-placeholder="Filter by status...">
                    <option value="">All History Status</option>
                    <option value="paid">Paid</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="partial_received">Partially Received</option>
                    <option value="finance_rejected">Finance Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" id="historyDateFrom" class="form-control" data-filter="date_from" title="From date">
            </div>
            <div class="col-md-2">
                <input type="date" id="historyDateTo" class="form-control" data-filter="date_to" title="To date">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="button" class="sm-view-toggle-btn" data-view-toggle title="Switch to row view">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </button>
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="active-filter-chips" id="historyFilterChips"></div>
        <div class="modern-card p-3 sm-fill-card">
            <div class="sm-requisition-grid" data-cards-container></div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small" data-info></span>
                <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
            </div>
        </div>
    </div>
</div>

<!-- Requisition Detail Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="requisitionDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="requisitionDetailBody">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
    <div class="p-3 border-top d-flex gap-2 justify-content-end" id="requisitionDetailFooter"></div>
</div>

<!-- Create Requisition Confirm Modal -->
<div class="modal fade" id="confirmSendModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Requisition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmSendBody">
                <p>You are about to create this requisition and send it to the supplier.</p>
                <div id="confirmSendItems"></div>
                <p class="fw-bold text-end mt-2">Total: <span id="confirmSendTotal">₱0.00</span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-yellow-primary btn-sm" id="confirmSendBtn">
                    <i class="bi bi-send"></i> Confirm & Create
                </button>
            </div>
        </div>
    </div>
</div>
EOT;

// Activate the tab named in ?tab=... (mine|create|pending-supplier|awaiting-finance|history),
// and open a specific requisition's detail modal if ?view=<id> is present.
$content .= <<<'EOT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    if (requestedTab) {
        const btn = document.querySelector(`#requisitionTabs button[data-tab-key="${requestedTab}"]`);
        if (btn) {
            new bootstrap.Tab(btn).show();
        }
    }
});
</script>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
