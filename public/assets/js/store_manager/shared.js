// ============================================
// STORE MANAGER — SHARED HELPERS
// Used by dashboard.js, requisitions.js, inventory.js
// ============================================

// Centralized status -> readable label mapping. Uses the project's real
// database status values as keys; CSS colors live in store_manager.css
// under .sm-status-badge.status-<value> (see spec section 4).
const SM_STATUS_LABELS = {
    draft: 'Draft',
    pending_supplier: 'Pending Supplier',
    sent_to_supplier: 'Sent to Supplier',
    supplier_processed: 'Supplier Processed',
    awaiting_finance_staff: 'Awaiting Finance Staff',
    awaiting_finance: 'Awaiting Finance',
    finance_approved: 'Finance Approved',
    finance_rejected: 'Finance Rejected',
    paid: 'Paid',
    shipped: 'Shipped',
    completed: 'Completed',
    partial_received: 'Partially Received',
};

function smStatusBadge(status) {
    const label = SM_STATUS_LABELS[status] || (status || 'Unknown').replace(/_/g, ' ');
    return `<span class="sm-status-badge status-${escapeHtmlSM(status)}">${escapeHtmlSM(label)}</span>`;
}

function smStockBadge(stockQuantity, reorderLevel) {
    const stock = parseInt(stockQuantity) || 0;
    const reorder = parseInt(reorderLevel) || 0;
    if (stock === 0) {
        return '<span class="sm-stock-badge out"><i class="bi bi-x-circle"></i> Out of Stock</span>';
    }
    if (stock <= reorder) {
        return '<span class="sm-stock-badge low"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>';
    }
    return '<span class="sm-stock-badge in"><i class="bi bi-check-circle"></i> In Stock</span>';
}

function escapeHtmlSM(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function smCurrency(amount) {
    return '₱' + (parseFloat(amount) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function smFormatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return escapeHtmlSM(dateStr);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function smEmptyState(message, icon = 'bi-inbox') {
    return `
        <div class="sm-empty-state">
            <i class="bi ${icon}"></i>
            ${escapeHtmlSM(message)}
        </div>
    `;
}

function smErrorState(message) {
    return `
        <div class="sm-error-state">
            <i class="bi bi-exclamation-triangle"></i>
            ${escapeHtmlSM(message || 'Something went wrong. Please try again.')}
        </div>
    `;
}

// Renders a Bootstrap-style pagination <ul> into the given container element,
// wiring click handlers that call onPageChange(page).
function smRenderPagination(container, infoEl, pagination, itemLabel, onPageChange) {
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
