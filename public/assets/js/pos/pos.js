// ============================================
// POS - MAIN CHECKOUT LOGIC
// ============================================

console.log('✅ pos.js loaded');

let cart = [];
let currentPage = 1;
let selectedPaymentMethod = 'cash';
let currentOrderId = null;

// Autocomplete variables
let autocompleteResults = [];
let selectedIndex = -1;
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    setupEventListeners();
});

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
    document.getElementById('searchAutocomplete').classList.remove('show');
    selectedIndex = -1;
    autocompleteResults = [];
    
    // Add to cart
    addToCart(product.id, product.name, parseFloat(product.price));
    
    // Keep search input value and reload products with search
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
    const grid = document.getElementById('productGrid');
    
    grid.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading products...</p>
        </div>
    `;
    
    const params = new URLSearchParams({
        p: page,
        limit: 20
    });
    if (search) params.append('search', search);
    
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

function renderProducts(products) {
    const grid = document.getElementById('productGrid');
    const info = document.getElementById('productInfo');
    
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
    
    let html = '<div class="row g-2">';
    products.forEach(product => {
        const isOutOfStock = product.stock_quantity <= 0;
        html += `
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card modern-card p-0 ${isOutOfStock ? 'out-of-stock' : ''}" 
                     onclick="${isOutOfStock ? '' : `addToCart(${product.id}, '${escapeJs(product.name)}', ${product.price})`}">
                    <div class="product-image-wrapper">
                        ${product.image_url 
                            ? `<img src="${product.image_url}" class="product-image" alt="${escapeHtml(product.name)}">`
                            : `<div class="placeholder-image"><i class="bi bi-box"></i></div>`
                        }
                    </div>
                    <div class="p-2">
                        <div class="product-name" title="${escapeHtml(product.name)}">${escapeHtml(product.name)}</div>
                        <div class="product-price">₱${parseFloat(product.price).toFixed(2)}</div>
                        <div class="product-stock">${product.stock_quantity} in stock</div>
                        <button class="btn btn-sm btn-yellow-primary w-100 mt-1" 
                                ${isOutOfStock ? 'disabled' : ''}
                                onclick="event.stopPropagation(); addToCart(${product.id}, '${escapeJs(product.name)}', ${product.price})">
                            ${isOutOfStock ? 'Out of Stock' : '<i class="bi bi-plus"></i> Add'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    grid.innerHTML = html;
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
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cart.length === 0) {
        container.style.display = 'none';
        emptyMessage.style.display = 'block';
        countBadge.textContent = '0';
        totalDisplay.textContent = '₱0.00';
        checkoutBtn.disabled = true;
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
                <div class="item-info">
                    <div class="item-name">${escapeHtml(item.name)}</div>
                    <div class="item-price">₱${item.price.toFixed(2)} each</div>
                </div>
                <div class="qty-control">
                    <button onclick="updateQuantity(${index}, -1)">−</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${index}, 1)">+</button>
                </div>
                <div class="fw-bold" style="min-width:60px;text-align:right;">
                    ₱${subtotal.toFixed(2)}
                </div>
                <button class="btn btn-sm btn-link text-danger p-0" onclick="removeFromCart(${index})">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `;
    });
    
    container.innerHTML = html;
    countBadge.textContent = itemCount;
    totalDisplay.textContent = '₱' + total.toFixed(2);
    checkoutBtn.disabled = false;
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
            
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            showReceipt(order);
            cart = [];
            updateCart();
            
            // Refresh product grid to update stock counts
            const search = document.getElementById('searchInput').value.trim();
            loadProducts(search, currentPage);
            
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