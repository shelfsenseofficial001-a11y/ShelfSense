// ============================================
// SUPPLIER - INVOICES
// ============================================

console.log('✅ supplier/invoices.js loaded');

let currentPage = 1;
let currentStatus = '';

document.addEventListener('DOMContentLoaded', function () {
    loadInvoices();
    setupEventListeners();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'search', type: 'search', elementId: 'searchInput' },
        ]);
    }
});

function setupEventListeners() {
    document.querySelectorAll('#invoiceStatusTabs button[data-status]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#invoiceStatusTabs button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.dataset.status;
            currentPage = 1;
            loadInvoices();
        });
    });

    document.getElementById('searchInput')?.addEventListener('input', debounceSp(() => {
        currentPage = 1;
        loadInvoices();
    }, 400));

    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        document.getElementById('searchInput').value = '';
        currentPage = 1;
        loadInvoices();
    });
}

function debounceSp(fn, wait) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function loadInvoices(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();

    const params = new URLSearchParams({ p: page, limit: 12 });
    if (search) params.append('search', search);
    if (currentStatus) params.append('status', currentStatus);

    const container = document.getElementById('invoiceCardsContainer');
    container.innerHTML = `<div class="text-center py-4" style="grid-column:1/-1;"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading invoices...</p></div>`;

    fetch(`?page=api_supplier_get_invoices&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderInvoices(data.data.invoices);
                spRenderPagination(
                    document.getElementById('paginationContainer'),
                    document.getElementById('tableInfo'),
                    data.data.pagination,
                    'invoices',
                    (p) => loadInvoices(p)
                );
                updateInvoiceTabCounts(data.data.tab_counts);
            } else {
                container.innerHTML = spErrorState(data.message || 'Failed to load invoices');
            }
        })
        .catch(() => { container.innerHTML = spErrorState(); });
}

function updateInvoiceTabCounts(counts) {
    if (!counts) return;
    document.getElementById('countAll').textContent = counts.all ?? 0;
    document.getElementById('countPending').textContent = counts.pending ?? 0;
    document.getElementById('countVerified').textContent = counts.verified ?? 0;
    document.getElementById('countPaid').textContent = counts.paid ?? 0;
}

function renderInvoices(invoices) {
    const container = document.getElementById('invoiceCardsContainer');

    if (!invoices || invoices.length === 0) {
        container.innerHTML = spEmptyState('No invoices found.');
        return;
    }

    container.innerHTML = invoices.map(inv => `
        <div class="sp-req-card" data-id="${inv.id}">
            <div class="sp-req-header">
                <div>
                    <div class="sp-req-number">${escapeHtmlSP(inv.invoice_number)}</div>
                    <div class="sp-req-store">${escapeHtmlSP(inv.requisition_number)}</div>
                </div>
                ${spStatusBadge(inv.status)}
            </div>
            <div class="sp-req-meta">
                <div>Invoice Date: <strong>${spFormatDate(inv.invoice_date)}</strong></div>
                <div>Due: <strong>${spFormatDate(inv.due_date)}</strong></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="sp-req-total">${spCurrency(inv.total)}</div>
                <button class="btn btn-sm btn-outline-primary view-invoice-btn" data-id="${inv.id}"><i class="bi bi-eye"></i> View</button>
            </div>
        </div>
    `).join('');

    container.querySelectorAll('.view-invoice-btn').forEach(btn => {
        btn.addEventListener('click', () => viewInvoice(btn.dataset.id));
    });
}

function viewInvoice(id) {
    const modal = document.getElementById('invoiceDetailModal');
    const body = document.getElementById('invoiceDetailBody');

    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    bootstrap.Offcanvas.getOrCreateInstance(modal).show();

    fetch(`?page=api_supplier_get_invoice&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderInvoiceDetail(data.data.invoice);
            } else {
                body.innerHTML = spErrorState(data.message || 'Failed to load invoice details.');
            }
        })
        .catch(() => { body.innerHTML = spErrorState(); });
}

function renderInvoiceDetail(inv) {
    const body = document.getElementById('invoiceDetailBody');

    let itemsHtml = '<tr><td colspan="4" class="text-center text-muted">No items</td></tr>';
    if (inv.items && inv.items.length > 0) {
        itemsHtml = inv.items.map(item => `
            <tr>
                <td>${escapeHtmlSP(item.product_name)}</td>
                <td>${item.quantity}</td>
                <td>${spCurrency(item.unit_price)}</td>
                <td>${spCurrency(item.total)}</td>
            </tr>
        `).join('');
    }

    const events = [{ date: inv.created_at, title: 'Invoice Created', icon: 'bi-file-earmark-plus' }];
    if (inv.payment_request && inv.payment_request.requested_at) {
        events.push({ date: inv.payment_request.requested_at, title: 'Forwarded to Finance — Payment Requested', icon: 'bi-cash' });
    }
    if (inv.payment_request && inv.payment_request.status === 'approved' && inv.payment_request.approved_at) {
        events.push({ date: inv.payment_request.approved_at, title: 'Finance Approved', icon: 'bi-check-circle' });
    }
    if (inv.paid_at) {
        events.push({ date: inv.paid_at, title: 'Payment Confirmed', icon: 'bi-credit-card' });
    }
    events.sort((a, b) => new Date(a.date) - new Date(b.date));
    const timelineHtml = events.map(e => `
        <div class="sp-timeline-item">
            <div class="sp-timeline-title"><i class="bi ${e.icon} me-1"></i>${e.title}</div>
            <div class="sp-timeline-date">${spFormatDate(e.date)}</div>
        </div>
    `).join('');

    body.innerHTML = `
        <div class="row mb-3">
            <div class="col-md-6">
                <p class="mb-1"><strong>Supplier:</strong> ${escapeHtmlSP(inv.supplier_name)}</p>
                <p class="mb-1"><strong>Invoice Date:</strong> ${spFormatDate(inv.invoice_date)}</p>
                <p class="mb-0"><strong>Status:</strong> ${spStatusBadge(inv.status)}</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Store:</strong> ${escapeHtmlSP(inv.first_name)} ${escapeHtmlSP(inv.last_name)}</p>
                <p class="mb-1"><strong>Due Date:</strong> ${spFormatDate(inv.due_date)}</p>
                <p class="mb-0"><strong>Total:</strong> ${spCurrency(inv.total)}</p>
            </div>
        </div>
        ${inv.notes ? `<p><strong>Notes:</strong><br>${escapeHtmlSP(inv.notes)}</p>` : ''}
        <hr>
        <h6 class="fw-bold">Items</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>${itemsHtml}</tbody>
            </table>
        </div>
        <h6 class="fw-bold">Payment Timeline</h6>
        <div class="sp-timeline">${timelineHtml}</div>
        <p class="text-muted small mb-0">Only events with a real recorded timestamp are shown.</p>
    `;
}
