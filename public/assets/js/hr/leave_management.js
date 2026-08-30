// ============================================
// LEAVE MANAGEMENT - HR Head View
// Location: public/assets/js/hr/leave_management.js
// ============================================

console.log('✅ leave_management.js loaded');

let currentPage = 1;
let leaveTypes = {
    'sick': 'Sick Leave',
    'vacation': 'Vacation Leave',
    'emergency': 'Emergency Leave',
    'maternity': 'Maternity Leave',
    'other': 'Other Leave'
};
let currentLeaveId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadLeaveRequests();
    setupEventListeners();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'search', type: 'search', elementId: 'searchInput' },
            { key: 'status', type: 'select', elementId: 'filterStatus' },
            { key: 'type', type: 'select', elementId: 'filterLeaveType' },
        ], { applyButtonId: 'applyFiltersBtn' });
    }
});

function setupEventListeners() {
    document.getElementById('applyFiltersBtn')?.addEventListener('click', function() {
        loadLeaveRequests();
    });
    
    document.getElementById('refreshBtn')?.addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterLeaveType').value = '';
        loadLeaveRequests();
    });
    
    document.getElementById('approveLeaveBtn')?.addEventListener('click', function() {
        updateLeaveRequest('approve');
    });
    
    document.getElementById('rejectLeaveBtn')?.addEventListener('click', function() {
        updateLeaveRequest('reject');
    });
}

function loadLeaveRequests(page = 1) {
    currentPage = page;
    
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('filterStatus').value;
    const leaveType = document.getElementById('filterLeaveType').value;
    
    const params = new URLSearchParams({
        p: page,
        limit: 20
    });
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (leaveType) params.append('leave_type', leaveType);
    
    const tbody = document.getElementById('leaveTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading leave requests...</p>
            </td>
        </tr>
    `;
    
    fetch(`?page=api_get_leave_requests&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderLeaveRequests(data.data.leaves);
                renderPagination(data.data.pagination);
                renderStats(data.data.leaves);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            ${data.message || 'Failed to load leave requests'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading leave requests:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

function renderLeaveRequests(leaves) {
    const tbody = document.getElementById('leaveTableBody');
    
    if (!leaves || leaves.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No leave requests found
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    leaves.forEach(leave => {
        const statusClass = leave.status === 'pending' ? 'leave-status-pending' : 
                           leave.status === 'approved' ? 'leave-status-approved' : 'leave-status-rejected';
        const statusIcon = leave.status === 'pending' ? '⏳' : 
                          leave.status === 'approved' ? '✅' : '❌';
        const typeLabel = leaveTypes[leave.leave_type] || leave.leave_type;
        const isPending = leave.status === 'pending';
        
        html += `
            <tr class="leave-row" data-id="${leave.id}">
                <td>
                    <strong>${leave.employee_name}</strong>
                    <br><small class="text-muted">${leave.employee_number || 'N/A'}</small>
                </td>
                <td><span class="leave-type-badge ${leave.leave_type}">${typeLabel}</span></td>
                <td>${leave.formatted_start}</td>
                <td>${leave.formatted_end}</td>
                <td><span class="badge bg-secondary">${leave.duration} day(s)</span></td>
                <td class="${statusClass}">${statusIcon} ${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-leave-btn" data-id="${leave.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${isPending ? `
                        <button class="btn btn-sm btn-success approve-btn" data-id="${leave.id}">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-sm btn-danger reject-btn" data-id="${leave.id}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Attach events
    document.querySelectorAll('.view-leave-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            viewLeaveDetail(this.dataset.id);
        });
    });
    
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            currentLeaveId = this.dataset.id;
            openActionModal('approve');
        });
    });
    
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            currentLeaveId = this.dataset.id;
            openActionModal('reject');
        });
    });
    
    document.querySelectorAll('.leave-row').forEach(row => {
        row.addEventListener('click', function() {
            viewLeaveDetail(this.dataset.id);
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
            loadLeaveRequests(page);
        });
    });
}

function renderStats(leaves) {
    const total = leaves.length;
    const pending = leaves.filter(l => l.status === 'pending').length;
    const approved = leaves.filter(l => l.status === 'approved').length;
    const rejected = leaves.filter(l => l.status === 'rejected').length;
    
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statPending').textContent = pending;
    document.getElementById('statApproved').textContent = approved;
    document.getElementById('statRejected').textContent = rejected;
}

function viewLeaveDetail(leaveId) {
    const modal = document.getElementById('leaveDetailModal');
    const body = document.getElementById('leaveDetailBody');
    const approveBtn = document.getElementById('approveLeaveBtn');
    const rejectBtn = document.getElementById('rejectLeaveBtn');
    
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;
    approveBtn.style.display = 'none';
    rejectBtn.style.display = 'none';
    
    bootstrap.Offcanvas.getOrCreateInstance(modal).show();

    fetch('?page=api_get_leave_requests&p=1&limit=100')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const leave = data.data.leaves.find(l => l.id == leaveId);
                if (leave) {
                    renderLeaveDetail(leave);
                    if (leave.status === 'pending') {
                        approveBtn.style.display = 'inline-block';
                        rejectBtn.style.display = 'inline-block';
                        approveBtn.dataset.id = leaveId;
                        rejectBtn.dataset.id = leaveId;
                        currentLeaveId = leaveId;
                    }
                } else {
                    body.innerHTML = `<div class="text-center text-danger py-4">Leave request not found</div>`;
                }
            } else {
                body.innerHTML = `<div class="text-center text-danger py-4">${data.message || 'Failed to load details'}</div>`;
            }
        })
        .catch(error => {
            body.innerHTML = `<div class="text-center text-danger py-4">An error occurred. Please try again.</div>`;
        });
}

function renderLeaveDetail(leave) {
    const body = document.getElementById('leaveDetailBody');
    const statusClass = leave.status === 'pending' ? 'text-warning' : 
                       leave.status === 'approved' ? 'text-success' : 'text-danger';
    const typeLabel = leaveTypes[leave.leave_type] || leave.leave_type;
    
    body.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Employee:</strong> ${leave.employee_name}</p>
                <p><strong>Employee #:</strong> ${leave.employee_number || 'N/A'}</p>
                <p><strong>Leave Type:</strong> <span class="leave-type-badge ${leave.leave_type}">${typeLabel}</span></p>
            </div>
            <div class="col-md-6">
                <p><strong>From:</strong> ${leave.formatted_start}</p>
                <p><strong>To:</strong> ${leave.formatted_end}</p>
                <p><strong>Duration:</strong> ${leave.duration} day(s)</p>
                <p><strong>Status:</strong> <span class="${statusClass} fw-bold">${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</span></p>
            </div>
        </div>
        ${leave.reason ? `
            <div class="mt-3">
                <label class="fw-semibold">Reason</label>
                <p class="small">${leave.reason}</p>
            </div>
        ` : ''}
        ${leave.approved_by_name ? `
            <div class="mt-2">
                <label class="fw-semibold">Approved By:</label>
                <span class="small">${leave.approved_by_name}</span>
                ${leave.approved_at ? ` <span class="small text-muted">(${new Date(leave.approved_at).toLocaleString()})</span>` : ''}
            </div>
        ` : ''}
        ${leave.notes ? `
            <div class="mt-2">
                <label class="fw-semibold">Notes:</label>
                <p class="small">${leave.notes}</p>
            </div>
        ` : ''}
    `;
}

function openActionModal(action) {
    const title = action === 'approve' ? 'Approve Leave Request' : 'Reject Leave Request';
    const icon = action === 'approve' ? 'question' : 'warning';
    const confirmColor = action === 'approve' ? '#198754' : '#dc3545';
    const confirmText = action === 'approve' ? 'Yes, Approve' : 'Yes, Reject';
    
    Swal.fire({
        title: title,
        text: action === 'approve' ? 'This will approve the leave request.' : 'This will reject the leave request.',
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputPlaceholder: 'Optional notes...',
        inputAttributes: { rows: 3, maxlength: 255 }
    }).then(result => {
        if (result.isConfirmed) {
            updateLeaveRequest(action, result.value || null);
        }
    });
}

function updateLeaveRequest(action, notes = null) {
    const btn = action === 'approve' 
        ? document.getElementById('approveLeaveBtn') 
        : document.getElementById('rejectLeaveBtn');
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    
    const data = {
        leave_id: currentLeaveId,
        action: action
    };
    if (notes) data.notes = notes;
    
    fetch('?page=api_update_leave_request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = action === 'approve' 
                ? '<i class="bi bi-check-circle"></i> Approve' 
                : '<i class="bi bi-x-circle"></i> Reject';
        }
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Leave Request ' + (action === 'approve' ? 'Approved!' : 'Rejected!'),
                text: 'The leave request has been ' + (action === 'approve' ? 'approved' : 'rejected') + '.',
                timer: 2000,
                showConfirmButton: false
            });
            bootstrap.Offcanvas.getInstance(document.getElementById('leaveDetailModal')).hide();
            loadLeaveRequests(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Action Failed', text: data.message || 'Please try again.' });
        }
    })
    .catch(error => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = action === 'approve' 
                ? '<i class="bi bi-check-circle"></i> Approve' 
                : '<i class="bi bi-x-circle"></i> Reject';
        }
        console.error('Error updating leave request:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}