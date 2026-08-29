// ============================================
// STORE MANAGER - REQUISITIONS (5-tab redesign)
// ============================================

console.log('✅ store_manager/requisitions.js loaded');

// Each listing tab maps to a real status grouping understood by get_requisitions.php.
// 'mine' has no group (shows every status, scoped to the current user) so its own
// status <select> filter is used instead.
const TAB_CONFIGS = {
    'mine': { scope: 'mine', group: '' },
    'pending-supplier': { scope: 'all', group: 'pending_supplier' },
    'awaiting-finance': { scope: 'all', group: 'awaiting_finance' },
    'history': { scope: 'all', group: 'history' },
};

const tabState = {
    'mine': { page: 1 },
    'pending-supplier': { page: 1 },
    'awaiting-finance': { page: 1 },
    'history': { page: 1 },
};

let cart = [];
let products = [];
let suppliers = [];
let selectedSupplierId = null;

document.addEventListener('DOMContentLoaded', function () {
    setupTabs();
    setupCreateTab();
    setupModals();
    loadTab('mine', 1);
});

// ============================================
// TAB WIRING
// ============================================

function setupTabs() {
    document.querySelectorAll('#requisitionTabs button[data-tab-key]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function () {
            const key = this.dataset.tabKey;
            if (key === 'create') {
                if (!suppliers.length) loadProductsForRequisition();
                return;
            }
            loadTab(key, tabState[key].page);
        });
    });

    document.querySelectorAll('[data-tab-panel]').forEach(panel => {
        const key = panel.dataset.tabPanel;
        panel.querySelectorAll('[data-filter]').forEach(el => {
            el.addEventListener('change', () => loadTab(key, 1));
        });
        const searchInput = panel.querySelector('[data-filter="search"]');
        if (searchInput) {
            let debounce;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(() => loadTab(key, 1), 400);
            });
        }
        const refreshBtn = panel.querySelector('[data-action="refresh"]');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                panel.querySelectorAll('[data-filter]').forEach(el => el.value = '');
                loadTab(key, 1);
            });
        }
    });
}

function loadTab(tabKey, page) {
    const config = TAB_CONFIGS[tabKey];
    if (!config) return;
    tabState[tabKey].page = page;

    const panel = document.querySelector(`[data-tab-panel="${tabKey}"]`);
    const cardsContainer = panel.querySelector('[data-cards-container]');
    cardsContainer.innerHTML = `
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading requisitions...</p>
        </div>
    `;

    const params = new URLSearchParams({ p: page, limit: 12, scope: config.scope });
    if (config.group) params.append('group', config.group);

    panel.querySelectorAll('[data-filter]').forEach(el => {
        const key = el.dataset.filter;
        if (el.value) params.append(key, el.value);
    });

    fetch(`?page=api_get_requisitions&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderCards(cardsContainer, data.data.requisitions, tabKey);
                smRenderPagination(
                    panel.querySelector('[data-pagination]'),
                    panel.querySelector('[data-info]'),
                    data.data.pagination,
                    'requisitions',
                    (p) => loadTab(tabKey, p)
                );
                renderTabStats(tabKey, data.data.summary);
            } else {
                cardsContainer.innerHTML = `<div style="grid-column:1/-1;">${smErrorState(data.message || 'Failed to load requisitions')}</div>`;
            }
        })
        .catch(() => {
            cardsContainer.innerHTML = `<div style="grid-column:1/-1;">${smErrorState()}</div>`;
        });
}

function renderTabStats(tabKey, summary) {
    if (!summary) return;

    if (tabKey === 'mine') {
        const el = document.querySelector('#mineTab [data-stats-container]');
        if (!el) return;
        el.innerHTML = `
            <div class="sm-stat-card"><div class="sm-stat-label">Total</div><div class="sm-stat-number primary">${summary.total}</div></div>
            <div class="sm-stat-card"><div class="sm-stat-label">Pending Supplier</div><div class="sm-stat-number warning">${summary.pending_supplier}</div></div>
            <div class="sm-stat-card"><div class="sm-stat-label">Awaiting Finance</div><div class="sm-stat-number" style="color:#9a3412;">${summary.awaiting_finance}</div></div>
            <div class="sm-stat-card"><div class="sm-stat-label">History</div><div class="sm-stat-number success">${summary.history}</div></div>
        `;
    }

    if (tabKey === 'history') {
        const el = document.querySelector('#historyTab [data-history-stats]');
        if (!el) return;
        el.innerHTML = `
            <div class="sm-stat-card"><div class="sm-stat-label">Total Requisitions</div><div class="sm-stat-number primary">${summary.history}</div></div>
            <div class="sm-stat-card"><div class="sm-stat-label">Completed</div><div class="sm-stat-number success">${summary.completed}</div></div>
            <div class="sm-stat-card"><div class="sm-stat-label">Rejected</div><div class="sm-stat-number danger">${summary.rejected}</div></div>
            <div class="sm-stat-card"><div class="sm-stat-label">Total Spent</div><div class="sm-stat-number success">${smCurrency(summary.total_spent)}</div></div>
        `;
    }
}

// ============================================
// REQUISITION CARDS
// ============================================

function renderCards(container, requisitions, tabKey) {
    if (!requisitions || requisitions.length === 0) {
        container.innerHTML = `<div style="grid-column:1/-1;">${smEmptyState('No requisitions found for this view.')}</div>`;
        return;
    }

    container.innerHTML = requisitions.map(req => buildRequisitionCard(req)).join('');

    container.querySelectorAll('.view-requisition-btn').forEach(btn => {
        btn.addEventListener('click', () => viewRequisition(btn.dataset.id));
    });
}

function buildRequisitionCard(req) {
    const total = smCurrency(req.total);
    let actions = `<button class="btn btn-sm btn-outline-primary view-requisition-btn" data-id="${req.id}"><i class="bi bi-eye"></i> View Details</button>`;

    return `
        <div class="sm-req-card" data-id="${req.id}">
            <div class="sm-req-header">
                <div>
                    <div class="sm-req-number">${escapeHtmlSM(req.requisition_number)}</div>
                    <div class="sm-req-supplier">${escapeHtmlSM(req.company_name)}</div>
                </div>
                ${smStatusBadge(req.status)}
            </div>
            <div class="sm-req-meta">
                <div>Order Date: <strong>${smFormatDate(req.order_date)}</strong></div>
                <div>Expected: <strong>${smFormatDate(req.expected_delivery)}</strong></div>
                ${req.actual_delivery_date ? `<div>Delivered: <strong>${smFormatDate(req.actual_delivery_date)}</strong></div>` : ''}
                <div>Items: <strong>${req.item_count ?? 0}</strong></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="sm-req-total">${total}</div>
            </div>
            <div class="sm-req-actions">${actions}</div>
        </div>
    `;
}

// ============================================
// REQUISITION DETAIL MODAL (with real-data timeline)
// ============================================

function viewRequisition(id) {
    const modal = document.getElementById('requisitionDetailModal');
    const body = document.getElementById('requisitionDetailBody');
    const footer = document.getElementById('requisitionDetailFooter');

    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    footer.innerHTML = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;

    new bootstrap.Modal(modal).show();

    fetch(`?page=api_get_requisition&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderRequisitionDetail(data.data.requisition);
            } else {
                body.innerHTML = smErrorState(data.message || 'Failed to load requisition details');
            }
        })
        .catch(() => { body.innerHTML = smErrorState(); });
}

function renderRequisitionDetail(req) {
    const body = document.getElementById('requisitionDetailBody');
    const footer = document.getElementById('requisitionDetailFooter');

    let itemsHtml = '';
    if (req.items && req.items.length > 0) {
        req.items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>${escapeHtmlSM(item.store_product_name)}</td>
                    <td>${escapeHtmlSM(item.supplier_product_name)}</td>
                    <td>${item.quantity}</td>
                    <td>${smCurrency(item.unit_price)}</td>
                    <td>${smCurrency(item.total)}</td>
                    <td>${item.received_quantity || 0}</td>
                </tr>
            `;
        });
    } else {
        itemsHtml = '<tr><td colspan="6" class="text-center text-muted">No items</td></tr>';
    }

    const invoiceHtml = req.invoice ? `
        <p class="mb-1"><strong>Invoice #:</strong> ${escapeHtmlSM(req.invoice.invoice_number)} — ${smStatusBadgeRaw(req.invoice.status)}</p>
        <p class="mb-1"><strong>Invoice Total:</strong> ${smCurrency(req.invoice.total)} &nbsp; <strong>Due:</strong> ${smFormatDate(req.invoice.due_date)}</p>
    ` : '<p class="text-muted mb-0">No invoice on file yet.</p>';

    body.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Requisition #:</strong> ${escapeHtmlSM(req.requisition_number)}</p>
                <p><strong>Supplier:</strong> ${escapeHtmlSM(req.company_name)}</p>
                <p><strong>Order Date:</strong> ${smFormatDate(req.order_date)}</p>
                <p><strong>Expected Delivery:</strong> ${smFormatDate(req.expected_delivery)}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> ${smStatusBadge(req.status)}</p>
                <p><strong>Subtotal:</strong> ${smCurrency(req.subtotal)}</p>
                <p><strong>Total:</strong> ${smCurrency(req.total)}</p>
                <p><strong>Created By:</strong> ${escapeHtmlSM(req.first_name)} ${escapeHtmlSM(req.last_name)}</p>
            </div>
        </div>
        ${req.notes ? `<p><strong>Notes:</strong> ${escapeHtmlSM(req.notes)}</p>` : ''}
        <hr>
        <h6 class="fw-bold">Items</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead><tr><th>Store Product</th><th>Supplier Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Received</th></tr></thead>
                <tbody>${itemsHtml}</tbody>
            </table>
        </div>
        <h6 class="fw-bold">Invoice</h6>
        <div class="mb-3">${invoiceHtml}</div>
        <h6 class="fw-bold">Timeline</h6>
        <div class="sm-timeline">${buildTimeline(req)}</div>
        <p class="text-muted small mb-0">Timeline shows only events the project has real timestamps for. Intermediate workflow steps without a dedicated timestamp column (e.g. exact "sent to supplier" or "shipped" time) are reflected in the current status above instead of being guessed here.</p>
    `;

    let actionsHtml = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;
    if (req.status === 'supplier_processed') {
        actionsHtml += `<button type="button" class="btn btn-warning btn-sm" id="detailForwardBtn" data-id="${req.id}"><i class="bi bi-send"></i> Forward to Finance</button>`;
    }
    if (req.status === 'paid' || req.status === 'shipped') {
        actionsHtml += `<button type="button" class="btn btn-success btn-sm" id="detailReceiveBtn" data-id="${req.id}"><i class="bi bi-box-arrow-in-down"></i> Receive Goods</button>`;
    }
    footer.innerHTML = actionsHtml;

    document.getElementById('detailForwardBtn')?.addEventListener('click', function () {
        forwardToFinanceStaff(this.dataset.id, currentActiveTabKey());
    });
    document.getElementById('detailReceiveBtn')?.addEventListener('click', function () {
        receiveGoods(this.dataset.id, currentActiveTabKey());
    });
}

function buildTimeline(req) {
    const events = [];
    events.push({ date: req.created_at, title: 'Requisition Created', icon: 'bi-file-earmark-plus' });

    if (req.payment_request && req.payment_request.requested_at) {
        events.push({ date: req.payment_request.requested_at, title: 'Payment Requested (Finance Staff)', icon: 'bi-cash' });
    }
    if (req.payment_request && req.payment_request.status === 'approved' && req.payment_request.approved_at) {
        events.push({ date: req.payment_request.approved_at, title: 'Finance Approved', icon: 'bi-check-circle' });
    }
    if (req.invoice && req.invoice.paid_at) {
        events.push({ date: req.invoice.paid_at, title: 'Invoice Paid', icon: 'bi-credit-card' });
    }
    if (req.goods_receipt && req.goods_receipt.created_at) {
        events.push({ date: req.goods_receipt.created_at, title: 'Goods Received', icon: 'bi-box-seam' });
    }

    events.sort((a, b) => new Date(a.date) - new Date(b.date));

    return events.map(e => `
        <div class="sm-timeline-item">
            <div class="sm-timeline-title"><i class="bi ${e.icon} me-1"></i>${e.title}</div>
            <div class="sm-timeline-date">${smFormatDate(e.date)}</div>
        </div>
    `).join('');
}

function smStatusBadgeRaw(status) {
    const labels = { pending: 'Pending', verified: 'Verified', paid: 'Paid', rejected: 'Rejected' };
    return `<span class="sm-status-badge status-${status === 'paid' ? 'paid' : (status === 'rejected' ? 'finance_rejected' : 'awaiting_finance_staff')}">${labels[status] || status}</span>`;
}

function currentActiveTabKey() {
    const active = document.querySelector('#requisitionTabs button.active');
    return active ? active.dataset.tabKey : 'mine';
}

// ============================================
// ACTIONS: FORWARD TO FINANCE / RECEIVE GOODS
// ============================================

function forwardToFinanceStaff(id, tabKey) {
    Swal.fire({
        title: 'Forward to Finance Staff?',
        text: 'This will send the invoice to Finance Staff for review and payment processing.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Yes, Forward',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('?page=api_forward_to_finance_staff', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        })
        .then(r => r.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('requisitionDetailModal'))?.hide();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Forwarded to Finance Staff!', timer: 1500, showConfirmButton: false });
                loadTab(tabKey || 'mine', tabState[tabKey || 'mine'].page);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to forward.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' }));
    });
}

function receiveGoods(id, tabKey) {
    fetch(`?page=api_get_requisition&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load requisition details.' });
                return;
            }
            const req = data.data.requisition;
            let itemsHtml = '';
            req.items.forEach(item => {
                const remaining = item.quantity - (item.received_quantity || 0);
                itemsHtml += `
                    <div class="row g-2 mb-2" style="color: var(--text-main);">
                        <div class="col-md-6">
                            <strong style="color: var(--bg-body);">${escapeHtmlSM(item.store_product_name)}</strong>
                            <br><small style="color: var(--text-muted);">Ordered: ${item.quantity} | Already received: ${item.received_quantity || 0} | Remaining: ${remaining}</small>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control form-control-sm receive-qty" data-ri-id="${item.id}" placeholder="Qty" min="0" max="${remaining}" value="${remaining}" style="color: var(--text-main); background: var(--bg-card-subtle);">
                        </div>
                    </div>
                `;
            });

            Swal.fire({
                title: 'Receive Goods',
                html: `<div style="text-align:left; color: var(--bg-body) !important;"><p style="color: var(--bg-body) !important;">Enter the quantities received for each item.</p>${itemsHtml}</div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirm Receipt',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const qtyInputs = document.querySelectorAll('.receive-qty');
                    const receivedItems = [];
                    let hasError = false;
                    qtyInputs.forEach(input => {
                        const qty = parseInt(input.value);
                        const riId = parseInt(input.dataset.riId);
                        const max = parseInt(input.max);
                        if (isNaN(qty) || qty < 0 || qty > max) {
                            hasError = true;
                            Swal.showValidationMessage('Invalid quantity for item.');
                            return;
                        }
                        if (qty > 0) receivedItems.push({ requisition_item_id: riId, quantity_received: qty });
                    });
                    if (hasError) return false;
                    if (receivedItems.length === 0) {
                        Swal.showValidationMessage('Please enter at least one item to receive.');
                        return false;
                    }
                    return { received_items: receivedItems };
                }
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch('?page=api_receive_goods', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: parseInt(id), received_items: result.value.received_items })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Goods Received!', text: 'Stock has been updated.', timer: 2000, showConfirmButton: false });
                        bootstrap.Modal.getInstance(document.getElementById('requisitionDetailModal'))?.hide();
                        loadTab(tabKey || 'mine', tabState[tabKey || 'mine'].page);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to receive goods.' });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' }));
            });
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' }));
}

// ============================================
// CREATE REQUISITION TAB (cart-based)
// ============================================

function setupCreateTab() {
    fixOrderDateToLocalToday();

    document.getElementById('productSearchInput')?.addEventListener('input', function () {
        renderProducts(this.value.trim());
    });
    document.getElementById('refreshProductsBtn')?.addEventListener('click', () => loadProductsForRequisition());
    document.getElementById('clearCartBtn')?.addEventListener('click', clearCart);
    document.getElementById('sendRequisitionBtn')?.addEventListener('click', openConfirmModal);
    document.getElementById('createSupplierSelect')?.addEventListener('change', function () {
        selectedSupplierId = parseInt(this.value) || null;
        cart = [];
        updateCartUI();
        loadProductsForRequisition();
    });

    const expectedDeliveryInput = document.getElementById('expectedDelivery');
    expectedDeliveryInput?.addEventListener('change', function () {
        const error = validateExpectedDeliveryClientSide(this.value);
        this.classList.toggle('is-invalid', !!error);
        let feedback = this.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            this.parentElement.appendChild(feedback);
        }
        feedback.textContent = error || '';
    });
}

// The order date is prefilled server-side with PHP's date('Y-m-d'), which can be off by a
// day from the visitor's actual local date whenever the server's configured timezone
// (this project's php.ini has date.timezone = Europe/Berlin) differs from the visitor's
// timezone. Since the field is read-only and meant to always show "today", correct it
// here using the browser's own local date. Also re-bases the Expected Delivery min/max
// bounds off the same local date so they can't disagree with Order Date.
function fixOrderDateToLocalToday() {
    const orderDateInput = document.getElementById('orderDate');
    const expectedDeliveryInput = document.getElementById('expectedDelivery');
    if (!orderDateInput) return;

    const now = new Date();
    const localToday = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    orderDateInput.value = localToday;

    if (expectedDeliveryInput) {
        expectedDeliveryInput.min = localToday;
        const maxDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        maxDate.setFullYear(maxDate.getFullYear() + 1);
        expectedDeliveryInput.max = maxDate.toISOString().split('T')[0];
    }
}

function setupModals() {
    document.getElementById('confirmSendBtn')?.addEventListener('click', sendRequisition);
}

function loadProductsForRequisition() {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading products...</p></div>`;

    const params = new URLSearchParams();
    if (selectedSupplierId) params.append('supplier_id', selectedSupplierId);

    fetch(`?page=api_get_products_for_requisition&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                products = data.data.products || [];
                suppliers = data.data.suppliers || [];
                selectedSupplierId = data.data.supplier?.id || null;
                populateSupplierSelect();
                renderProducts();
            } else {
                grid.innerHTML = smErrorState(data.message || 'Failed to load products');
            }
        })
        .catch(() => { grid.innerHTML = smErrorState(); });
}

function populateSupplierSelect() {
    const select = document.getElementById('createSupplierSelect');
    if (!select) return;
    if (!suppliers.length) {
        select.innerHTML = '<option value="">No suppliers available</option>';
    } else {
        select.innerHTML = suppliers.map(s =>
            `<option value="${s.id}" ${parseInt(s.id) === selectedSupplierId ? 'selected' : ''}>${escapeHtmlSM(s.company_name)}</option>`
        ).join('');
    }
    // Options were just replaced after the searchable-select widget already
    // initialized (or hasn't yet, on first load) — make it pick up the real list.
    window.refreshSearchableSelect?.(select);
}

function renderProducts(search = '') {
    const grid = document.getElementById('productGrid');
    const info = document.getElementById('productInfo');

    let filtered = products;
    if (search) {
        const q = search.toLowerCase();
        filtered = products.filter(p => p.name.toLowerCase().includes(q) || (p.barcode && p.barcode.toLowerCase().includes(q)));
    }

    if (!filtered || filtered.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1;">${smEmptyState('No products found', 'bi-box-seam')}</div>`;
        info.textContent = '0 products';
        return;
    }

    info.textContent = `${filtered.length} products`;

    grid.innerHTML = filtered.map(product => {
        const inCart = cart.find(c => c.store_product_id === product.store_product_id);
        const qtyInCart = inCart ? inCart.quantity : 0;
        const stockClass = product.stock_quantity <= product.reorder_level ? 'text-danger' : 'text-muted';
        const hasSupplierProduct = product.supplier_product_id !== null;
        return `
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card modern-card p-0 ${hasSupplierProduct ? 'clickable-card' : 'opacity-50'}"
                     style="cursor:${hasSupplierProduct ? 'pointer' : 'not-allowed'};border:2px solid transparent;overflow:hidden;"
                     data-product-id="${product.store_product_id}"
                     onclick="${hasSupplierProduct ? `addToCartFromCard(${product.store_product_id})` : ''}">
                    <div class="product-image-container" style="width:100%;height:110px;overflow:hidden;background:var(--bg-card-subtle);">
                        ${product.image_path
                            ? `<img src="/ShelfSense/public/${product.image_path}" alt="${escapeHtmlSM(product.name)}" style="width:100%;height:100%;object-fit:cover;">`
                            : `<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted);font-size:2rem;"><i class="bi bi-box"></i></div>`
                        }
                    </div>
                    <div class="p-2">
                        <div class="product-name" title="${escapeHtmlSM(product.name)}" style="font-weight:600;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtmlSM(product.name)}</div>
                        <div class="product-price" style="font-weight:700;color:var(--brand-yellow-hover);">${smCurrency(product.supplier_price)}</div>
                        <div class="product-stock" style="font-size:0.7rem;color:var(--text-muted);">Stock: ${product.stock_quantity} <span class="${stockClass}">(Reorder: ${product.reorder_level})</span></div>
                        ${!hasSupplierProduct ? '<div class="text-danger small">⚠️ Not offered by this supplier</div>' : ''}
                        <div class="mt-1">
                            <div class="input-group input-group-sm">
                                <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation(); updateCartQty(${product.store_product_id}, -1)" ${!hasSupplierProduct ? 'disabled' : ''}>−</button>
                                <input type="number" class="form-control form-control-sm text-center qty-input"
                                       data-store-id="${product.store_product_id}"
                                       value="${qtyInCart}" min="0" max="999"
                                       style="max-width:50px;"
                                       ${!hasSupplierProduct ? 'disabled' : ''}
                                       onclick="event.stopPropagation();"
                                       onchange="setCartQty(${product.store_product_id}, this.value)">
                                <button class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation(); updateCartQty(${product.store_product_id}, 1)" ${!hasSupplierProduct ? 'disabled' : ''}>+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function addToCartFromCard(storeProductId) {
    const product = products.find(p => p.store_product_id === storeProductId);
    if (!product || !product.supplier_product_id) return;
    updateCartQty(storeProductId, 1);
}

function updateCartQty(storeProductId, delta) {
    const product = products.find(p => p.store_product_id === storeProductId);
    if (!product || !product.supplier_product_id) return;
    const existing = cart.find(c => c.store_product_id === storeProductId);
    let newQty = existing ? existing.quantity + delta : (delta > 0 ? 1 : 0);
    newQty = Math.max(0, Math.min(999, newQty));
    setCartQty(storeProductId, newQty);
}

function setCartQty(storeProductId, value) {
    const qty = parseInt(value) || 0;
    const product = products.find(p => p.store_product_id === storeProductId);
    if (!product || !product.supplier_product_id) return;

    if (qty <= 0) {
        cart = cart.filter(c => c.store_product_id !== storeProductId);
    } else {
        const existing = cart.find(c => c.store_product_id === storeProductId);
        if (existing) {
            existing.quantity = Math.min(qty, 999);
        } else {
            cart.push({
                store_product_id: product.store_product_id,
                supplier_product_id: product.supplier_product_id,
                name: product.name,
                unit_price: parseFloat(product.supplier_price || 0),
                quantity: Math.min(qty, 999)
            });
        }
    }
    updateCartUI();
    renderProducts(document.getElementById('productSearchInput')?.value || '');
}

function updateCartUI() {
    const container = document.getElementById('cartItems');
    const emptyMessage = document.getElementById('emptyCartMessage');
    const countBadge = document.getElementById('cartCount');
    const totalDisplay = document.getElementById('cartTotal');
    const sendBtn = document.getElementById('sendRequisitionBtn');

    if (cart.length === 0) {
        container.style.display = 'none';
        emptyMessage.style.display = 'block';
        countBadge.textContent = '0';
        totalDisplay.textContent = smCurrency(0);
        sendBtn.disabled = true;
        return;
    }

    container.style.display = 'block';
    emptyMessage.style.display = 'none';

    let total = 0, itemCount = 0;
    container.innerHTML = cart.map((item, index) => {
        const subtotal = item.unit_price * item.quantity;
        total += subtotal;
        itemCount += item.quantity;
        return `
            <div class="cart-item">
                <div class="item-info">
                    <div class="item-name">${escapeHtmlSM(item.name)}</div>
                    <div class="item-price">${smCurrency(item.unit_price)} each</div>
                </div>
                <div class="qty-control">
                    <button onclick="updateCartQty(${item.store_product_id}, -1)">−</button>
                    <input type="number" class="form-control form-control-sm text-center"
                           value="${item.quantity}" min="0" max="999"
                           style="width:50px; padding:2px;"
                           onchange="setCartQty(${item.store_product_id}, this.value)">
                    <button onclick="updateCartQty(${item.store_product_id}, 1)">+</button>
                </div>
                <div class="fw-bold" style="min-width:60px;text-align:right;">${smCurrency(subtotal)}</div>
                <button class="btn btn-sm btn-link text-danger p-0" onclick="removeFromCart(${index})"><i class="bi bi-x-circle"></i></button>
            </div>
        `;
    }).join('');

    countBadge.textContent = itemCount;
    totalDisplay.textContent = smCurrency(total);
    sendBtn.disabled = false;
}

function removeFromCart(index) {
    if (index < 0 || index >= cart.length) return;
    cart.splice(index, 1);
    updateCartUI();
    renderProducts(document.getElementById('productSearchInput')?.value || '');
}

function clearCart() {
    if (cart.length === 0) {
        Swal.fire({ icon: 'info', title: 'Cart is Already Empty', timer: 1500, showConfirmButton: false });
        return;
    }
    Swal.fire({
        title: 'Clear Cart?',
        text: 'This will remove all items from the requisition cart.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Clear All',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            cart = [];
            updateCartUI();
            renderProducts(document.getElementById('productSearchInput')?.value || '');
        }
    });
}

// Client-side mirror of validateExpectedDeliveryDate() in app/helpers/functions.php.
// This is a UX convenience only — the server re-validates independently using its
// own configured timezone and never trusts this result.
function validateExpectedDeliveryClientSide(value) {
    if (!value) return null; // optional field in the existing workflow

    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) return 'Expected delivery date must be a valid date (YYYY-MM-DD).';

    const year = parseInt(match[1], 10);
    const month = parseInt(match[2], 10);
    const day = parseInt(match[3], 10);
    const parsed = new Date(year, month - 1, day);
    const isRealCalendarDate = parsed.getFullYear() === year && parsed.getMonth() === month - 1 && parsed.getDate() === day;
    if (!isRealCalendarDate) {
        return 'Expected delivery date is not a valid calendar date.';
    }

    parsed.setHours(0, 0, 0, 0);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const maxDate = new Date(today);
    maxDate.setFullYear(maxDate.getFullYear() + 1);

    if (parsed < today) return 'Expected delivery date cannot be earlier than today.';
    if (parsed > maxDate) return 'Expected delivery date cannot be more than one year from today.';
    return null;
}

function openConfirmModal() {
    if (cart.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Cart Empty', text: 'Add items to the requisition first.' });
        return;
    }
    if (!selectedSupplierId) {
        Swal.fire({ icon: 'warning', title: 'Supplier Required', text: 'Please select a supplier.' });
        return;
    }
    const orderDate = document.getElementById('orderDate').value;
    if (!orderDate) {
        Swal.fire({ icon: 'warning', title: 'Order Date Required', text: 'Please select an order date.' });
        return;
    }

    const expectedDeliveryError = validateExpectedDeliveryClientSide(document.getElementById('expectedDelivery').value);
    if (expectedDeliveryError) {
        Swal.fire({ icon: 'warning', title: 'Invalid Expected Delivery Date', text: expectedDeliveryError });
        return;
    }

    let total = 0;
    let itemsHtml = '<ul class="list-unstyled">';
    cart.forEach(item => {
        const subtotal = item.unit_price * item.quantity;
        total += subtotal;
        itemsHtml += `<li>${item.quantity}x ${escapeHtmlSM(item.name)} — ${smCurrency(subtotal)}</li>`;
    });
    itemsHtml += '</ul>';
    document.getElementById('confirmSendItems').innerHTML = itemsHtml;
    document.getElementById('confirmSendTotal').textContent = smCurrency(total);

    new bootstrap.Modal(document.getElementById('confirmSendModal')).show();
}

function sendRequisition() {
    const orderDate = document.getElementById('orderDate').value;
    const expectedDelivery = document.getElementById('expectedDelivery').value;
    const notes = document.getElementById('requisitionNotes').value.trim();

    if (!selectedSupplierId) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No supplier selected.' });
        return;
    }

    const data = {
        supplier_id: selectedSupplierId,
        order_date: orderDate,
        expected_delivery: expectedDelivery || null,
        notes: notes,
        items: cart.map(item => ({
            store_product_id: item.store_product_id,
            supplier_product_id: item.supplier_product_id,
            quantity: item.quantity,
            unit_price: item.unit_price
        }))
    };

    const btn = document.getElementById('confirmSendBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';

    fetch('?page=api_create_requisition', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Confirm & Create';

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Requisition Created!',
                text: `Requisition #${data.data.requisition_number} has been sent to the supplier.`,
                timer: 2000,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('confirmSendModal')).hide();
            clearCartSilently();
            document.getElementById('mine-tab').click();
            loadTab('mine', 1);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to create requisition.' });
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Confirm & Create';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}

function clearCartSilently() {
    cart = [];
    updateCartUI();
    renderProducts(document.getElementById('productSearchInput')?.value || '');
}
