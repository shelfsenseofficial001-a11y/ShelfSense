<?php
$title = 'Checkout - ShelfSense POS';
$pageTitle = 'Checkout';
$activePage = 'checkout';
$additional_js = '<script src="/ShelfSense/public/assets/js/pos/pos.js"></script>';

$content = <<<'EOT'
<div class="row g-3">
    <!-- Left: Product Grid -->
    <div class="col-lg-8">
        <div class="modern-card p-3">
            <!-- Search & Barcode - Separate -->
            <div class="search-barcode-row mb-3 flex-shrink-0">
                <div class="search-wrapper">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <div class="autocomplete-wrapper" style="flex:1;">
                            <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by name..." autocomplete="off">
                            <div class="autocomplete-dropdown" id="searchAutocomplete"></div>
                        </div>
                    </div>
                </div>
                <div class="barcode-wrapper">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="barcodeInput" class="form-control barcode-input" placeholder="Scan barcode..." autocomplete="off">
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <button class="btn btn-yellow-outline btn-sm" id="refreshProductsBtn">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Product Grid -->
            <div class="product-grid" id="productGrid">
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
    
    <!-- Right: Cart Panel -->
    <div class="col-lg-4">
        <div class="modern-card p-0 d-flex flex-column">
            <div class="card-header bg-transparent border-bottom flex-shrink-0">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-cart-fill text-yellow me-2"></i>
                    Cart <span class="badge bg-primary" id="cartCount">0</span>
                </h6>
            </div>
            <div class="cart-panel" id="cartPanel">
                <div class="text-center text-muted py-4" id="emptyCartMessage">
                    <i class="bi bi-cart3 fs-3 d-block mb-2"></i>
                    Cart is empty
                </div>
                <div id="cartItems" style="display:none;"></div>
            </div>
            <div class="cart-total-row flex-shrink-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Total:</span>
                    <span class="fs-4 fw-bold text-yellow" id="cartTotal">₱0.00</span>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-outline-danger btn-sm flex-grow-1" id="clearCartBtn">
                        <i class="bi bi-trash"></i> Clear
                    </button>
                    <button class="btn btn-yellow-primary flex-grow-1" id="checkoutBtn" disabled>
                        <i class="bi bi-credit-card"></i> Pay
                    </button>
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

require_once __DIR__ . '/../../layouts/cashier.php';