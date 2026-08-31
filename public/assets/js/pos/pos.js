// ============================================
// POS - MAIN CHECKOUT LOGIC
// ============================================

console.log('✅ pos.js loaded');

let cart = [];
let currentPage = 1;
let currentSearch = '';
let activeCategory = 0;
let selectedPaymentMethod = 'cash';
let currentOrderId = null;

// Autocomplete variables
let autocompleteResults = [];
let selectedIndex = -1;
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    loadTopBarData();
    loadCategories();
    loadProducts();
    setupEventListeners();
});

// ============================================
// TOP BAR: SHIFT, STATS, RECENT ORDERS
// ============================================

function loadTopBarData() {
    fetch('?page=api_get_my_shift')
        .then(response => response.json())
        .then(data => {
            const label = document.getElementById('myShiftLabel');
            if (!label) return;
            if (data.success) {
                const s = data.data;
                if (s.is_rest_day || !s.time_in) {
                    label.textContent = 'Rest Day';
                } else {
                    label.textContent = `${formatTime(s.time_in)} – ${formatTime(s.time_out)}`;
                }
            } else {
                label.textContent = '—';
            }
        })
        .catch(() => {
            const label = document.getElementById('myShiftLabel');
            if (label) label.textContent = '—';
        });

    fetch('?page=api_get_daily_sales')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            const d = data.data;
            const salesLabel = document.getElementById('todaySalesLabel');
            const txLabel = document.getElementById('todayTransactionsLabel');
            if (salesLabel) salesLabel.textContent = '₱' + d.today.total_sales.toFixed(2);
            if (txLabel) txLabel.textContent = d.today.transaction_count;
            renderRecentOrders(d.recent_transactions);
        })
        .catch(() => {
            const row = document.getElementById('recentOrdersRow');
            if (row) row.innerHTML = '<div class="text-danger small py-2">Failed to load recent orders.</div>';
        });
}

function formatTime(time24) {
    if (!time24) return '—';
    const [h, m] = time24.split(':').map(Number);
    const d = new Date(2000, 0, 1, h, m);
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}

function renderRecentOrders(orders) {
    const row = document.getElementById('recentOrdersRow');
    if (!row) return;
    if (!orders || orders.length === 0) {
        row.innerHTML = '<div class="text-muted small py-2">No orders yet today.</div>';
        return;
    }

    // Let the cashier reprint their last receipt even after a page refresh.
    if (!currentOrderId) {
        currentOrderId = orders[0].id;
        const printLastBtn = document.getElementById('printLastReceiptBtn');
        if (printLastBtn) printLastBtn.disabled = false;
    }

    row.innerHTML = orders.slice(0, 8).map(order => {
        const time = new Date(order.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        return `
            <div class="recent-order-card">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                    <strong class="recent-order-number" title="#${escapeHtml(order.order_number)}">#${escapeHtml(order.order_number)}</strong>
                    <span class="badge bg-success">Paid</span>
                </div>
                <div class="text-muted small">${order.item_count || 0} items · ${time}</div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="fw-bold text-yellow">₱${parseFloat(order.total).toFixed(2)}</span>
                    <a href="?page=pos_orders&view=${order.id}" class="recent-order-view-more">View More</a>
                </div>
            </div>
        `;
    }).join('');
}

// ============================================
// CATEGORIES
// ============================================

function loadCategories() {
    fetch('?page=api_get_categories')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            const row = document.getElementById('categoryRow');
            if (!row) return;
            const categories = data.data.categories || [];
            let html = `<button class="pos-category-chip active" data-category="0"><i class="bi bi-grid-fill"></i> All</button>`;
            categories.forEach(cat => {
                html += `<button class="pos-category-chip" data-category="${cat.id}">${escapeHtml(cat.name)}</button>`;
            });
            row.innerHTML = html;
            row.querySelectorAll('.pos-category-chip').forEach(chip => {
                chip.addEventListener('click', function() {
                    row.querySelectorAll('.pos-category-chip').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    activeCategory = parseInt(this.dataset.category) || 0;
                    loadProducts(currentSearch, 1);
                });
            });
        })
        .catch(error => console.error('Error loading categories:', error));
}

// ============================================
// EVENT LISTENERS
// ============================================

function setupEventListeners() {
    // Search input - Autocomplete
    const searchInput = document.getElementById('searchInput');
    const autocompleteDropdown = document.getElementById('searchAutocomplete');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            clearTimeout(searchTimeout);

            if (query.length < 1) {
                autocompleteDropdown.classList.remove('show');
                autocompleteResults = [];
                selectedIndex = -1;
                currentPage = 1;
                loadProducts('');
                return;
            }

            searchTimeout = setTimeout(() => {
                fetchAutocompleteSuggestions(query);
            }, 300);
        });

        // Keyboard navigation for autocomplete
        searchInput.addEventListener('keydown', function(e) {
            const items = autocompleteDropdown.querySelectorAll('.item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length > 0) {
                    selectedIndex = (selectedIndex + 1) % items.length;
                    highlightItem(items);
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length > 0) {
                    selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                    highlightItem(items);
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && selectedIndex < items.length) {
                    const selectedItem = items[selectedIndex];
                    const index = parseInt(selectedItem.dataset.index);
                    const product = autocompleteResults[index];
                    if (product) {
                        selectAutocompleteItem(product);
                    }
                } else if (autocompleteResults.length > 0) {
                    // Select first result if nothing highlighted
                    const firstProduct = autocompleteResults[0];
                    if (firstProduct) {
                        selectAutocompleteItem(firstProduct);
                    }
                }
            } else if (e.key === 'Escape') {
                autocompleteDropdown.classList.remove('show');
            }
        });
        
        // Click outside to close dropdown
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
                autocompleteDropdown.classList.remove('show');
            }
        });
    }
    
    // Barcode input
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcode = this.value.trim();
                if (barcode) {
                    addProductByBarcode(barcode);
                    this.value = '';
                }
            }
        });
        
        // Auto-focus barcode input
        setTimeout(() => barcodeInput.focus(), 500);
    }
    
    // Refresh products
    document.getElementById('refreshProductsBtn')?.addEventListener('click', function() {
        const search = document.getElementById('searchInput').value.trim();
        loadProducts(search);
    });

    // Grid / List view toggle
    document.querySelectorAll('#productViewToggle button').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === productViewMode);
        btn.addEventListener('click', function() {
            setProductViewMode(this.dataset.view);
        });
    });
    
    // Clear cart
    document.getElementById('clearCartBtn')?.addEventListener('click', function() {
        clearCart();
    });
    
    // Checkout / Pay button
    document.getElementById('checkoutBtn')?.addEventListener('click', function() {
        openPaymentModal();
    });
    
    // Payment method selection
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            selectedPaymentMethod = this.dataset.method;
            togglePaymentFields(selectedPaymentMethod);
        });
    });
    
    // Amount tendered - calculate change
    document.getElementById('amountTendered')?.addEventListener('input', function() {
        calculateChange();
    });
    
    // Complete payment
    document.getElementById('completePaymentBtn')?.addEventListener('click', function() {
        completePayment();
    });
    
    // Cancel payment (void order) - Shows confirmation
    document.getElementById('cancelPaymentBtn')?.addEventListener('click', function() {
        if (cart.length === 0) {
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            return;
        }
        
        Swal.fire({
            title: 'Void Transaction?',
            text: 'This will clear your cart and cancel the transaction. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Void Transaction',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                clearCart();
                showToast('Transaction voided', 'info');
            }
        });
    });
    
    // New Sale
    document.getElementById('newSaleBtn')?.addEventListener('click', function() {
        clearCart();
        bootstrap.Modal.getInstance(document.getElementById('receiptModal')).hide();
        document.getElementById('barcodeInput')?.focus();
    });
    
    // Print receipt - only print the receipt content
    document.getElementById('printReceiptBtn')?.addEventListener('click', function() {
        window.print();
    });

    // Re-open the receipt for the last order placed this session
    document.getElementById('printLastReceiptBtn')?.addEventListener('click', function() {
        if (!currentOrderId) return;
        fetch(`?page=api_get_order&id=${currentOrderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showReceipt(data.data.order);
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Could not load the receipt.' });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
            });
    });
}

// ============================================
// FETCH AUTOCOMPLETE SUGGESTIONS
// ============================================

function fetchAutocompleteSuggestions(query) {
    const params = new URLSearchParams({
        p: 1,
        limit: 10,
        search: query
    });
    
    fetch(`?page=api_get_products&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                autocompleteResults = data.data.products || [];
                renderAutocomplete(autocompleteResults);
            }
        })
        .catch(error => {
            console.error('Autocomplete error:', error);
        });
}

// ============================================
// RENDER AUTOCOMPLETE DROPDOWN
// ============================================

function renderAutocomplete(results) {
    const dropdown = document.getElementById('searchAutocomplete');
    
    if (!results || results.length === 0) {
        dropdown.innerHTML = `<div class="no-results">No products found</div>`;
        dropdown.classList.add('show');
        return;
    }
    
    let html = '';
    results.forEach((product, index) => {
        const isSelected = index === selectedIndex;
        const isOutOfStock = product.stock_quantity <= 0;
        html += `
            <div class="item ${isSelected ? 'selected' : ''}" data-index="${index}" data-id="${product.id}" style="${isOutOfStock ? 'opacity:0.5;' : ''}">
                <div>
                    <div class="item-name">${escapeHtml(product.name)}</div>
                    <small class="item-stock">${product.stock_quantity} in stock</small>
                </div>
                <div class="item-price">₱${parseFloat(product.price).toFixed(2)}</div>
            </div>
        `;
    });
    
    dropdown.innerHTML = html;
    dropdown.classList.add('show');
    
    dropdown.querySelectorAll('.item').forEach(item => {
        item.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            const product = autocompleteResults[index];
            if (product) {
                selectAutocompleteItem(product);
            }
        });
    });
}

// ============================================
// SELECT AUTOCOMPLETE ITEM
// ============================================

function selectAutocompleteItem(product) {
    const searchInput = document.getElementById('searchInput');
    searchInput.value = product.name;
    window.ShelfSenseUpdateClearBtn?.(searchInput);
    document.getElementById('searchAutocomplete').classList.remove('show');
    selectedIndex = -1;
    autocompleteResults = [];

    // Just filter the product list to this item -- adding to cart is a
    // separate, explicit action (the qty stepper on the product card).
    loadProducts(product.name);
}

// ============================================
// HIGHLIGHT AUTOCOMPLETE ITEM
// ============================================

function highlightItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('selected', index === selectedIndex);
        if (index === selectedIndex) {
            item.scrollIntoView({ block: 'nearest' });
        }
    });
}

// ============================================
// LOAD PRODUCTS
// ============================================

function loadProducts(search = '', page = 1) {
    currentPage = page;
    currentSearch = search;
    const grid = document.getElementById('productGrid');

    grid.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading products...</p>
        </div>
    `;

    const params = new URLSearchParams({
        p: page,
        limit: 24
    });
    if (search) params.append('search', search);
    if (activeCategory > 0) params.append('category', activeCategory);

    fetch(`?page=api_get_products&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderProducts(data.data.products);
                renderPagination(data.data.pagination);
            } else {
                grid.innerHTML = `
                    <div class="text-center text-danger py-4">
                        ${data.message || 'Failed to load products'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            grid.innerHTML = `
                <div class="text-center text-danger py-4">
                    An error occurred. Please try again.
                </div>
            `;
        });
}

// ============================================
// RENDER PRODUCTS
// ============================================

let lastRenderedProducts = [];
let productViewMode = localStorage.getItem('pos_product_view') || 'grid';

function renderProducts(products) {
    const grid = document.getElementById('productGrid');
    const info = document.getElementById('productInfo');

    lastRenderedProducts = products || [];

    if (!products || products.length === 0) {
        grid.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                No products found
            </div>
        `;
        info.textContent = '0 products';
        return;
    }

    info.textContent = `${products.length} products loaded`;
    grid.innerHTML = productViewMode === 'list' ? renderProductsAsList(products) : renderProductsAsGrid(products);
}

function renderProductsAsGrid(products) {
    let html = '<div class="pos-product-tiles">';
    products.forEach(product => {
        const isOutOfStock = product.stock_quantity <= 0;
        const qty = getCartQty(product.id);
        html += `
            <div class="pos-product-tile modern-card p-0 ${isOutOfStock ? 'out-of-stock' : ''}" data-product-id="${product.id}">
                <div class="pos-product-tile-img">
                    ${product.image_url
                        ? `<img src="${product.image_url}" alt="${escapeHtml(product.name)}">`
                        : `<div class="placeholder-image"><i class="bi bi-box"></i></div>`
                    }
                </div>
                <div class="pos-product-tile-body">
                    <div class="pos-product-tile-name" title="${escapeHtml(product.name)}">${escapeHtml(product.name)}</div>
                    ${product.category_name ? `<span class="pos-product-category-chip pos-tile-category-chip">${escapeHtml(product.category_name)}</span>` : ''}
                    <div class="pos-product-tile-price">₱${parseFloat(product.price).toFixed(2)}</div>
                </div>
                ${isOutOfStock ? `
                    <div class="pos-product-tile-oos">Out of Stock</div>
                ` : `
                    <div class="pos-product-tile-qty">
                        <button class="qty-minus" ${qty === 0 ? 'disabled' : ''} onclick="stepProductQty(${product.id}, -1)">−</button>
                        <input type="number" class="qty-value" min="0" value="${qty}" onchange="setProductQty(${product.id}, this.value)">
                        <button class="qty-plus" onclick="stepProductQty(${product.id}, 1)">+</button>
                    </div>
                `}
            </div>
        `;
    });
    html += '</div>';
    return html;
}

function renderProductsAsList(products) {
    let html = '<div class="pos-product-list">';
    products.forEach(product => {
        const isOutOfStock = product.stock_quantity <= 0;
        const qty = getCartQty(product.id);
        html += `
            <div class="pos-product-list-row ${isOutOfStock ? 'out-of-stock' : ''}" data-product-id="${product.id}">
                <div class="pos-product-list-img">
                    ${product.image_url
                        ? `<img src="${product.image_url}" alt="${escapeHtml(product.name)}">`
                        : `<div class="placeholder-image"><i class="bi bi-box"></i></div>`
                    }
                </div>
                <div class="pos-product-list-info">
                    <div class="pos-product-list-name" title="${escapeHtml(product.name)}">${escapeHtml(product.name)}</div>
                    <div class="pos-product-list-meta">
                        ${product.category_name ? `<span class="pos-product-category-chip">${escapeHtml(product.category_name)}</span>` : ''}
                        <span class="pos-product-list-stock">${isOutOfStock ? 'Out of Stock' : product.stock_quantity + ' in stock'}</span>
                    </div>
                </div>
                <div class="pos-product-list-price">₱${parseFloat(product.price).toFixed(2)}</div>
                ${isOutOfStock ? '' : `
                    <div class="pos-product-tile-qty pos-product-list-qty">
                        <button class="qty-minus" ${qty === 0 ? 'disabled' : ''} onclick="stepProductQty(${product.id}, -1)">−</button>
                        <input type="number" class="qty-value" min="0" value="${qty}" onchange="setProductQty(${product.id}, this.value)">
                        <button class="qty-plus" onclick="stepProductQty(${product.id}, 1)">+</button>
                    </div>
                `}
            </div>
        `;
    });
    html += '</div>';
    return html;
}

function setProductViewMode(mode) {
    if (mode !== 'grid' && mode !== 'list') return;
    productViewMode = mode;
    localStorage.setItem('pos_product_view', mode);
    document.querySelectorAll('#productViewToggle button').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === mode);
    });
    renderProducts(lastRenderedProducts);
}

function getCartQty(productId) {
    const item = cart.find(i => i.product_id === productId);
    return item ? item.quantity : 0;
}

function stepProductQty(productId, delta) {
    const product = lastRenderedProducts.find(p => p.id === productId);
    if (!product) return;
    if (delta > 0) {
        addToCart(product.id, product.name, parseFloat(product.price));
    } else {
        const index = cart.findIndex(i => i.product_id === productId);
        if (index >= 0) updateQuantity(index, -1);
    }
}

function setProductQty(productId, value) {
    const product = lastRenderedProducts.find(p => p.id === productId);
    if (!product) return;
    const qty = Math.floor(Number(value));
    const index = cart.findIndex(i => i.product_id === productId);

    if (!Number.isFinite(qty) || qty <= 0) {
        if (index >= 0) cart.splice(index, 1);
    } else if (index >= 0) {
        cart[index].quantity = qty;
    } else {
        cart.push({ product_id: product.id, name: product.name, price: parseFloat(product.price), quantity: qty });
    }
    updateCart();
}

function syncProductTiles() {
    document.querySelectorAll('#productGrid [data-product-id]').forEach(tile => {
        const productId = parseInt(tile.dataset.productId);
        const qty = getCartQty(productId);
        const valueEl = tile.querySelector('.qty-value');
        const minusBtn = tile.querySelector('.qty-minus');
        if (valueEl) valueEl.value = qty;
        if (minusBtn) minusBtn.disabled = qty === 0;
    });
}

// ============================================
// RENDER PAGINATION
// ============================================

function renderPagination(pagination) {
    const container = document.getElementById('productPagination');
    const info = document.getElementById('productInfo');
    
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        return;
    }
    
    info.textContent = `Page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} total)`;
    
    let html = '';
    if (pagination.currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
    }
    
    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);
    
    for (let i = start; i <= end; i++) {
        if (i === pagination.currentPage) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }
    
    if (pagination.currentPage < pagination.totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage + 1}">»</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">»</span></li>`;
    }
    
    container.innerHTML = html;
    
    container.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            const search = document.getElementById('searchInput').value.trim();
            loadProducts(search, page);
        });
    });
}

// ============================================
// CART FUNCTIONS
// ============================================

function addToCart(productId, name, price) {
    const existing = cart.find(item => item.product_id === productId);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            product_id: productId,
            name: name,
            price: price,
            quantity: 1
        });
    }
    updateCart();
    showToast(`${name} added to cart`, 'success');
}

function addProductByBarcode(barcode) {
    fetch(`?page=api_get_product_by_barcode&barcode=${encodeURIComponent(barcode)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.data.product;
                addToCart(product.id, product.name, parseFloat(product.price));
                document.getElementById('barcodeInput')?.focus();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Product Not Found',
                    text: `No product found with barcode: ${barcode}`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        })
        .catch(error => {
            console.error('Error scanning barcode:', error);
        });
}

function updateCart() {
    const container = document.getElementById('cartItems');
    const emptyMessage = document.getElementById('emptyCartMessage');
    const countBadge = document.getElementById('cartCount');
    const totalDisplay = document.getElementById('cartTotal');
    const subtotalDisplay = document.getElementById('cartSubtotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (cart.length === 0) {
        container.style.display = 'none';
        emptyMessage.style.display = 'block';
        countBadge.textContent = '0';
        totalDisplay.textContent = '₱0.00';
        if (subtotalDisplay) subtotalDisplay.textContent = '₱0.00';
        checkoutBtn.disabled = true;
        syncProductTiles();
        return;
    }

    container.style.display = 'block';
    emptyMessage.style.display = 'none';

    let total = 0;
    let itemCount = 0;
    let html = '';
    
    cart.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        itemCount += item.quantity;
        
        html += `
            <div class="cart-item">
                <div class="cart-item-top">
                    <div class="item-name">${escapeHtml(item.name)}</div>
                    <button class="cart-item-remove" onclick="removeFromCart(${index})" title="Remove">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="cart-item-bottom">
                    <div class="item-price">₱${item.price.toFixed(2)} each</div>
                    <div class="cart-item-qty">${item.quantity}</div>
                    <div class="cart-item-subtotal">₱${subtotal.toFixed(2)}</div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    countBadge.textContent = itemCount;
    totalDisplay.textContent = '₱' + total.toFixed(2);
    if (subtotalDisplay) subtotalDisplay.textContent = '₱' + total.toFixed(2);
    checkoutBtn.disabled = false;
    syncProductTiles();
}

function updateQuantity(index, delta) {
    if (index < 0 || index >= cart.length) return;
    cart[index].quantity += delta;
    if (cart[index].quantity <= 0) {
        cart.splice(index, 1);
    }
    updateCart();
}


function removeFromCart(index) {
    if (index < 0 || index >= cart.length) return;
    cart.splice(index, 1);
    updateCart();
}

function clearCart() {
    cart = [];
    updateCart();
    document.getElementById('barcodeInput')?.focus();
}

// ============================================
// TOAST / NOTIFICATION
// ============================================

function showToast(message, type = 'success') {
    Swal.fire({
        icon: type,
        title: message,
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// ============================================
// PAYMENT MODAL
// ============================================

function openPaymentModal() {
    if (cart.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Cart Empty', text: 'Add items to the cart first.' });
        return;
    }
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    document.getElementById('paymentTotal').textContent = '₱' + total.toFixed(2);
    document.getElementById('amountTendered').value = total.toFixed(2);
    document.getElementById('changeDisplay').textContent = '₱0.00';
    document.getElementById('paymentReference').value = '';
    document.getElementById('paymentNotes').value = '';
    
    selectedPaymentMethod = 'cash';
    document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.payment-method-btn[data-method="cash"]')?.classList.add('active');
    togglePaymentFields('cash');
    
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function togglePaymentFields(method) {
    const cashFields = document.getElementById('cashFields');
    const refFields = document.getElementById('referenceFields');
    
    if (method === 'cash') {
        cashFields.style.display = 'block';
        refFields.style.display = 'none';
    } else {
        cashFields.style.display = 'none';
        refFields.style.display = 'block';
    }
}

function calculateChange() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
    const change = Math.max(0, tendered - total);
    document.getElementById('changeDisplay').textContent = '₱' + change.toFixed(2);
}

// ============================================
// COMPLETE PAYMENT
// ============================================

function completePayment() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const amountTendered = parseFloat(document.getElementById('amountTendered').value) || 0;
    const notes = document.getElementById('paymentNotes').value.trim();
    const paymentReference = document.getElementById('paymentReference').value.trim();
    
    if (selectedPaymentMethod === 'cash' && amountTendered < total) {
        Swal.fire({
            icon: 'warning',
            title: 'Insufficient Amount',
            text: `Amount tendered (₱${amountTendered.toFixed(2)}) is less than total (₱${total.toFixed(2)})`
        });
        return;
    }
    
    if (notes.length > 500) {
        Swal.fire({ icon: 'warning', title: 'Notes Too Long', text: 'Notes cannot exceed 500 characters.' });
        return;
    }
    
    const btn = document.getElementById('completePaymentBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
    
    const orderData = {
        items: cart.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        })),
        payment_method: selectedPaymentMethod,
        amount_paid: selectedPaymentMethod === 'cash' ? amountTendered : total,
        notes: notes,
        payment_reference: paymentReference || null
    };
    
    fetch('?page=api_create_order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => { throw new Error(data.message || 'Server error'); });
        }
        return response.json();
    })
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Pay';
        
        if (data.success) {
            const order = data.data.order;
            currentOrderId = order.id;
            const printLastBtn = document.getElementById('printLastReceiptBtn');
            if (printLastBtn) printLastBtn.disabled = false;

            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            showReceipt(order);
            cart = [];
            updateCart();
            
            // Refresh product grid to update stock counts, and the top bar
            // (Today's Sales / Recent Orders) to reflect the new order.
            const search = document.getElementById('searchInput').value.trim();
            loadProducts(search, currentPage);
            loadTopBarData();

        } else {
            Swal.fire({
                icon: 'error',
                title: 'Payment Failed',
                text: data.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Pay';
        console.error('Error completing payment:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Something went wrong. Please try again.'
        });
    });
}

// ============================================
// RECEIPT
// ============================================

function showReceipt(order) {
    const orderDetails = document.getElementById('receiptOrderDetails');
    const itemsContainer = document.getElementById('receiptItems');
    const totalsContainer = document.getElementById('receiptTotals');
    
    const date = new Date(order.created_at).toLocaleString();
    orderDetails.innerHTML = `
        <div>Cashier: ${order.first_name} ${order.last_name}</div>
        <div>Date: ${date}</div>
        <div>Order #: ${order.order_number}</div>
        <div>Payment: ${order.payment_method.toUpperCase()}</div>
    `;
    
    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            itemsHtml += `
                <div style="display:flex;justify-content:space-between;font-size:0.85rem;padding:2px 0;">
                    <span>${item.quantity}x ${item.name}</span>
                    <span>₱${parseFloat(item.subtotal).toFixed(2)}</span>
                </div>
            `;
        });
    }
    itemsContainer.innerHTML = itemsHtml;
    
    totalsContainer.innerHTML = `
        <div style="display:flex;justify-content:space-between;font-weight:600;">
            <span>Subtotal:</span>
            <span>₱${parseFloat(order.subtotal).toFixed(2)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:var(--brand-yellow-hover);">
            <span>Total:</span>
            <span>₱${parseFloat(order.total).toFixed(2)}</span>
        </div>
        ${order.amount_paid > 0 ? `
            <div style="display:flex;justify-content:space-between;">
                <span>Amount Paid:</span>
                <span>₱${parseFloat(order.amount_paid).toFixed(2)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;color:#dc2626;">
                <span>Change:</span>
                <span>₱${parseFloat(order.change_amount).toFixed(2)}</span>
            </div>
        ` : ''}
    `;
    
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}

// ============================================
// HELPERS
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    if (!text) return '';
    return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}