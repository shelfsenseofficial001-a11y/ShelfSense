// ============================================
// FINANCE STAFF - MY PAYMENT REQUESTS (4-tab)
// ============================================

console.log('✅ finance/staff/payment_requests.js loaded');

let currentStatus = 'pending';
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function () {
    setupTabs();
    loadRequests('pending', 1);

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'search', type: 'search', elementId: 'searchInput' },
            { key: 'dateFrom', type: 'date', elementId: 'dateFrom', labelPrefix: 'From' },
            { key: 'dateTo', type: 'date', elementId: 'dateTo', labelPrefix: 'To' },
        ]);
    }
});

function setupTabs() {
    document.querySelectorAll('#prTabs button[data-status]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#prTabs button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.dataset.status;
            loadRequests(currentStatus, 1);
        });
    });

    document.getElementById('searchInput')?.addEventListener('input', debounceFnp(() => loadRequests(currentStatus, 1), 400));
    document.getElementById('dateFrom')?.addEventListener('change', () => loadRequests(currentStatus, 1));
    document.getElementById('dateTo')?.addEventListener('change', () => loadRequests(currentStatus, 1));
    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        document.getElementById('searchInput').value = '';
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        loadRequests(currentStatus, 1);
    });
}

function debounceFnp(fn, wait) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function loadRequests(status, page) {
    currentStatus = status;
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    const container = document.getElementById('fn-pr-container');
    container.innerHTML = `<div class="text-center py-4" style="grid-column:1/-1;"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading...</p></div>`;

    const params = new URLSearchParams({ p: page, limit: 9 });
    if (status) params.append('status', status);
    if (search) params.append('search', search);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    fetch(`?page=api_finance_staff_get_payment_requests&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderCards(container, data.data.payment_requests);
                fnRenderPagination(
                    document.getElementById('paginationContainer'),
                    document.getElementById('tableInfo'),
                    data.data.pagination,
                    'requests',
                    (p) => loadRequests(status, p)
                );
                updateTabCounts(data.data.tab_counts);
            } else {
                container.innerHTML = fnErrorState(data.message || 'Failed to load payment requests');
            }
        })
        .catch(() => { container.innerHTML = fnErrorState(); });
}

function updateTabCounts(counts) {
    if (!counts) return;
    document.getElementById('countPending').textContent = counts.pending ?? 0;
    document.getElementById('countApproved').textContent = counts.approved ?? 0;
    document.getElementById('countRejected').textContent = counts.rejected ?? 0;
    document.getElementById('countAll').textContent = counts.all ?? 0;
}

function renderCards(container, requests) {
    if (!requests || requests.length === 0) {
        container.innerHTML = fnEmptyState('No payment requests found.');
        return;
    }
    container.innerHTML = requests.map(buildCard).join('');
    container.querySelectorAll('.view-pr-btn').forEach(btn => {
        btn.addEventListener('click', () => viewRequestDetail(btn.dataset.reqId));
    });
}

function buildCard(pr) {
    let timeline = `<div class="fn-timeline-item"><div class="fn-timeline-title">Payment Request created</div><div class="fn-timeline-date">${fnFormatDate(pr.requested_at, true)}</div></div>`;
    if (pr.status === 'approved' && pr.approved_at) {
        timeline += `<div class="fn-timeline-item"><div class="fn-timeline-title">Approved by ${fnEscapeHtml(pr.approved_first || 'Finance Head')} ${fnEscapeHtml(pr.approved_last || '')}</div><div class="fn-timeline-date">${fnFormatDate(pr.approved_at, true)}</div></div>`;
        timeline += `<div class="fn-timeline-item"><div class="fn-timeline-title">Payment recorded automatically</div><div class="fn-timeline-date">&nbsp;</div></div>`;
    } else if (pr.status === 'rejected') {
        timeline += `<div class="fn-timeline-item"><div class="fn-timeline-title">Rejected${pr.rejection_reason ? ': ' + fnEscapeHtml(pr.rejection_reason) : ''}</div><div class="fn-timeline-date">&nbsp;</div></div>`;
    } else {
        timeline += `<div class="fn-timeline-item pending"><div class="fn-timeline-title text-muted">Waiting for Finance Head</div></div>`;
    }

    return `
        <div class="fn-req-card">
            <div class="fn-req-header">
                <div>
                    <div class="fn-req-number">${fnPRNumber(pr.id)} — ${fnEscapeHtml(pr.requisition_number)}</div>
                    <div class="fn-req-sub">Invoice: ${fnEscapeHtml(pr.invoice_number)}</div>
                </div>
                ${fnPaymentStatusBadge(pr.status)}
            </div>
            <div class="d-flex justify-content-between small mb-2">
                <span>Amount: <strong>${fnCurrency(pr.requisition_total)}</strong></span>
                ${pr.budget_exceeded == 1 ? '<span class="fn-status-badge status-exceeded">⚠️ Was Exceeded</span>' : '<span class="fn-status-badge status-within_budget">✅ Within</span>'}
            </div>
            <div class="fn-timeline">${timeline}</div>
            <div class="fn-req-actions">
                <button class="btn btn-sm btn-outline-primary view-pr-btn" data-req-id="${pr.requisition_id}"><i class="bi bi-eye"></i> View Details</button>
            </div>
        </div>
    `;
}

function viewRequestDetail(requisitionId) {
    const modal = document.getElementById('requestDetailModal');
    const body = document.getElementById('requestDetailBody');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    new bootstrap.Modal(modal).show();

    fetch(`?page=api_finance_get_requisition_detail&id=${requisitionId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderDetail(data.data.requisition);
            } else {
                body.innerHTML = fnErrorState(data.message || 'Failed to load details');
            }
        })
        .catch(() => { body.innerHTML = fnErrorState(); });
}

function renderDetail(req) {
    const body = document.getElementById('requestDetailBody');
    const pr = req.payment_request;

    let itemsHtml = '<tr><td colspan="5" class="text-center text-muted">No items</td></tr>';
    if (req.items && req.items.length > 0) {
        itemsHtml = req.items.map(item => `
            <tr>
                <td>${fnEscapeHtml(item.store_product_name)}</td>
                <td>${item.quantity}</td>
                <td>${fnCurrency(item.unit_price)}</td>
                <td>${fnCurrency(item.total)}</td>
            </tr>
        `).join('');
    }

    body.innerHTML = `
        <div class="row mb-2">
            <div class="col-md-6">
                <p class="mb-1"><strong>Requisition #:</strong> ${fnEscapeHtml(req.requisition_number)}</p>
                <p class="mb-0"><strong>Status:</strong> ${fnReqStatusBadge(req.status)}</p>
            </div>
            <div class="col-md-6">
                ${pr ? `<p class="mb-1"><strong>Payment Request:</strong> ${fnPRNumber(pr.id)} — ${fnPaymentStatusBadge(pr.status)}</p>` : ''}
                <p class="mb-0"><strong>Total:</strong> ${fnCurrency(req.total)}</p>
            </div>
        </div>
        ${pr && pr.status === 'pending' ? `<div class="fn-budget-warning mb-2"><i class="bi bi-hourglass-split me-1"></i>Waiting for the Finance Head to approve. <strong>No payment has been made yet.</strong></div>` : ''}
        ${req.invoice ? `
            <div class="fn-doc-box mb-2">
                <div class="fn-doc-title">📄 Supplier Invoice (Primary Document)</div>
                <div>Invoice #: <strong>${fnEscapeHtml(req.invoice.invoice_number)}</strong></div>
                <div>Due Date: <strong>${fnFormatDate(req.invoice.due_date)}</strong></div>
                <div>Total: <strong>${fnCurrency(req.invoice.total)}</strong></div>
                ${req.invoice.notes ? `<div class="text-muted">Supplier notes: ${fnEscapeHtml(req.invoice.notes)}</div>` : ''}
            </div>
        ` : ''}
        <div class="fn-doc-box mb-2">
            <div class="fn-doc-title">📋 Original Requisition (Reference)</div>
            <div>Expected Delivery: <strong>${fnFormatDate(req.expected_delivery)}</strong></div>
            ${req.notes ? `<div class="text-muted">Notes: ${fnEscapeHtml(req.notes)}</div>` : ''}
        </div>
        ${pr && pr.notes ? `<p><strong>Finance Staff Notes:</strong> ${fnEscapeHtml(pr.notes)}</p>` : ''}
        ${pr && pr.budget_exceeded == 1 ? `<div class="fn-budget-warning mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>${fnEscapeHtml(pr.budget_exceeded_reason || 'This request exceeded the available budget when submitted.')}</div>` : ''}
        <hr>
        <h6 class="fw-bold">Items</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>${itemsHtml}</tbody>
            </table>
        </div>
        <h6 class="fw-bold">Timeline</h6>
        <div class="fn-timeline">
            <div class="fn-timeline-item"><div class="fn-timeline-title">Requisition created</div><div class="fn-timeline-date">${fnFormatDate(req.created_at, true)}</div></div>
            ${pr ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Payment request created</div><div class="fn-timeline-date">${fnFormatDate(pr.requested_at, true)}</div></div>` : ''}
            ${pr && pr.status === 'approved' && pr.approved_at ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Finance Head approved${pr.approved_first ? ' — ' + fnEscapeHtml(pr.approved_first) + ' ' + fnEscapeHtml(pr.approved_last) : ''}</div><div class="fn-timeline-date">${fnFormatDate(pr.approved_at, true)}</div></div>` : ''}
            ${pr && pr.status === 'rejected' ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Rejected${pr.rejection_reason ? ': ' + fnEscapeHtml(pr.rejection_reason) : ''}</div></div>` : ''}
            ${req.goods_receipt ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Goods received by store</div><div class="fn-timeline-date">${fnFormatDate(req.goods_receipt.created_at, true)}</div></div>` : ''}
        </div>
        <p class="text-muted small mb-0">Only events with a real recorded timestamp are shown.</p>
    `;
}
