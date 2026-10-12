// ============================================
// PROCUREMENT — SHARED HELPERS (store_manager, finance staff/head, supplier)
// ============================================

const PR_STATUS_LABELS = {
    draft: 'Draft',
    pending_budget_check: 'Pending Budget Check',
    budget_rejected: 'Budget Rejected',
    pending_finance_head: 'Pending Finance Head',
    revision_requested: 'Revision Requested',
    approved: 'Approved',
    rejected: 'Rejected',
    converted_to_po: 'Converted to PO',
    cancelled: 'Cancelled',
    pending_dispatch: 'Pending Dispatch',
    pending_confirmation: 'Pending Confirmation',
    supplier_accepted: 'Supplier Accepted',
    supplier_counter_proposed: 'Supplier Counter-Proposed',
    confirmed: 'Confirmed',
    partially_received: 'Partially Received',
    received: 'Received',
    discrepancy_logged: 'Discrepancy Logged',
    action_taken: 'Action Taken',
    resolved: 'Resolved',
    closed: 'Closed',
    pending: 'Pending',
    matched: 'Matched',
    price_hold: 'Price Hold',
    quantity_hold: 'Quantity Hold',
    paid: 'Paid',
    pending_approval: 'Pending Approval',
    disbursed: 'Disbursed',
};

function prStatusBadge(status) {
    const label = PR_STATUS_LABELS[status] || (status || 'Unknown').replace(/_/g, ' ');
    const positive = ['approved', 'converted_to_po', 'confirmed', 'received', 'matched', 'closed', 'disbursed', 'supplier_accepted', 'resolved'];
    const negative = ['rejected', 'budget_rejected', 'cancelled', 'price_hold', 'quantity_hold', 'discrepancy_logged'];
    let cls = 'bg-secondary';
    if (positive.includes(status)) cls = 'bg-success';
    else if (negative.includes(status)) cls = 'bg-danger';
    else cls = 'bg-warning text-dark';
    return `<span class="badge ${cls}">${prEscapeHtml(label)}</span>`;
}

function prEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function prCurrency(amount) {
    return '₱' + (parseFloat(amount) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function prFormatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return prEscapeHtml(dateStr);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function prEmptyRow(colspan, message) {
    return `<tr><td colspan="${colspan}" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> ${prEscapeHtml(message)}</td></tr>`;
}

function prErrorRow(colspan, message) {
    return `<tr><td colspan="${colspan}" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle"></i> ${prEscapeHtml(message || 'Something went wrong.')}</td></tr>`;
}

function prRenderPagination(container, infoEl, pagination, itemLabel, onPageChange) {
    if (!container) return;
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = '';
        if (infoEl) infoEl.textContent = `${pagination?.totalRecords || 0} ${itemLabel}`;
        return;
    }
    if (infoEl) infoEl.textContent = `Page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} ${itemLabel})`;

    let html = '';
    html += pagination.currentPage > 1 ? `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>` : `<li class="page-item disabled"><span class="page-link">«</span></li>`;
    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);
    if (start > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    for (let i = start; i <= end; i++) {
        html += i === pagination.currentPage ? `<li class="page-item active"><span class="page-link">${i}</span></li>` : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    if (end < pagination.totalPages) {
        if (end < pagination.totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
    }
    html += pagination.currentPage < pagination.totalPages ? `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage + 1}">»</a></li>` : `<li class="page-item disabled"><span class="page-link">»</span></li>`;
    container.innerHTML = html;
    container.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            onPageChange(parseInt(this.dataset.page));
        });
    });
}

async function prFetchJson(url, options = {}) {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!data.success) {
        throw new Error(data.message || 'Request failed');
    }
    return data.data;
}

/**
 * Disables a button for the duration of an in-flight action request, so a
 * second click (or a fast double-click) before the response/list-refresh
 * lands can't fire the same request twice. The backend guard would reject
 * the duplicate anyway (e.g. "not pending dispatch"), but that shows up to
 * the user as a confusing error rather than being prevented outright.
 * Returns an unlock function -- always call it in a `finally` block so the
 * button recovers even if the request throws.
 */
function prLockButton(btn) {
    if (!btn) return () => {};
    if (btn.disabled) return () => {};
    btn.disabled = true;
    return () => { btn.disabled = false; };
}

/**
 * Sets (or clears) the small count badge on a nav-tab button, so a screen
 * with multiple tabs (Pending Dispatch / Request Payment / Reconciliation
 * Holds, etc.) shows at a glance which ones actually need attention.
 */
function prSetTabBadge(tabButtonId, count) {
    const btn = document.getElementById(tabButtonId);
    if (!btn) return;
    let badge = btn.querySelector('.pr-tab-badge');
    if (!count) {
        if (badge) badge.remove();
        return;
    }
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'badge rounded-pill bg-danger ms-1 pr-tab-badge';
        btn.appendChild(badge);
    }
    badge.textContent = count > 99 ? '99+' : String(count);
}

/** Full timestamp for a history timeline entry, e.g. "Sep 5, 2026, 2:14 PM". */
function prFormatDateTime(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return prEscapeHtml(dateStr);
    return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

/** Renders a PO's `events` array (from the shared get_purchase_order endpoint) as a vertical timeline. */
function prRenderTimeline(events) {
    if (!events || events.length === 0) {
        return '<p class="text-muted small mb-0">No history yet.</p>';
    }
    return `
        <ul class="list-unstyled pr-timeline mb-0">
            ${events.map(e => `
                <li class="mb-2 pb-2 border-bottom">
                    <div class="d-flex justify-content-between">
                        <span>${prEscapeHtml(e.description)}</span>
                        <small class="text-muted ms-2 text-nowrap">${prFormatDateTime(e.created_at)}</small>
                    </div>
                    ${e.actor_name ? `<small class="text-muted">by ${prEscapeHtml(e.actor_name)}</small>` : ''}
                </li>
            `).join('')}
        </ul>
    `;
}

/**
 * The 3-Way Match view (PO <-> Goods Receipt <-> Invoice), shared across
 * every page that references an invoice (Finance Staff, Finance Head,
 * Supplier's own invoices) -- a read-only breakdown that's just as visible
 * when everything reconciled cleanly as when it didn't, instead of only
 * surfacing when there's a hold to fix. Builds its own modal on first use
 * so no page needs its own copy of the markup.
 */
function prOpenMatchDetail(invoiceId) {
    let modalEl = document.getElementById('prMatchModal');
    if (!modalEl) {
        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="prMatchModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-arrow-left-right"></i> 3-Way Match</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="prMatchModalBody">Loading...</div>
                    </div>
                </div>
            </div>
        `);
        modalEl = document.getElementById('prMatchModal');
    }
    const body = document.getElementById('prMatchModalBody');
    body.innerHTML = 'Loading...';
    new bootstrap.Modal(modalEl).show();

    prFetchJson(`?page=api_get_invoice&id=${invoiceId}`).then(inv => {
        const isReconciled = inv.match_status === 'reconciled';
        const rowsHtml = inv.items.map(i => {
            const ok = i.variance_flag === 'ok';
            return `
                <tr class="${ok ? '' : 'table-warning'}">
                    <td>${prEscapeHtml(i.store_product_name)}</td>
                    <td>${i.po_quantity} @ ${prCurrency(i.po_unit_price)}</td>
                    <td>${i.good_received_quantity}</td>
                    <td>${i.billed_quantity} @ ${prCurrency(i.billed_unit_price)}</td>
                    <td class="text-center">${ok
                        ? '<i class="bi bi-check-circle-fill text-success" title="Matches"></i>'
                        : `<span class="badge bg-danger">${prEscapeHtml((i.variance_flag || '').replace(/_/g, ' '))}</span>`}
                    </td>
                </tr>
            `;
        }).join('');

        body.innerHTML = `
            <div class="alert ${isReconciled ? 'alert-success' : 'alert-warning'} d-flex align-items-center gap-2">
                <i class="bi ${isReconciled ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} fs-4"></i>
                <div>
                    <div class="fw-bold">${isReconciled ? 'Matches cleanly' : 'Variance found'}</div>
                    <div class="small mb-0">${isReconciled
                        ? 'Every line reconciles against the Purchase Order and Goods Receipt. Ready for payment.'
                        : 'One or more lines don’t match the Purchase Order and/or Goods Receipt -- see below.'}
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-2">Invoice <strong>${prEscapeHtml(inv.invoice_number)}</strong> for PO <strong>${prEscapeHtml(inv.po_number)}</strong></p>
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Product</th><th>PO Ordered @ Price</th><th>GR Received (Good)</th><th>Invoice Billed @ Price</th><th class="text-center">Match</th></tr></thead>
                <tbody>${rowsHtml}</tbody>
            </table>
        `;
    }).catch(e => {
        body.innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    });
}
