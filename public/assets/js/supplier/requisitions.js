// ============================================
// SUPPLIER - INCOMING REQUISITIONS (5-tab redesign)
// ============================================

console.log('✅ supplier/requisitions.js loaded');

const SP_TAB_GROUPS = {
    'pending': 'pending',
    'invoiced': 'invoiced',
    'paid': 'paid',
    'shipped': 'shipped',
    'all': '',
};

const spTabState = {
    'pending': { page: 1 },
    'invoiced': { page: 1 },
    'paid': { page: 1 },
    'shipped': { page: 1 },
    'all': { page: 1 },
};

document.addEventListener('DOMContentLoaded', function () {
    setupTabs();
    setupModals();
    loadSpTab('pending', 1);

    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    if (requestedTab && SP_TAB_GROUPS.hasOwnProperty(requestedTab)) {
        const btn = document.querySelector(`#requisitionTabs button[data-tab-key="${requestedTab}"]`);
        if (btn) new bootstrap.Tab(btn).show();
    }
    const viewId = urlParams.get('view');
    if (viewId) {
        viewRequisition(viewId);
    }
});

function setupTabs() {
    document.querySelectorAll('#requisitionTabs button[data-tab-key]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function () {
            loadSpTab(this.dataset.tabKey, spTabState[this.dataset.tabKey].page);
        });
    });

    document.querySelectorAll('[data-tab-panel]').forEach(panel => {
        const key = panel.dataset.tabPanel;
        panel.querySelectorAll('[data-filter]').forEach(el => {
            el.addEventListener('change', () => loadSpTab(key, 1));
        });
        const searchInput = panel.querySelector('[data-filter="search"]');
        if (searchInput) {
            let debounce;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(() => loadSpTab(key, 1), 400);
            });
        }
        panel.querySelector('[data-action="refresh"]')?.addEventListener('click', () => {
            panel.querySelectorAll('[data-filter]').forEach(el => el.value = '');
            loadSpTab(key, 1);
        });
    });
}

function loadSpTab(tabKey, page) {
    if (!SP_TAB_GROUPS.hasOwnProperty(tabKey)) return;
    spTabState[tabKey].page = page;

    const panel = document.querySelector(`[data-tab-panel="${tabKey}"]`);
    const cardsContainer = panel.querySelector('[data-cards-container]');
    cardsContainer.innerHTML = `<div class="text-center py-4" style="grid-column:1/-1;"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading requisitions...</p></div>`;

    const params = new URLSearchParams({ p: page, limit: 12 });
    const group = SP_TAB_GROUPS[tabKey];
    if (group) params.append('group', group);

    panel.querySelectorAll('[data-filter]').forEach(el => {
        if (el.value) params.append(el.dataset.filter, el.value);
    });

    fetch(`?page=api_supplier_get_requisitions&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderSpCards(cardsContainer, data.data.requisitions, tabKey);
                spRenderPagination(
                    panel.querySelector('[data-pagination]'),
                    panel.querySelector('[data-info]'),
                    data.data.pagination,
                    'requisitions',
                    (p) => loadSpTab(tabKey, p)
                );
                updateTabCounts(data.data.tab_counts);
            } else {
                cardsContainer.innerHTML = spErrorState(data.message || 'Failed to load requisitions');
            }
        })
        .catch(() => { cardsContainer.innerHTML = spErrorState(); });
}

function updateTabCounts(counts) {
    if (!counts) return;
    const map = { pending: 'pending', invoiced: 'invoiced', paid: 'paid', shipped: 'shipped', all: 'all' };
    Object.keys(map).forEach(key => {
        const btn = document.querySelector(`#requisitionTabs button[data-tab-key="${key}"]`);
        if (!btn) return;
        let badge = btn.querySelector('.badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'badge bg-secondary ms-1';
            btn.appendChild(badge);
        }
        badge.textContent = counts[map[key]] ?? 0;
    });
}

function renderSpCards(container, requisitions, tabKey) {
    if (!requisitions || requisitions.length === 0) {
        container.innerHTML = spEmptyState('No requisitions found for this view.');
        return;
    }

    container.innerHTML = requisitions.map(req => buildSpCard(req)).join('');

    container.querySelectorAll('.view-requisition-btn').forEach(btn => {
        btn.addEventListener('click', () => viewRequisition(btn.dataset.id));
    });
    container.querySelectorAll('.accept-btn').forEach(btn => {
        btn.addEventListener('click', (e) => { e.stopPropagation(); openInvoiceModal(btn.dataset.id, tabKey); });
    });
    container.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', (e) => { e.stopPropagation(); openRejectModal(btn.dataset.id, tabKey); });
    });
    container.querySelectorAll('.ship-btn').forEach(btn => {
        btn.addEventListener('click', (e) => { e.stopPropagation(); openShipModal(btn.dataset.id, tabKey); });
    });
}

function buildSpCard(req) {
    const total = spCurrency(req.total);
    const isPending = req.status === 'pending_supplier' || req.status === 'sent_to_supplier';
    const isPaid = req.status === 'paid';
    const alreadyRejected = (req.notes || '').indexOf('[SUPPLIER REJECTED]') !== -1;

    let actions = `<button class="btn btn-sm btn-outline-primary view-requisition-btn" data-id="${req.id}"><i class="bi bi-eye"></i> View Details</button>`;
    if (isPending && !alreadyRejected) {
        actions += `<button class="btn btn-sm btn-success accept-btn" data-id="${req.id}"><i class="bi bi-check-circle"></i> Accept</button>`;
        actions += `<button class="btn btn-sm btn-outline-danger reject-btn" data-id="${req.id}"><i class="bi bi-x-circle"></i> Reject</button>`;
    }
    if (isPaid) {
        actions += `<button class="btn btn-sm btn-primary ship-btn" data-id="${req.id}"><i class="bi bi-truck"></i> Ship Goods</button>`;
    }

    return `
        <div class="sp-req-card" data-id="${req.id}">
            <div class="sp-req-header">
                <div>
                    <div class="sp-req-number">${escapeHtmlSP(req.requisition_number)}</div>
                    <div class="sp-req-store">Store: ${escapeHtmlSP(req.first_name)} ${escapeHtmlSP(req.last_name)}</div>
                </div>
                ${alreadyRejected ? spStatusBadge('rejected') : spStatusBadge(req.status)}
            </div>
            <div class="sp-req-meta">
                <div>Ordered: <strong>${spFormatDate(req.order_date)}</strong></div>
                <div>Expected: <strong>${spFormatDate(req.expected_delivery)}</strong></div>
                <div>Items: <strong>${req.item_count ?? 0}</strong></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="sp-req-total">${total}</div>
            </div>
            <div class="sp-req-actions">${actions}</div>
        </div>
    `;
}

// ============================================
// REQUISITION DETAIL (with real-data timeline)
// ============================================

function viewRequisition(id) {
    const modal = document.getElementById('requisitionDetailModal');
    const body = document.getElementById('requisitionDetailBody');
    const footer = document.getElementById('requisitionDetailFooter');

    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    footer.innerHTML = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;

    new bootstrap.Modal(modal).show();

    fetch(`?page=api_supplier_get_requisition&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderRequisitionDetail(data.data.requisition);
            } else {
                body.innerHTML = spErrorState(data.message || 'Failed to load requisition details');
            }
        })
        .catch(() => { body.innerHTML = spErrorState(); });
}

function renderRequisitionDetail(req) {
    const body = document.getElementById('requisitionDetailBody');
    const footer = document.getElementById('requisitionDetailFooter');
    const alreadyRejected = (req.notes || '').indexOf('[SUPPLIER REJECTED]') !== -1;

    let itemsHtml = '';
    if (req.items && req.items.length > 0) {
        req.items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>${escapeHtmlSP(item.store_product_name)}</td>
                    <td>${escapeHtmlSP(item.supplier_product_name)}</td>
                    <td>${item.quantity}</td>
                    <td>${spCurrency(item.unit_price)}</td>
                    <td>${spCurrency(item.total)}</td>
                    <td>${item.received_quantity || 0}</td>
                </tr>
            `;
        });
    } else {
        itemsHtml = '<tr><td colspan="6" class="text-center text-muted">No items</td></tr>';
    }

    body.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Requisition #:</strong> ${escapeHtmlSP(req.requisition_number)}</p>
                <p><strong>Store:</strong> ${escapeHtmlSP(req.first_name)} ${escapeHtmlSP(req.last_name)}</p>
                <p><strong>Order Date:</strong> ${spFormatDate(req.order_date)}</p>
                <p><strong>Expected Delivery:</strong> ${spFormatDate(req.expected_delivery)}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> ${alreadyRejected ? spStatusBadge('rejected') : spStatusBadge(req.status)}</p>
                <p><strong>Subtotal:</strong> ${spCurrency(req.subtotal)}</p>
                <p><strong>Total:</strong> ${spCurrency(req.total)}</p>
            </div>
        </div>
        ${req.notes ? `<p><strong>Notes:</strong><br>${escapeHtmlSP(req.notes).replace(/\n/g, '<br>')}</p>` : ''}
        <hr>
        <h6 class="fw-bold">Items</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead><tr><th>Store Product</th><th>Your Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Received</th></tr></thead>
                <tbody>${itemsHtml}</tbody>
            </table>
        </div>
        <h6 class="fw-bold">Timeline</h6>
        <div class="sp-timeline">${buildSpTimeline(req)}</div>
        <p class="text-muted small mb-0">Only events with a real recorded timestamp are shown.</p>
    `;

    let actionsHtml = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;
    const isPending = req.status === 'pending_supplier' || req.status === 'sent_to_supplier';
    if (isPending && !alreadyRejected) {
        actionsHtml += `<button type="button" class="btn btn-outline-danger btn-sm" id="detailRejectBtn" data-id="${req.id}"><i class="bi bi-x-circle"></i> Reject</button>`;
        actionsHtml += `<button type="button" class="btn btn-success btn-sm" id="detailAcceptBtn" data-id="${req.id}"><i class="bi bi-check-circle"></i> Accept</button>`;
    }
    if (req.status === 'paid') {
        actionsHtml += `<button type="button" class="btn btn-primary btn-sm" id="detailShipBtn" data-id="${req.id}"><i class="bi bi-truck"></i> Ship Goods</button>`;
    }
    footer.innerHTML = actionsHtml;

    document.getElementById('detailAcceptBtn')?.addEventListener('click', function () { openInvoiceModal(this.dataset.id, currentActiveSpTab()); });
    document.getElementById('detailRejectBtn')?.addEventListener('click', function () { openRejectModal(this.dataset.id, currentActiveSpTab()); });
    document.getElementById('detailShipBtn')?.addEventListener('click', function () { openShipModal(this.dataset.id, currentActiveSpTab()); });
}

function buildSpTimeline(req) {
    const events = [{ date: req.created_at, title: 'Requisition Created', icon: 'bi-file-earmark-plus' }];

    if (req.invoice && req.invoice.created_at) {
        events.push({ date: req.invoice.created_at, title: `Invoice Created (${req.invoice.invoice_number})`, icon: 'bi-receipt' });
    }
    if (req.payment_request && req.payment_request.requested_at) {
        events.push({ date: req.payment_request.requested_at, title: 'Forwarded to Finance — Payment Requested', icon: 'bi-cash' });
    }
    if (req.payment_request && req.payment_request.status === 'approved' && req.payment_request.approved_at) {
        events.push({ date: req.payment_request.approved_at, title: 'Finance Approved', icon: 'bi-check-circle' });
    }
    if (req.invoice && req.invoice.paid_at) {
        events.push({ date: req.invoice.paid_at, title: 'Payment Confirmed', icon: 'bi-credit-card' });
    }
    if (req.goods_receipt && req.goods_receipt.created_at) {
        events.push({ date: req.goods_receipt.created_at, title: 'Goods Received by Store', icon: 'bi-box-seam' });
    }

    events.sort((a, b) => new Date(a.date) - new Date(b.date));
    return events.map(e => `
        <div class="sp-timeline-item">
            <div class="sp-timeline-title"><i class="bi ${e.icon} me-1"></i>${e.title}</div>
            <div class="sp-timeline-date">${spFormatDate(e.date)}</div>
        </div>
    `).join('');
}

function currentActiveSpTab() {
    const active = document.querySelector('#requisitionTabs button.active');
    return active ? active.dataset.tabKey : 'pending';
}

// ============================================
// REJECT FLOW
// ============================================

function openRejectModal(id, tabKey) {
    document.getElementById('rejectRequisitionId').value = id;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectReason').classList.remove('is-invalid');
    document.getElementById('confirmRejectBtn').dataset.tabKey = tabKey || 'pending';
    bootstrap.Modal.getInstance(document.getElementById('requisitionDetailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function setupModals() {
    document.getElementById('confirmRejectBtn')?.addEventListener('click', submitReject);
    document.getElementById('invoiceForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitInvoice();
    });
    document.getElementById('invoiceDueDate')?.addEventListener('change', function () {
        const error = spValidateFutureDate(this.value);
        this.classList.toggle('is-invalid', !!error);
        const feedback = this.parentElement.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = error || '';
    });
    document.getElementById('confirmShipBtn')?.addEventListener('click', submitShip);
}

function submitReject() {
    const id = document.getElementById('rejectRequisitionId').value;
    const reason = document.getElementById('rejectReason').value.trim();
    const reasonInput = document.getElementById('rejectReason');

    if (!reason) {
        reasonInput.classList.add('is-invalid');
        return;
    }
    reasonInput.classList.remove('is-invalid');

    const btn = document.getElementById('confirmRejectBtn');
    const tabKey = btn.dataset.tabKey || 'pending';
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Rejecting...';

    fetch('?page=api_supplier_process_requisition', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(id), reason: reason })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Confirm Reject';
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide();
            Swal.fire({ icon: 'success', title: 'Requisition Rejected', text: 'The Store Manager has been notified.', timer: 2000, showConfirmButton: false });
            loadSpTab(tabKey, spTabState[tabKey].page);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to reject requisition.' });
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Confirm Reject';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}

// ============================================
// ACCEPT -> CREATE INVOICE FLOW
// ============================================

function openInvoiceModal(id, tabKey) {
    bootstrap.Modal.getInstance(document.getElementById('requisitionDetailModal'))?.hide();

    const modal = document.getElementById('invoiceModal');
    const requisitionSummary = document.getElementById('invoiceRequisitionSummary');

    document.getElementById('invoiceRequisitionId').value = id;
    document.getElementById('invoiceRequisitionId').dataset.tabKey = tabKey || 'pending';
    // Invoice date is always "today" — server enforces this too, this is just display.
    const now = new Date();
    document.getElementById('invoiceDate').value = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    spSetFutureDateBounds(document.getElementById('invoiceDueDate'));
    document.getElementById('invoiceDueDate').value = '';
    document.getElementById('invoiceDueDate').classList.remove('is-invalid');
    document.getElementById('invoiceNotes').value = '';

    fetch(`?page=api_supplier_get_requisition&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const req = data.data.requisition;
                requisitionSummary.innerHTML = `
                    <p class="mb-1"><strong>Requisition #:</strong> ${escapeHtmlSP(req.requisition_number)}</p>
                    <p class="mb-1"><strong>Store:</strong> ${escapeHtmlSP(req.first_name)} ${escapeHtmlSP(req.last_name)}</p>
                    <p class="mb-0"><strong>Order Date:</strong> ${spFormatDate(req.order_date)}</p>
                `;

                let itemsHtml = '<p class="text-muted small">No items.</p>';
                if (req.items && req.items.length > 0) {
                    itemsHtml = `
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                            <tbody>
                                ${req.items.map(item => `
                                    <tr>
                                        <td>${escapeHtmlSP(item.store_product_name)}</td>
                                        <td>${item.quantity}</td>
                                        <td>${spCurrency(item.unit_price)}</td>
                                        <td>${spCurrency(item.total)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
                document.getElementById('invoiceItemsPreview').innerHTML = itemsHtml;
                document.getElementById('invoiceTotalDisplay').textContent = spCurrency(req.total);

                new bootstrap.Modal(modal).show();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load requisition details.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' }));
}

function submitInvoice() {
    const requisitionId = document.getElementById('invoiceRequisitionId').value;
    const tabKey = document.getElementById('invoiceRequisitionId').dataset.tabKey || 'pending';
    const dueDate = document.getElementById('invoiceDueDate').value;
    const notes = document.getElementById('invoiceNotes').value.trim();

    const dueDateError = spValidateFutureDate(dueDate) || (!dueDate ? 'Due date is required.' : null);
    if (dueDateError) {
        document.getElementById('invoiceDueDate').classList.add('is-invalid');
        document.getElementById('invoiceDueDate').parentElement.querySelector('.invalid-feedback').textContent = dueDateError;
        return;
    }

    const submitBtn = document.querySelector('#invoiceForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

    fetch('?page=api_supplier_create_invoice', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ requisition_id: parseInt(requisitionId), due_date: dueDate, notes: notes })
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send"></i> Send Invoice to Store Manager';

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('invoiceModal'))?.hide();
            Swal.fire({
                icon: 'success',
                title: 'Invoice Created!',
                html: `Invoice <strong>${escapeHtmlSP(data.data.invoice.invoice_number)}</strong> has been sent to the Store Manager.<br>Next step: wait for Finance approval to ship the goods.`,
                timer: 3000,
                showConfirmButton: false
            });
            loadSpTab(tabKey, spTabState[tabKey].page);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to create invoice.' });
        }
    })
    .catch(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send"></i> Send Invoice to Store Manager';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}

// ============================================
// SHIP GOODS FLOW
// ============================================

function openShipModal(id, tabKey) {
    bootstrap.Modal.getInstance(document.getElementById('requisitionDetailModal'))?.hide();
    document.getElementById('confirmShipBtn').dataset.id = id;
    document.getElementById('confirmShipBtn').dataset.tabKey = tabKey || 'paid';
    document.getElementById('shipTrackingNumber').value = '';
    document.getElementById('shipNotes').value = '';
    new bootstrap.Modal(document.getElementById('shipModal')).show();
}

function submitShip() {
    const btn = document.getElementById('confirmShipBtn');
    const id = btn.dataset.id;
    const tabKey = btn.dataset.tabKey || 'paid';
    const trackingNumber = document.getElementById('shipTrackingNumber').value.trim();
    const notes = document.getElementById('shipNotes').value.trim();

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    fetch('?page=api_supplier_ship_goods', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ requisition_id: parseInt(id), tracking_number: trackingNumber, notes: notes })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-truck"></i> Confirm Shipment';

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('shipModal'))?.hide();
            Swal.fire({ icon: 'success', title: 'Goods Shipped!', text: 'The Store Manager has been notified.', timer: 2000, showConfirmButton: false });
            loadSpTab(tabKey, spTabState[tabKey].page);
        } else {
            Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Please try again.' });
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-truck"></i> Confirm Shipment';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}
