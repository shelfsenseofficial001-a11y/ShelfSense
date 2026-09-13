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

    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('entriesModal')).show();

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
    hidePaydayWarning();
    previewDates();
    new bootstrap.Modal(document.getElementById('createCycleModal')).show();
}

// Fixed cutoff split (mirrors App\Core\CutoffPeriod::getHalves() in PHP):
// H1 is always 1st-15th, H2 is always 16th through the last day of the
// month, regardless of month length -- no more per-month-length branching
// to keep in sync between the two implementations.
function getPeriodDates(year, month, half) {
    const daysInMonth = new Date(year, month, 0).getDate();
    const startDay = half == 1 ? 1 : 16;
    const endDay = half == 1 ? 15 : daysInMonth;
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
        suggestPaydayFor(endDate);
    } else {
        document.getElementById('previewDates').textContent = 'Select month, year, and half to preview';
    }
}

// ============================================
// PAYDAY ADJUSTMENT LOGIC
// Buffer of up to 5 days after the cutoff ends, for HR to review payslips
// before pay actually goes out. A weekend/holiday payday is flagged --
// informational, not a hard block, since disbursement itself may happen
// through a different channel/schedule; this is just so HR is clear on
// the target pay date.
// ============================================

let holidaysCache = {}; // year -> Set of 'YYYY-MM-DD' strings

function addDaysToDate(dateStr, n) {
    const d = new Date(dateStr + 'T00:00:00Z');
    d.setUTCDate(d.getUTCDate() + n);
    return d.toISOString().slice(0, 10);
}

function isWeekend(dateStr) {
    const dow = new Date(dateStr + 'T00:00:00Z').getUTCDay();
    return dow === 0 || dow === 6;
}

function loadHolidaysForYear(year) {
    if (holidaysCache[year]) return Promise.resolve(holidaysCache[year]);
    return fetch(`?page=api_get_holidays&year=${year}`)
        .then(r => r.json())
        .then(data => {
            const set = new Set((data.success ? data.data.holidays : []).map(h => h.holiday_date));
            holidaysCache[year] = set;
            return set;
        })
        .catch(() => new Set());
}

function isHolidayDate(dateStr, set) {
    return set.has(dateStr);
}

function checkPaydayDate(dateStr) {
    const year = parseInt(dateStr.slice(0, 4));
    return loadHolidaysForYear(year).then(set => {
        const weekend = isWeekend(dateStr);
        const holiday = isHolidayDate(dateStr, set);
        return { weekend, holiday, invalid: weekend || holiday };
    });
}

function showPaydayWarning(text) {
    const el = document.getElementById('paydayWarning');
    document.getElementById('paydayWarningText').textContent = text;
    el.style.display = 'block';
}

function hidePaydayWarning() {
    document.getElementById('paydayWarning').style.display = 'none';
}

function suggestPaydayFor(endDate) {
    const year = parseInt(endDate.slice(0, 4));
    loadHolidaysForYear(year).then(set => {
        // Prefer the latest day within the 5-day buffer (closest to the
        // full review window), falling back earlier if it lands on a
        // weekend/holiday.
        let chosen = null;
        for (let offset = 5; offset >= 1; offset--) {
            const candidate = addDaysToDate(endDate, offset);
            if (!isWeekend(candidate) && !isHolidayDate(candidate, set)) {
                chosen = candidate;
                break;
            }
        }
        const input = document.getElementById('cyclePaymentDate');
        if (chosen) {
            input.value = chosen;
            hidePaydayWarning();
        } else {
            // Every day in the 5-day buffer is a weekend/holiday -- rare,
            // but leave the field for HR to pick manually instead of
            // guessing further out.
            input.value = '';
            showPaydayWarning('Every date in the usual 5-day payday buffer after this cutoff falls on a weekend or holiday. Please choose a payday manually.');
        }
    });
}

document.getElementById('cyclePaymentDate')?.addEventListener('change', function () {
    if (!this.value) { hidePaydayWarning(); return; }
    checkPaydayDate(this.value).then(({ weekend, holiday }) => {
        if (weekend) {
            showPaydayWarning('This payday falls on a weekend. Consider selecting another date -- this does not block creating the cycle, it\'s just so it\'s clear when pay is expected.');
        } else if (holiday) {
            showPaydayWarning('This payday falls on a holiday. Consider selecting another date -- this does not block creating the cycle, it\'s just so it\'s clear when pay is expected.');
        } else {
            hidePaydayWarning();
        }
    });
});

// ============================================
// MANAGE HOLIDAYS
// ============================================

function loadHolidaysList() {
    const tbody = document.getElementById('holidaysTableBody');
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>`;

    fetch('?page=api_get_holidays')
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">${data.message || 'Failed to load'}</td></tr>`;
                return;
            }
            const holidays = data.data.holidays || [];
            if (!holidays.length) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No holidays recorded.</td></tr>`;
                return;
            }
            tbody.innerHTML = holidays.map(h => `
                <tr>
                    <td>${formatDate(h.holiday_date)}</td>
                    <td>${escapeHtml(h.name)}</td>
                    <td><span class="badge ${h.type === 'regular' ? 'bg-primary' : 'bg-secondary'}">${h.type === 'regular' ? 'Regular' : 'Special'}</span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger delete-holiday-btn" data-id="${h.id}"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `).join('');

            tbody.querySelectorAll('.delete-holiday-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    Swal.fire({
                        title: 'Remove this holiday?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, remove',
                        confirmButtonColor: '#dc3545'
                    }).then(result => {
                        if (!result.isConfirmed) return;
                        fetch('?page=api_delete_holiday', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: this.dataset.id })
                        })
                            .then(r => r.json())
                            .then(res => {
                                if (res.success) {
                                    holidaysCache = {};
                                    loadHolidaysList();
                                } else {
                                    Swal.fire('Error', res.message || 'Failed to remove holiday', 'error');
                                }
                            });
                    });
                });
            });
        });
}

document.getElementById('manageHolidaysBtn')?.addEventListener('click', function () {
    loadHolidaysList();
    new bootstrap.Modal(document.getElementById('holidaysModal')).show();
});

document.getElementById('addHolidayForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const date = document.getElementById('holidayDate').value;
    const name = document.getElementById('holidayName').value.trim();
    const type = document.getElementById('holidayType').value;

    if (!date || !name) return;

    fetch('?page=api_save_holiday', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ holiday_date: date, name, type })
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('addHolidayForm').reset();
                document.getElementById('holidayType').value = 'special_non_working';
                holidaysCache = {};
                loadHolidaysList();
            } else {
                Swal.fire('Error', res.message || 'Failed to add holiday', 'error');
            }
        });
});

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
    if (window.__INITIAL_DATA__) {
        const cycles = window.__INITIAL_DATA__.cycles || [];
        renderCycles(cycles);
        renderStats(cycles);
        document.getElementById('tableCount').textContent = cycles.length + ' cycles';
        const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');
        const currentYear = String(new Date().getFullYear());
        const hasDraft = cycles.some(c =>
            c.status === 'draft' &&
            c.start_date &&
            c.start_date.startsWith(currentYear + '-' + currentMonth)
        );
        const alert = document.getElementById('payrollReadyAlert');
        if (alert) alert.style.display = hasDraft ? 'block' : 'none';
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        loadPayrollCycles();
    }

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
    document.getElementById('entriesModal')?.addEventListener('hidden.bs.offcanvas', function() {
        document.getElementById('entriesBody').innerHTML = '';
    });
    document.getElementById('logsModal')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('logsBody').innerHTML = '';
    });
});