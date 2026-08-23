// ============================================
// LEAVE MODULE - My Leaves (Employee View)
// Location: public/assets/js/leave.js
// ============================================

console.log('✅ leave.js loaded');

let currentPage = 1;
let leaveTypes = {
    'sick': 'Sick Leave',
    'vacation': 'Vacation Leave',
    'emergency': 'Emergency Leave',
    'other': 'Other Leave'
};

document.addEventListener('DOMContentLoaded', function() {
    loadLeaveBalances();
    loadLeaveRequests();
    setupEventListeners();
});

function setupEventListeners() {
    // Apply leave button
    document.getElementById('applyLeaveBtn')?.addEventListener('click', function() {
        openApplyLeaveModal();
    });

    // Apply leave form
    document.getElementById('applyLeaveForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitLeaveRequest();
    });

    // Leave type change - update balance hint
    document.getElementById('leaveType')?.addEventListener('change', function() {
        updateBalanceHint(this.value);
    });

    // Date change - calculate duration
    document.getElementById('leaveStartDate')?.addEventListener('change', calculateDuration);
    document.getElementById('leaveEndDate')?.addEventListener('change', calculateDuration);
}

function loadLeaveBalances() {
    fetch('?page=api_get_leave_balances')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const b = data.data.balances;
                // Entitled
                document.getElementById('balanceSick').textContent = b.entitled.sick;
                document.getElementById('balanceVacation').textContent = b.entitled.vacation;
                document.getElementById('balanceEmergency').textContent = b.entitled.emergency;
                document.getElementById('balanceOther').textContent = b.entitled.other;
                // Used
                document.getElementById('usedSick').textContent = b.used.sick;
                document.getElementById('usedVacation').textContent = b.used.vacation;
                document.getElementById('usedEmergency').textContent = b.used.emergency;
                document.getElementById('usedOther').textContent = b.used.other;
                // Remaining
                updateRemainingDisplay('Sick', b.remaining.sick);
                updateRemainingDisplay('Vacation', b.remaining.vacation);
                updateRemainingDisplay('Emergency', b.remaining.emergency);
                updateRemainingDisplay('Other', b.remaining.other);
            }
        })
        .catch(error => {
            console.error('Error loading leave balances:', error);
        });
}

function updateRemainingDisplay(type, remaining) {
    const el = document.getElementById('remaining' + type);
    if (el) {
        el.textContent = remaining + ' remaining';
        el.className = 'balance-remaining ' + (remaining > 0 ? 'positive' : 'low');
    }
}

function loadLeaveRequests(page = 1) {
    currentPage = page;
    
    const tbody = document.getElementById('leaveTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading leave requests...</p>
            </td>
        </tr>
    `;
    
    const params = new URLSearchParams({
        p: page,
        limit: 20
    });
    
    fetch(`?page=api_get_leave_requests&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderLeaveRequests(data.data.leaves);
                renderPagination(data.data.pagination);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger py-4">
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
                    <td colspan="6" class="text-center text-danger py-4">
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
                <td colspan="6" class="text-center text-muted py-4">
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
        
        html += `
            <tr class="leave-row" data-id="${leave.id}">
                <td><span class="leave-type-badge ${leave.leave_type}">${typeLabel}</span></td>
                <td>${leave.formatted_start}</td>
                <td>${leave.formatted_end}</td>
                <td><span class="badge bg-secondary">${leave.duration} day(s)</span></td>
                <td class="${statusClass}">${statusIcon} ${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-leave-btn" data-id="${leave.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${leave.status === 'pending' ? `
                        <button class="btn btn-sm btn-outline-danger cancel-leave-btn" data-id="${leave.id}">
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
    
    document.querySelectorAll('.cancel-leave-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            cancelLeaveRequest(this.dataset.id);
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
    
    if (!container) return;
    
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        if (info) info.textContent = `Showing ${pagination?.totalRecords || 0} requests`;
        return;
    }
    
    if (info) info.textContent = `Showing page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} total)`;
    
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

function openApplyLeaveModal() {
    document.getElementById('applyLeaveForm').reset();
    document.getElementById('leaveDuration').textContent = '0';
    document.getElementById('leaveStartDate').min = new Date().toISOString().split('T')[0];
    document.getElementById('leaveEndDate').min = new Date().toISOString().split('T')[0];
    document.getElementById('leaveBalanceHint').textContent = 'Select a leave type to see balance';
    new bootstrap.Modal(document.getElementById('applyLeaveModal')).show();
}

function updateBalanceHint(leaveType) {
    const hint = document.getElementById('leaveBalanceHint');
    if (!leaveType) {
        hint.textContent = 'Select a leave type to see balance';
        return;
    }
    
    // Get balance from the displayed values
    const balanceEl = document.getElementById('balance' + leaveType.charAt(0).toUpperCase() + leaveType.slice(1));
    const usedEl = document.getElementById('used' + leaveType.charAt(0).toUpperCase() + leaveType.slice(1));
    const remaining = parseInt(balanceEl.textContent) - parseInt(usedEl.textContent);
    
    const label = leaveTypes[leaveType] || leaveType;
    hint.textContent = `${label}: ${remaining} days remaining`;
}

function calculateDuration() {
    const start = document.getElementById('leaveStartDate').value;
    const end = document.getElementById('leaveEndDate').value;
    const durationEl = document.getElementById('leaveDuration');
    
    if (start && end) {
        const s = new Date(start);
        const e = new Date(end);
        if (e >= s) {
            const days = Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + 1;
            durationEl.textContent = days;
        } else {
            durationEl.textContent = '0';
        }
    } else {
        durationEl.textContent = '0';
    }
}

function submitLeaveRequest() {
    const form = document.getElementById('applyLeaveForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Validate dates
    if (!data.start_date || !data.end_date) {
        Swal.fire({ icon: 'warning', title: 'Missing Dates', text: 'Please select start and end dates.' });
        return;
    }
    if (data.end_date < data.start_date) {
        Swal.fire({ icon: 'warning', title: 'Invalid Dates', text: 'End date must be after start date.' });
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';
    
    fetch('?page=api_create_leave_request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Request';
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Leave Request Submitted!',
                text: `Your ${data.data.leave_type} leave for ${data.data.duration} day(s) has been submitted for approval.`,
                timer: 3000,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('applyLeaveModal')).hide();
            loadLeaveBalances();
            loadLeaveRequests(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Submission Failed', text: data.message || 'Please try again.' });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Request';
        console.error('Error submitting leave request:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

function viewLeaveDetail(leaveId) {
    const modal = document.getElementById('leaveDetailModal');
    const body = document.getElementById('leaveDetailBody');
    
    if (!modal || !body) return;
    
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;
    
    new bootstrap.Modal(modal).show();
    
    // Fetch the list and find the specific leave
    fetch('?page=api_get_leave_requests&p=1&limit=100')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const leave = data.data.leaves.find(l => l.id == leaveId);
                if (leave) {
                    renderLeaveDetail(leave);
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
                <p><strong>Leave Type:</strong> <span class="leave-type-badge ${leave.leave_type}">${typeLabel}</span></p>
                <p><strong>From:</strong> ${leave.formatted_start}</p>
                <p><strong>To:</strong> ${leave.formatted_end}</p>
                <p><strong>Duration:</strong> ${leave.duration} day(s)</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="${statusClass} fw-bold">${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</span></p>
                ${leave.approved_by_name ? `<p><strong>Approved By:</strong> ${leave.approved_by_name}</p>` : ''}
                ${leave.approved_at ? `<p><strong>Approved At:</strong> ${new Date(leave.approved_at).toLocaleString()}</p>` : ''}
                ${leave.notes ? `<p><strong>Notes:</strong> ${leave.notes}</p>` : ''}
            </div>
        </div>
        ${leave.reason ? `
            <div class="mt-3">
                <label class="fw-semibold">Reason</label>
                <p class="small">${leave.reason}</p>
            </div>
        ` : ''}
    `;
}

function cancelLeaveRequest(leaveId) {
    Swal.fire({
        title: 'Cancel Leave Request?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No, Keep'
    }).then(result => {
        if (result.isConfirmed) {
            // TODO: Implement cancellation API
            Swal.fire({
                icon: 'info',
                title: 'Cancel Request',
                text: 'Leave cancellation will be available soon.',
                confirmButtonText: 'OK'
            });
        }
    });
}