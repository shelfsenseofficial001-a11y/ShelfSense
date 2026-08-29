// ============================================
// HR PAYROLL - FULL AJAX
// ============================================

console.log('✅ payroll.js loaded');

let currentCycleId = null;

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatCurrency(amount) {
    if (!amount) return '₱0.00';
    return '₱' + parseFloat(amount).toFixed(2);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getStatusBadge(status) {
    const labels = {
        'draft': 'Draft',
        'pending_approval': 'Pending Approval',
        'approved': 'Approved',
        'verified': 'Verified',
        'processed': 'Processed',
        'cancelled': 'Cancelled'
    };
    const cls = status || 'draft';
    return `<span class="payroll-status-badge ${cls}">${labels[cls] || cls}</span>`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// LOAD PAYROLL CYCLES
// ============================================

function loadPayrollCycles() {
    const year = document.getElementById('filterYear')?.value || '';
    const month = document.getElementById('filterMonth')?.value || '';
    const status = document.getElementById('filterStatus')?.value || '';

    const params = new URLSearchParams();
    if (year) params.append('year', year);
    if (month) params.append('month', month);
    if (status) params.append('status', status);

    const tbody = document.getElementById('cyclesTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading payroll cycles...</p>
            </td>
        </tr>
    `;

    fetch(`?page=api_get_payroll_cycles&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const cycles = data.data.cycles || [];
                renderCycles(cycles);
                renderStats(cycles);
                document.getElementById('tableCount').textContent = cycles.length + ' cycles';

                // ✅ CHECK FOR DRAFT CYCLE IN CURRENT MONTH & SHOW BANNER
                const currentMonth = document.getElementById('monthSelect')?.value || String(new Date().getMonth() + 1).padStart(2, '0');
                const currentYear = document.getElementById('yearSelect')?.value || String(new Date().getFullYear());
                const hasDraft = cycles.some(c => 
                    c.status === 'draft' && 
                    c.start_date && 
                    c.start_date.startsWith(currentYear + '-' + currentMonth)
                );
                const alert = document.getElementById('payrollReadyAlert');
                if (alert) {
                    alert.style.display = hasDraft ? 'block' : 'none';
                }
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-danger py-4">
                            <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                            ${data.message || 'Failed to load payroll cycles'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

// ============================================
// RENDER CYCLES
// ============================================

function renderCycles(cycles) {
    const tbody = document.getElementById('cyclesTableBody');

    if (!cycles || cycles.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No payroll cycles found.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    cycles.forEach(cycle => {
        const status = cycle.status || 'draft';
        const canApprove = status === 'pending_approval';
        const canVerify = status === 'approved';
        const canProcess = status === 'verified';
        const canCancel = ['draft', 'pending_approval', 'approved'].includes(status);

        html += `
            <tr class="cycle-row">
                <td><strong>${escapeHtml(cycle.cycle_name)}</strong></td>
                <td>${formatDate(cycle.start_date)} - ${formatDate(cycle.end_date)}</td>
                <td>${formatDate(cycle.payment_date)}</td>
                <td>${cycle.total_employees || 0}</td>
                <td class="payroll-amount">${formatCurrency(cycle.total_gross)}</td>
                <td class="payroll-amount positive">${formatCurrency(cycle.total_net)}</td>
                <td>${getStatusBadge(status)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-entries-btn" data-id="${cycle.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary view-logs-btn" data-id="${cycle.id}">
                        <i class="bi bi-clock-history"></i>
                    </button>
                    ${canApprove ? `
                        <button class="btn btn-sm btn-success approve-btn" data-id="${cycle.id}">
                            <i class="bi bi-check2-circle"></i>
                        </button>
                    ` : ''}
                    ${canVerify ? `
                        <button class="btn btn-sm btn-info verify-btn" data-id="${cycle.id}">
                            <i class="bi bi-shield-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger reject-btn" data-id="${cycle.id}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ` : ''}
                    ${canProcess ? `
                        <button class="btn btn-sm btn-primary process-btn" data-id="${cycle.id}">
                            <i class="bi bi-play-circle"></i>
                        </button>
                    ` : ''}
                    ${canCancel ? `
                        <button class="btn btn-sm btn-danger cancel-btn" data-id="${cycle.id}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Attach event listeners
    document.querySelectorAll('.view-entries-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            viewEntries(id);
        });
    });

    document.querySelectorAll('.view-logs-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            viewLogs(id);
        });
    });

    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            approveCycle(id);
        });
    });

    document.querySelectorAll('.verify-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            verifyCycle(id);
        });
    });

    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openRejectModal(id);
        });
    });

    document.querySelectorAll('.process-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            processCycle(id);
        });
    });

    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            cancelCycle(id);
        });
    });
}

// ============================================
// RENDER STATS
// ============================================

function renderStats(cycles) {
    const stats = {
        total: cycles.length,
        draft: 0,
        pending_approval: 0,
        approved: 0,
        verified: 0,
        processed: 0,
        cancelled: 0
    };

    cycles.forEach(c => {
        if (stats[c.status] !== undefined) stats[c.status]++;
    });

    document.getElementById('statTotal').textContent = stats.total;
    document.getElementById('statDraft').textContent = stats.draft;
    document.getElementById('statPending').textContent = stats.pending_approval;
    document.getElementById('statApproved').textContent = stats.approved;
    document.getElementById('statVerified').textContent = stats.verified;
    document.getElementById('statProcessed').textContent = stats.processed;
}

// ============================================
// VIEW ENTRIES
// ============================================

function viewEntries(cycleId) {
    currentCycleId = cycleId;
    const body = document.getElementById('entriesBody');
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading entries...</p>
        </div>
    `;

    document.getElementById('exportCsvBtn').href = `?page=api_export_payroll&cycle_id=${cycleId}`;

    new bootstrap.Modal(document.getElementById('entriesModal')).show();

    fetch(`?page=api_get_payroll_entries&cycle_id=${cycleId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderEntries(data.data.entries);
            } else {
                body.innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                        ${data.message || 'Failed to load entries'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            body.innerHTML = `
                <div class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                    An error occurred. Please try again.
                </div>
            `;
        });
}

function renderEntries(entries) {
    const body = document.getElementById('entriesBody');

    if (!entries || entries.length === 0) {
        body.innerHTML = `<div class="text-center text-muted py-4">No entries found for this cycle.</div>`;
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Work Days</th>
                        <th>Attended</th>
                        <th>Absent</th>
                        <th>OT (hrs)</th>
                        <th>Late (min)</th>
                        <th>Regular Pay</th>
                        <th>OT Pay</th>
                        <th>Holiday Pay</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                    </tr>
                </thead>
                <tbody>
    `;

    entries.forEach(entry => {
        html += `
            <tr>
                <td><strong>${escapeHtml(entry.first_name)} ${escapeHtml(entry.last_name)}</strong><br><small class="text-muted">${escapeHtml(entry.employee_number)}</small></td>
                <td><span class="badge bg-info">${escapeHtml(entry.role)}</span></td>
                <td>${entry.total_working_days || 0}</td>
                <td>${entry.attended_days || 0}</td>
                <td>${entry.absent_days || 0}</td>
                <td>${entry.total_overtime_hours || 0}</td>
                <td>${entry.late_minutes || 0}</td>
                <td>${formatCurrency(entry.regular_pay)}</td>
                <td>${formatCurrency(entry.overtime_pay)}</td>
                <td>${formatCurrency(entry.holiday_pay)}</td>
                <td class="payroll-amount positive">${formatCurrency(entry.gross_pay)}</td>
                <td class="payroll-amount negative">${formatCurrency(entry.total_deductions)}</td>
                <td class="payroll-amount positive">${formatCurrency(entry.net_pay)}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
        <div class="mt-2 text-muted small">
            <i class="bi bi-info-circle me-1"></i>
            Total: ${entries.length} employees | 
            Gross: ${formatCurrency(entries.reduce((sum, e) => sum + parseFloat(e.gross_pay || 0), 0))} | 
            Net: ${formatCurrency(entries.reduce((sum, e) => sum + parseFloat(e.net_pay || 0), 0))}
        </div>
    `;

    body.innerHTML = html;
}

// ============================================
// VIEW LOGS
// ============================================

function viewLogs(cycleId) {
    const body = document.getElementById('logsBody');
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading logs...</p>
        </div>
    `;

    new bootstrap.Modal(document.getElementById('logsModal')).show();

    // Placeholder – logs API not implemented yet
    body.innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
            Approval logs will be available here.
        </div>
    `;
}

// ============================================
// CYCLE ACTIONS
// ============================================

function performAction(cycleId, action, endpoint, extraData = {}) {
    const btn = document.querySelector(`.${action}-btn[data-id="${cycleId}"]`);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    const data = { cycle_id: cycleId, ...extraData };
    if (action === 'cancel') data.action = 'cancel';

    fetch(`?page=${endpoint}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = action === 'approve' ? '<i class="bi bi-check2-circle"></i>' :
                            action === 'verify' ? '<i class="bi bi-shield-check"></i>' :
                            action === 'process' ? '<i class="bi bi-play-circle"></i>' :
                            '<i class="bi bi-x-circle"></i>';
        }
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || `${action} successful.`,
                timer: 1500,
                showConfirmButton: false
            });
            loadPayrollCycles();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: data.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = action === 'approve' ? '<i class="bi bi-check2-circle"></i>' :
                            action === 'verify' ? '<i class="bi bi-shield-check"></i>' :
                            action === 'process' ? '<i class="bi bi-play-circle"></i>' :
                            '<i class="bi bi-x-circle"></i>';
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
}

function approveCycle(id) {
    Swal.fire({
        title: 'Approve Payroll?',
        text: 'This will approve the payroll cycle. It will then be sent to Finance for verification.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            performAction(id, 'approve', 'api_approve_payroll');
        }
    });
}

function verifyCycle(id) {
    Swal.fire({
        title: 'Verify Payroll?',
        text: 'This will verify the payroll cycle. It is now ready for processing.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        confirmButtonText: 'Yes, Verify',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            performAction(id, 'verify', 'api_verify_payroll');
        }
    });
}

function processCycle(id) {
    Swal.fire({
        title: 'Process Payroll?',
        text: 'This will mark the payroll as processed. Payment has been completed.',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Process',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            performAction(id, 'process', 'api_process_payroll');
        }
    });
}

function cancelCycle(id) {
    Swal.fire({
        title: 'Cancel Payroll?',
        text: 'This will cancel the payroll cycle. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No, Keep'
    }).then(result => {
        if (result.isConfirmed) {
            performAction(id, 'cancel', 'api_cancel_payroll');
        }
    });
}

// ============================================
// REJECT MODAL (Finance)
// ============================================

let rejectCycleId = null;

function openRejectModal(cycleId) {
    rejectCycleId = cycleId;
    document.getElementById('rejectReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

document.getElementById('confirmRejectBtn').addEventListener('click', function() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        Swal.fire({
            icon: 'warning',
            title: 'Reason Required',
            text: 'Please provide a reason for rejecting this payroll.'
        });
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();

    // Perform reject action with reason
    const btn = document.querySelector(`.reject-btn[data-id="${rejectCycleId}"]`);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    const data = {
        cycle_id: rejectCycleId,
        action: 'reject',
        reason: reason
    };

    fetch('?page=api_verify_payroll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-x-circle"></i>';
        }
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Rejected',
                text: 'Payroll has been rejected with reason.',
                timer: 1500,
                showConfirmButton: false
            });
            loadPayrollCycles();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Rejection Failed',
                text: result.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-x-circle"></i>';
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
});

// ============================================
// CREATE PAYROLL CYCLE (Month + Half)
// ============================================

function openCreateCycleModal() {
    document.getElementById('createCycleForm').reset();
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('cyclePaymentDate').value = today;
    previewDates();
    new bootstrap.Modal(document.getElementById('createCycleModal')).show();
}

function getPeriodDates(year, month, half) {
    const daysInMonth = new Date(year, month, 0).getDate();
    let startDay, endDay;
    if (daysInMonth == 31) {
        if (half == 1) { startDay = 1; endDay = 16; }
        else { startDay = 17; endDay = 31; }
    } else if (daysInMonth == 30) {
        if (half == 1) { startDay = 1; endDay = 15; }
        else { startDay = 16; endDay = 30; }
    } else {
        if (half == 1) { startDay = 1; endDay = 15; }
        else { startDay = 16; endDay = daysInMonth; }
    }
    const pad = (n) => String(n).padStart(2, '0');
    return {
        startDate: `${year}-${pad(month)}-${pad(startDay)}`,
        endDate: `${year}-${pad(month)}-${pad(endDay)}`
    };
}

function previewDates() {
    const month = parseInt(document.getElementById('cycleMonth').value);
    const year = parseInt(document.getElementById('cycleYear').value);
    const half = parseInt(document.getElementById('cycleHalf').value);
    if (month && year && half) {
        const { startDate, endDate } = getPeriodDates(year, month, half);
        document.getElementById('previewDates').textContent =
            new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ' - ' +
            new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } else {
        document.getElementById('previewDates').textContent = 'Select month, year, and half to preview';
    }
}

document.getElementById('createCycleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    const month = parseInt(data.month);
    const year = parseInt(data.year);
    const half = parseInt(data.half);

    if (!month || !year || !half) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Selection',
            text: 'Please select month, year, and half.'
        });
        return;
    }

    const { startDate, endDate } = getPeriodDates(year, month, half);
    data.start_date = startDate;
    data.end_date = endDate;

    if (!data.payment_date) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Payment Date',
            text: 'Please select a payment date.'
        });
        return;
    }
    if (data.payment_date < data.end_date) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Date',
            text: 'Payment date must be after end date.'
        });
        return;
    }
    delete data.cycle_name;

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

    fetch('?page=api_create_payroll_cycle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Generate Payroll';
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Payroll Cycle Created!',
                text: result.message || 'Cycle created.',
                confirmButtonText: 'OK'
            });
            bootstrap.Modal.getInstance(document.getElementById('createCycleModal')).hide();
            loadPayrollCycles();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Creation Failed',
                text: result.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Generate Payroll';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
});

// ============================================
// EVENT LISTENERS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Load cycles on page load
    loadPayrollCycles();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'year', type: 'select', elementId: 'filterYear' },
            { key: 'month', type: 'select', elementId: 'filterMonth' },
            { key: 'status', type: 'select', elementId: 'filterStatus' },
        ]);
    }

    // Filter change
    document.getElementById('loadCyclesBtn').addEventListener('click', loadPayrollCycles);
    document.getElementById('refreshBtn').addEventListener('click', loadPayrollCycles);

    // Filter dropdowns – auto-load on change
    document.getElementById('filterYear')?.addEventListener('change', loadPayrollCycles);
    document.getElementById('filterMonth')?.addEventListener('change', loadPayrollCycles);
    document.getElementById('filterStatus')?.addEventListener('change', loadPayrollCycles);

    // Create cycle button
    document.getElementById('createCycleBtn').addEventListener('click', openCreateCycleModal);

    // Preview dates on selection change
    document.getElementById('cycleMonth')?.addEventListener('change', previewDates);
    document.getElementById('cycleYear')?.addEventListener('change', previewDates);
    document.getElementById('cycleHalf')?.addEventListener('change', previewDates);

    // Auto-close modals when clicking outside
    document.getElementById('entriesModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('entriesBody').innerHTML = '';
    });
    document.getElementById('logsModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('logsBody').innerHTML = '';
    });
});