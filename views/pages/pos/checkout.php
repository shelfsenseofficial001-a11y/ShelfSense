<?php
$title = 'Checkout - ShelfSense POS';
$pageTitle = 'Checkout';
$activePage = 'checkout';
$additional_js = '<script src="/ShelfSense/public/assets/js/pos/pos.js?v=20260831390000"></script>';

$content = <<<'EOT'
<div class="row g-3 flex-grow-1">
    <!-- Left: Shift/Stats + Recent Orders + Categories + Product Grid -->
    <div class="col-lg-8 d-flex flex-column">
        <!-- Shift / Stats Bar -->
        <div class="row g-3 mb-3 flex-shrink-0" id="posInfoBar">
            <div class="col-4">
                <div class="modern-card p-3 pos-info-item">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <small class="text-muted d-block">My Shift Today</small>
                        <strong id="myShiftLabel">Loading...</strong>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="modern-card p-3 pos-info-item">
                    <i class="bi bi-cash-stack"></i>
                    <div>
                        <small class="text-muted d-block">Today's Sales</small>
                        <strong id="todaySalesLabel">₱0.00</strong>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="modern-card p-3 pos-info-item">
                    <i class="bi bi-receipt"></i>
                    <div>
                        <small class="text-muted d-block">Transactions Today</small>
                        <strong id="todayTransactionsLabel">0</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="mb-3 flex-shrink-0">
            <h6 class="fw-bold mb-2"><i class="bi bi-list-ul text-yellow me-2"></i>Recent Orders</h6>
            <div class="recent-orders-row" id="recentOrdersRow">
                <div class="text-muted small py-2">Loading recent orders...</div>
            </div>
        </div>

        <div class="modern-card p-3 flex-grow-1 d-flex flex-column">
            <!-- Search & Barcode -->
            <div class="search-barcode-row mb-3 flex-shrink-0">
                <div class="search-wrapper">
                    <div class="autocomplete-wrapper">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by name..." autocomplete="off">
                        <div class="autocomplete-dropdown" id="searchAutocomplete"></div>
                    </div>
                </div>
                <div class="barcode-wrapper">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="barcodeInput" class="form-control barcode-input" placeholder="Scan barcode..." autocomplete="off">
                    </div>
                </div>
                <div class="flex-shrink-0 d-flex gap-2">
                    <div class="pos-view-toggle" id="productViewToggle">
                        <button type="button" class="active" data-view="grid" title="Grid view"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                        <button type="button" data-view="list" title="List view"><i class="bi bi-list-ul"></i></button>
                    </div>
                    <button class="btn btn-yellow-outline btn-sm" id="refreshProductsBtn">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Menu Categories -->
            <div class="pos-category-row mb-3 flex-shrink-0" id="categoryRow">
                <button class="pos-category-chip active" data-category="0">
                    <i class="bi bi-grid-fill"></i> All
                </button>
            </div>

            <!-- Product Grid (tiled/compact) -->
            <div class="product-grid product-grid-compact flex-grow-1" id="productGrid">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading products...</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3 flex-shrink-0">
                <span class="text-muted small" id="productInfo">Loading...</span>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0" id="productPagination">
                        <li class="page-item disabled"><span class="page-link">1</span></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Right: Order / Checkout Panel -->
    <div class="col-lg-4">
        <div class="modern-card p-0 d-flex flex-column h-100">
            <div class="card-header bg-transparent border-bottom flex-shrink-0 order-summary-header">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-receipt-cutoff text-yellow me-2"></i>
                    Order Summary <span class="badge" id="cartCount">0</span>
                </h6>
            </div>
            <div class="cart-panel" id="cartPanel">
                <div class="text-center text-muted" id="emptyCartMessage">
                    <i class="bi bi-cart3 d-block mb-2"></i>
                    Cart is empty
                </div>
                <div id="cartItems" style="display:none;"></div>
            </div>
            <div class="payment-summary flex-shrink-0">
                <div class="payment-summary-title">Payment Summary</div>
                <div class="summary-row">
                    <span>Sub Total</span>
                    <span id="cartSubtotal">₱0.00</span>
                </div>
                <div class="summary-total-row">
                    <span class="label">Amount to be Paid</span>
                    <span class="fs-4 fw-bold text-yellow" id="cartTotal">₱0.00</span>
                </div>
                <div class="primary-actions">
                    <button class="btn btn-outline-danger btn-sm flex-grow-1" id="clearCartBtn">
                        <i class="bi bi-trash"></i> Clear
                    </button>
                    <button class="btn btn-yellow-primary flex-grow-1" id="checkoutBtn" disabled>
                        <i class="bi bi-credit-card"></i> Place Order
                    </button>
                </div>
                <div class="secondary-actions">
                    <button class="btn flex-grow-1" id="printLastReceiptBtn" disabled>
                        <i class="bi bi-printer"></i> Print Last Receipt
                    </button>
                    <a href="?page=pos_orders" class="btn flex-grow-1">
                        <i class="bi bi-list-ul"></i> Transactions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">💳 Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h4>Total Amount</h4>
                    <h2 class="fw-bold text-yellow" id="paymentTotal">₱0.00</h2>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Method</label>
                    <div class="d-flex flex-wrap gap-2" id="paymentMethods">
                        <button class="btn btn-outline-primary payment-method-btn active" data-method="cash">💵 Cash</button>
                        <button class="btn btn-outline-primary payment-method-btn" data-method="card">💳 Card</button>
                        <button class="btn btn-outline-primary payment-method-btn" data-method="gcash">📱 GCash</button>
                        <button class="btn btn-outline-primary payment-method-btn" data-method="paymaya">📱 PayMaya</button>
                        <button class="btn btn-outline-primary payment-method-btn" data-method="other">🔄 Other</button>
                    </div>
                </div>

                <div id="cashFields">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount Tendered</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" id="amountTendered" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Change</label>
                        <h4 class="text-success" id="changeDisplay">₱0.00</h4>
                    </div>
                </div>

                <div id="referenceFields" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference Number (optional)</label>
                        <input type="text" id="paymentReference" class="form-control" placeholder="e.g., GCash ref #">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes (optional)</label>
                    <textarea id="paymentNotes" class="form-control" rows="2" placeholder="Any notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Close
                </button>
                <button type="button" class="btn btn-warning btn-sm" id="cancelPaymentBtn">
                    <i class="bi bi-arrow-counterclockwise"></i> Cancel
                </button>
                <button type="button" class="btn btn-yellow-primary" id="completePaymentBtn">
                    <i class="bi bi-check-circle"></i> Pay
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🧾 Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receiptBody">
                <div id="receiptContent">
                    <div class="text-center">
                        <h5 class="fw-bold">ShelfSense POS</h5>
                        <small class="text-muted">123 Main St, City</small><br>
                        <small class="text-muted">Tel: (02) 1234-5678</small>
                        <hr>
                    </div>
                    <div id="receiptOrderDetails"></div>
                    <hr>
                    <div id="receiptItems"></div>
                    <hr>
                    <div id="receiptTotals"></div>
                    <hr>
                    <div class="text-center text-muted small">
                        Thank you for shopping with us!<br>
                        Have a great day!
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="printReceiptBtn">
                    <i class="bi bi-printer"></i> Print
                </button>
                <button type="button" class="btn btn-yellow-primary btn-sm" id="newSaleBtn">
                    <i class="bi bi-cart-plus"></i> New Sale
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/pos_terminal.php';
