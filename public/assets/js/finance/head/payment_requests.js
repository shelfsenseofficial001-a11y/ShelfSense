// ============================================
// FINANCE HEAD - PAYMENT REQUESTS
// ============================================

console.log('✅ finance/head/payment_requests.js loaded');

let currentPage = 1;
let historyPage = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadPaymentRequests();
    setupEventListeners();

    // History tab listener
    document.getElementById('approval-history-tab')?.addEventListener('shown.bs.tab', function() {
        loadApprovalHistory();
    });
});

function setupEventListeners() {
    document.getElementById('applyFiltersBtn')?.addEventListener('click', function() {
        currentPage = 1;
        loadPaymentRequests();
    });
    document.getElementById('refreshBtn')?.addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterStatus').value = '';
        currentPage = 1;
        loadPaymentRequests();
    });
    document.getElementById('confirmApproveBtn')?.addEventListener('click', function() {
        processRequest('approve');
    });
    document.getElementById('confirmRejectBtn')?.addEventListener('click', function() {
        processRequest('reject');
    });
}

function loadPaymentRequests(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams({
        p: page,
        limit: 20
    });
    if (search) params.append('search', search);
    if (status) params.append('status', status);

    const tbody = document.getElementById('requestsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading payment requests...</p>
            </td>
        </tr>
    `;

    fetch(`?page=api_finance_get_payment_requests&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRequests(data.data.payment_requests);
                renderPagination(data.data.pagination);
                renderStats(data.data.payment_requests);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            ${data.message || 'Failed to load payment requests'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading payment requests:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

function renderRequests(requests) {
    const tbody = document.getElementById('requestsTableBody');
    if (!requests || requests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No payment requests found
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    requests.forEach(req => {
        const statusClass = req.status;
        const statusLabel = req.status.charAt(0).toUpperCase() + req.status.slice(1);
        const total = parseFloat(req.requisition_total || 0).toFixed(2);
        const isPending = req.status === 'pending';
        
        // ✅ Use req.budget_exceeded directly (same as table)
        const budgetStatus = req.budget_exceeded 
            ? `<span class="badge bg-danger">⚠️ Exceeded</span>` 
            : `<span class="badge bg-success">✅ OK</span>`;

        html += `
            <tr class="request-row" data-id="${req.id}">
                <td><strong>${req.requisition_number}</strong></td>
                <td>${escapeHtml(req.company_name)}</td>
                <td>${escapeHtml(req.requested_first)} ${escapeHtml(req.requested_last)}</td>
                <td class="fw-bold">₱${total}</td>
                <td>${budgetStatus}</td>
                <td><span class="badge status-badge ${statusClass}">${statusLabel}</span></td>
                <td class="text-center">
                    ${isPending ? `
                        <button class="btn btn-sm btn-success approve-btn" data-id="${req.id}">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-sm btn-danger reject-btn" data-id="${req.id}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-outline-primary view-request-btn" data-id="${req.id}">
                            <i class="bi bi-eye"></i>
                        </button>
                    `}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    document.querySelectorAll('.view-request-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            viewRequest(this.dataset.id);
        });
    });

    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            openActionModal(this.dataset.id, 'approve');
        });
    });

    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            openActionModal(this.dataset.id, 'reject');
        });
    });

    document.querySelectorAll('.request-row').forEach(row => {
        row.addEventListener('click', function() {
            const id = this.dataset.id;
            const isPending = this.querySelector('.approve-btn');
            if (isPending) {
                openActionModal(id, 'approve');
            } else {
                viewRequest(id);
            }
        });
    });
}

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const info = document.getElementById('tableInfo');

    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        info.textContent = `Showing ${pagination?.totalRecords || 0} requests`;
        return;
    }

    info.textContent = `Showing page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} total)`;

    let html = '';
    if (pagination.currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
    }

    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);

    if (start > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    for (let i = start; i <= end; i++) {
        if (i === pagination.currentPage) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }

    if (end < pagination.totalPages) {
        if (end < pagination.totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
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
            loadPaymentRequests(page);
        });
    });
}

function renderStats(requests) {
    const total = requests.length;
    const pending = requests.filter(r => r.status === 'pending').length;
    const approved = requests.filter(r => r.status === 'approved').length;
    const rejected = requests.filter(r => r.status === 'rejected').length;

    document.getElementById('statPending').textContent = pending;
    document.getElementById('statApproved').textContent = approved;
    document.getElementById('statRejected').textContent = rejected;
}

function viewRequest(id) {
    const modal = document.getElementById('actionModal');
    const title = document.getElementById('actionModalTitle');
    const body = document.getElementById('actionModalBody');
    const reasonField = document.getElementById('reasonField');
    const approveBtn = document.getElementById('confirmApproveBtn');
    const rejectBtn = document.getElementById('confirmRejectBtn');

    title.textContent = 'Payment Request Details';
    reasonField.style.display = 'none';
    approveBtn.style.display = 'none';
    rejectBtn.style.display = 'none';

    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;

    new bootstrap.Modal(modal).show();

    fetch(`?page=api_finance_get_requisition_detail&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRequestDetail(data.data.requisition, 'view');
            } else {
                body.innerHTML = `
                    <div class="text-center text-danger py-4">
                        ${data.message || 'Failed to load details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            body.innerHTML = `
                <div class="text-center text-danger py-4">
                    An error occurred. Please try again.
                </div>
            `;
        });
}

function openActionModal(id, action) {
    const modal = document.getElementById('actionModal');
    const title = document.getElementById('actionModalTitle');
    const body = document.getElementById('actionModalBody');
    const reasonField = document.getElementById('reasonField');
    const approveBtn = document.getElementById('confirmApproveBtn');
    const rejectBtn = document.getElementById('confirmRejectBtn');

    const isApprove = action === 'approve';
    title.textContent = isApprove ? 'Approve Payment Request' : 'Reject Payment Request';
    reasonField.style.display = isApprove ? 'none' : 'block';
    approveBtn.style.display = isApprove ? 'inline-block' : 'none';
    rejectBtn.style.display = isApprove ? 'none' : 'inline-block';

    approveBtn.dataset.id = id;
    rejectBtn.dataset.id = id;

    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;

    new bootstrap.Modal(modal).show();

    fetch(`?page=api_finance_get_requisition_detail&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRequestDetail(data.data.requisition, action);
            } else {
                body.innerHTML = `
                    <div class="text-center text-danger py-4">
                        ${data.message || 'Failed to load details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            body.innerHTML = `
                <div class="text-center text-danger py-4">
                    An error occurred. Please try again.
                </div>
            `;
        });
}

function renderRequestDetail(req, mode) {
    const body = document.getElementById('actionModalBody');

    let itemsHtml = '';
    if (req.items && req.items.length > 0) {
        req.items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>${escapeHtml(item.store_product_name)}</td>
                    <td>${escapeHtml(item.supplier_product_name)}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td>₱${parseFloat(item.total).toFixed(2)}</td>
                </tr>
            `;
        });
    } else {
        itemsHtml = '<tr><td colspan="5" class="text-center text-muted">No items</td></tr>';
    }

    // ✅ Use req.budget_exceeded directly (same as table)
    const budgetStatus = req.budget_exceeded
        ? `<span class="badge bg-danger">⚠️ Budget Exceeded</span>`
        : `<span class="badge bg-success">✅ Within Budget</span>`;

    body.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Requisition #:</strong> ${req.requisition_number}</p>
                <p><strong>Supplier:</strong> ${escapeHtml(req.company_name)}</p>
                <p><strong>Order Date:</strong> ${req.order_date}</p>
                <p><strong>Expected Delivery:</strong> ${req.expected_delivery || '—'}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge status-badge ${req.status}">${req.status.replace('_', ' ').toUpperCase()}</span></p>
                <p><strong>Subtotal:</strong> ₱${parseFloat(req.subtotal || 0).toFixed(2)}</p>
                <p><strong>Total:</strong> ₱${parseFloat(req.total || 0).toFixed(2)}</p>
                <p><strong>Budget Status:</strong> ${budgetStatus}</p>
                ${req.invoice ? `
                    <hr>
                    <p><strong>Invoice #:</strong> ${req.invoice.invoice_number}</p>
                    <p><strong>Invoice Total:</strong> ₱${parseFloat(req.invoice.total).toFixed(2)}</p>
                ` : ''}
                ${req.payment_request && req.payment_request.budget_exceeded_reason ? `
                    <hr>
                    <p><strong>Budget Exceeded Reason:</strong></p>
                    <p class="small text-danger">${req.payment_request.budget_exceeded_reason}</p>
                ` : ''}
            </div>
        </div>
        ${req.notes ? `<p><strong>Notes:</strong> ${req.notes}</p>` : ''}
        <hr>
        <h6 class="fw-bold">Items</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Store Product</th>
                        <th>Supplier Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
        </div>
    `;
}

function processRequest(action) {
    const id = action === 'approve' 
        ? document.getElementById('confirmApproveBtn').dataset.id
        : document.getElementById('confirmRejectBtn').dataset.id;

    const reason = document.getElementById('actionReason').value.trim();

    if (action === 'reject' && !reason) {
        Swal.fire({
            icon: 'warning',
            title: 'Reason Required',
            text: 'Please provide a reason for rejecting this payment request.'
        });
        return;
    }

    const data = {
        payment_request_id: parseInt(id),
        action: action,
        reason: reason
    };

    const btn = action === 'approve' 
        ? document.getElementById('confirmApproveBtn')
        : document.getElementById('confirmRejectBtn');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    fetch('?page=api_finance_approve_payment_request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = action === 'approve' ? 'Approve' : 'Reject';

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: action === 'approve' ? 'Payment Approved!' : 'Payment Rejected!',
                text: data.message || 'Action completed successfully.',
                timer: 2000,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
            loadPaymentRequests(currentPage);
            // Reload history if tab is active
            if (document.getElementById('approvalHistory')?.classList.contains('active')) {
                loadApprovalHistory();
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: data.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = action === 'approve' ? 'Approve' : 'Reject';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
}

// ============================================
// LOAD APPROVAL HISTORY
// ============================================

function loadApprovalHistory(page = 1) {
    historyPage = page;
    const tbody = document.getElementById('approvalHistoryBody');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading history...</p>
            </td>
        </tr>
    `;

    const params = new URLSearchParams({
        p: page,
        limit: 20
    });

    fetch(`?page=api_finance_get_payment_requests&${params}&status=approved,rejected`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderApprovalHistory(data.data.payment_requests);
                renderHistoryPagination(data.data.pagination);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-danger py-4">
                            ${data.message || 'Failed to load history'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading history:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger py-4">
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

function renderApprovalHistory(requests) {
    const tbody = document.getElementById('approvalHistoryBody');
    if (!requests || requests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No approval history found
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    requests.forEach(req => {
        const statusClass = req.status;
        const statusLabel = req.status.charAt(0).toUpperCase() + req.status.slice(1);
        const total = parseFloat(req.requisition_total || 0).toFixed(2);
        const approvedBy = req.approved_first 
            ? `${req.approved_first} ${req.approved_last}` 
            : '—';
        const reason = req.rejection_reason || '—';
        const date = req.updated_at ? new Date(req.updated_at).toLocaleString() : '—';

        html += `
            <tr>
                <td><strong>${req.requisition_number}</strong></td>
                <td>${escapeHtml(req.company_name)}</td>
                <td class="fw-bold">₱${total}</td>
                <td><span class="badge status-badge ${statusClass}">${statusLabel}</span></td>
                <td>${approvedBy}</td>
                <td>${reason}</td>
                <td>${date}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-request-btn" data-id="${req.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    document.querySelectorAll('#approvalHistoryBody .view-request-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            viewRequest(this.dataset.id);
        });
    });
}

function renderHistoryPagination(pagination) {
    const container = document.getElementById('historyPaginationContainer');
    const info = document.getElementById('historyTableInfo');

    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        info.textContent = `Showing ${pagination?.totalRecords || 0} records`;
        return;
    }

    info.textContent = `Showing page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} total)`;

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
            loadApprovalHistory(page);
        });
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}