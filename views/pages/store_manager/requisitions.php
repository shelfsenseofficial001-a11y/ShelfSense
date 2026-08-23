<?php
$title = 'Requisitions - Store Manager';
$pageTitle = 'Requisitions';
$activePage = 'requisitions';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/requisitions.js"></script>';
$additional_css = '
<style>
    .product-card.clickable-card:hover { border-color: var(--brand-yellow); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .cart-item { padding: 8px 12px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px; }
    .cart-item .item-info { flex: 1; }
    .cart-item .item-name { font-size: 0.85rem; font-weight: 500; }
    .cart-item .item-price { font-size: 0.8rem; color: var(--text-muted); }
    .cart-item .qty-control { display: flex; align-items: center; gap: 4px; }
    .cart-item .qty-control button { width: 24px; height: 24px; padding: 0; font-size: 0.7rem; border-radius: 50%; border: 1px solid var(--border-color); background: var(--bg-card-subtle); color: var(--text-main); cursor: pointer; }
    .cart-item .qty-control button:hover { background: var(--brand-yellow); }
    .cart-item .qty-control input[type="number"] { -moz-appearance: textfield; appearance: textfield; }
    .cart-item .qty-control input[type="number"]::-webkit-outer-spin-button,
    .cart-item .qty-control input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .cart-item .qty-control span { min-width: 20px; text-align: center; font-weight: 600; }
    .cart-total-row { padding: 12px 16px; border-top: 2px solid var(--brand-yellow); background: var(--light-yellow-subtle); border-radius: 0 0 8px 8px; }
</style>
';

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
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition #...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select searchable-select" data-filter="status" data-placeholder="Filter by status...">
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
                <select class="form-select searchable-select" data-filter="sort_by" data-placeholder="Sort by...">
                    <option value="created_at">Sort: Newest First</option>
                    <option value="order_date">Sort: Order Date</option>
                    <option value="total">Sort: Total Amount</option>
                    <option value="requisition_number">Sort: Requisition #</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button class="btn btn-yellow-outline btn-sm w-100" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="sm-requisition-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TAB 2: CREATE REQUISITION (Cart Style) -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="createTab" role="tabpanel">
        <div class="row g-3">
            <!-- Left: Product Grid -->
            <div class="col-lg-8">
                <div class="modern-card p-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-box-seam text-yellow me-2"></i>Select Products to Restock</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-5">
                            <select id="createSupplierSelect" class="form-select form-select-sm searchable-select" data-placeholder="Select supplier...">
                                <option value="">Loading suppliers...</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="productSearchInput" class="form-control" placeholder="Search products...">
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-yellow-outline btn-sm" id="refreshProductsBtn">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div id="productGrid" class="row g-2" style="max-height:420px;overflow-y:auto;">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading products...</p>
                        </div>
                    </div>
                    <div class="mt-2 text-muted small" id="productInfo">Loading...</div>
                </div>
            </div>

            <!-- Right: Cart Panel -->
            <div class="col-lg-4">
                <div class="modern-card p-0">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-cart-fill text-yellow me-2"></i>
                            Requisition Cart <span class="badge bg-primary" id="cartCount">0</span>
                        </h6>
                    </div>
                    <div id="cartPanel" style="max-height:300px;overflow-y:auto;">
                        <div class="text-center text-muted py-4" id="emptyCartMessage">
                            <i class="bi bi-cart3 fs-3 d-block mb-2"></i>
                            No products added
                        </div>
                        <div id="cartItems" style="display:none;"></div>
                    </div>
                    <div class="cart-total-row">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total:</span>
                            <span class="fs-4 fw-bold text-yellow" id="cartTotal">₱0.00</span>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button class="btn btn-outline-danger btn-sm flex-grow-1" id="clearCartBtn">
                                <i class="bi bi-trash"></i> Clear
                            </button>
                            <button class="btn btn-yellow-primary flex-grow-1" id="sendRequisitionBtn" disabled>
                                <i class="bi bi-send"></i> Create Requisition
                            </button>
                        </div>
                    </div>
                    <div class="p-2 small">
                        <div class="row g-1">
                            <div class="col-6">
                                <label class="form-label">Order Date</label>
                                <input type="date" id="orderDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Expected Delivery</label>
                                <input type="date" id="expectedDelivery" class="form-control form-control-sm"
                                       min="<?= date('Y-m-d') ?>"
                                       max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea id="requisitionNotes" class="form-control form-control-sm" rows="1" placeholder="Optional notes..."></textarea>
                            </div>
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
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition # or supplier...">
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="sm-requisition-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TAB 4: AWAITING FINANCE -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="awaitingFinanceTab" role="tabpanel" data-tab-panel="awaiting-finance">
        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search by requisition # or supplier...">
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="sm-requisition-grid" data-cards-container></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small" data-info></span>
            <nav><ul class="pagination pagination-sm mb-0" data-pagination></ul></nav>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TAB 5: HISTORY -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="historyTab" role="tabpanel" data-tab-panel="history">
        <div class="sm-stats-grid" data-history-stats></div>
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" data-filter="search" placeholder="Search...">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select searchable-select" data-filter="status" data-placeholder="Filter by status...">
                    <option value="">All History Status</option>
                    <option value="paid">Paid</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="partial_received">Partially Received</option>
                    <option value="finance_rejected">Finance Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" data-filter="date_from" title="From date">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" data-filter="date_to" title="To date">
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-yellow-outline btn-sm" data-action="refresh">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="sm-requisition-grid" data-cards-container></div>
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
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="requisitionDetailFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
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
