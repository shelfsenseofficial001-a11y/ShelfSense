// ============================================
// SUPPLIER — SHARED HELPERS
// Used by dashboard.js, requisitions.js, invoices.js, products.js
// ============================================

const SP_STATUS_LABELS = {
    draft: 'Draft',
    pending_supplier: 'Pending Supplier',
    sent_to_supplier: 'Sent to Supplier',
    supplier_processed: 'Invoiced',
    awaiting_finance_staff: 'Awaiting Finance Staff',
    awaiting_finance: 'Awaiting Finance',
    finance_approved: 'Finance Approved',
    finance_rejected: 'Finance Rejected',
    paid: 'Paid — Ready to Ship',
    shipped: 'Shipped',
    completed: 'Completed',
    partial_received: 'Partially Received',
    pending: 'Pending',
    verified: 'Verified',
    rejected: 'Rejected',
};

function spStatusBadge(status) {
    const label = SP_STATUS_LABELS[status] || (status || 'Unknown').replace(/_/g, ' ');
    return `<span class="sp-status-badge status-${escapeHtmlSP(status)}">${escapeHtmlSP(label)}</span>`;
}

function escapeHtmlSP(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function spCurrency(amount) {
    return '₱' + (parseFloat(amount) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function spFormatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return escapeHtmlSP(dateStr);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function spEmptyState(message, icon = 'bi-inbox') {
    return `<div class="sp-empty-state"><i class="bi ${icon}"></i>${escapeHtmlSP(message)}</div>`;
}

function spErrorState(message) {
    return `<div class="sp-error-state"><i class="bi bi-exclamation-triangle"></i>${escapeHtmlSP(message || 'Something went wrong. Please try again.')}</div>`;
}

function spRenderPagination(container, infoEl, pagination, itemLabel, onPageChange) {
    if (!container) return;

    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        if (infoEl) infoEl.textContent = `${pagination?.totalRecords || 0} ${itemLabel}`;
        return;
    }

    if (infoEl) {
        infoEl.textContent = `Page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} ${itemLabel})`;
    }

    let html = '';
    html += pagination.currentPage > 1
        ? `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>`
        : `<li class="page-item disabled"><span class="page-link">«</span></li>`;

    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);

    if (start > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    for (let i = start; i <= end; i++) {
        html += i === pagination.currentPage
            ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
            : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    if (end < pagination.totalPages) {
        if (end < pagination.totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
    }

    html += pagination.currentPage < pagination.totalPages
        ? `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage + 1}">»</a></li>`
        : `<li class="page-item disabled"><span class="page-link">»</span></li>`;

    container.innerHTML = html;
    container.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            onPageChange(parseInt(this.dataset.page));
        });
    });
}

// Client-side mirror of validateExpectedDeliveryDate() in app/helpers/functions.php
// (the same rule used for Store Manager's expected delivery date: today through
// one year from today, inclusive). The server always re-validates independently.
function spValidateFutureDate(value) {
    if (!value) return null;

    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) return 'Date must be a valid date (YYYY-MM-DD).';

    const year = parseInt(match[1], 10);
    const month = parseInt(match[2], 10);
    const day = parseInt(match[3], 10);
    const parsed = new Date(year, month - 1, day);
    const isRealCalendarDate = parsed.getFullYear() === year && parsed.getMonth() === month - 1 && parsed.getDate() === day;
    if (!isRealCalendarDate) {
        return 'Date is not a valid calendar date.';
    }

    parsed.setHours(0, 0, 0, 0);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const maxDate = new Date(today);
    maxDate.setFullYear(maxDate.getFullYear() + 1);

    if (parsed < today) return 'Date cannot be earlier than today.';
    if (parsed > maxDate) return 'Date cannot be more than one year from today.';
    return null;
}

// Sets min=today and max=today+1 year on a date input, using the browser's local
// date (not the server's, since this is a UI affordance only — the server always
// re-validates with its own configured timezone).
function spSetFutureDateBounds(input) {
    if (!input) return;
    const now = new Date();
    const localToday = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    input.min = localToday;
    const maxDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    maxDate.setFullYear(maxDate.getFullYear() + 1);
    input.max = maxDate.toISOString().split('T')[0];
    return localToday;
}
