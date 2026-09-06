// ============================================
// PROCUREMENT — SHARED HELPERS (store_manager, finance staff/head, supplier)
// ============================================

const PR_STATUS_LABELS = {
    draft: 'Draft',
    pending_budget_check: 'Pending Budget Check',
    budget_rejected: 'Budget Rejected',
    pending_finance_head: 'Pending Finance Head',
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
    const positive = ['approved', 'converted_to_po', 'confirmed', 'received', 'matched', 'closed', 'disbursed', 'supplier_accepted'];
    const negative = ['rejected', 'budget_rejected', 'cancelled', 'price_hold', 'quantity_hold'];
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
