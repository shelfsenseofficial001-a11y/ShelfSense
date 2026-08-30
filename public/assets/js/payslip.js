// ============================================
// PAYSLIP MODULE
// ============================================

console.log('✅ payslip.js loaded');

let currentPage = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadPayslips();
});

function loadPayslips(page = 1) {
    currentPage = page;
    
    const tbody = document.getElementById('payslipsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading payslips...</p>
            </td>
        </tr>
    `;
    
    const params = new URLSearchParams({
        p: page,
        limit: 10
    });
    
    fetch(`?page=api_get_payslip&${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderPayslips(data.data.payslips);
                renderPagination(data.data.pagination);
                renderStats(data.data.payslips);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            ${data.message || 'Failed to load payslips'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading payslips:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

function renderPayslips(payslips) {
    const tbody = document.getElementById('payslipsTableBody');
    
    if (!payslips || payslips.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No payslips found
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    payslips.forEach(payslip => {
        const statusBadge = `<span class="payslip-status-badge ${payslip.cycle_status}">${payslip.cycle_status.charAt(0).toUpperCase() + payslip.cycle_status.slice(1)}</span>`;
        html += `
            <tr class="payslip-card" data-id="${payslip.id}">
                <td>${payslip.formatted_start} - ${payslip.formatted_end}</td>
                <td>${payslip.formatted_payment_date}</td>
                <td class="payslip-amount positive">₱${payslip.gross_pay.toFixed(2)}</td>
                <td class="payslip-amount negative">₱${payslip.total_deductions.toFixed(2)}</td>
                <td class="payslip-amount positive fw-bold">₱${payslip.net_pay.toFixed(2)}</td>
                <td>${statusBadge}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-payslip-btn" data-id="${payslip.id}">
                        <i class="bi bi-eye"></i> View
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Attach click events
    document.querySelectorAll('.view-payslip-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            viewPayslip(this.dataset.id);
        });
    });
    
    document.querySelectorAll('.payslip-card').forEach(row => {
        row.addEventListener('click', function() {
            viewPayslip(this.dataset.id);
        });
    });
}

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const info = document.getElementById('tableInfo');
    
    if (!container) return;
    
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        if (info) info.textContent = `Showing ${pagination?.totalRecords || 0} payslips`;
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
            loadPayslips(page);
        });
    });
}

function renderStats(payslips) {
    if (!payslips || payslips.length === 0) {
        document.getElementById('statTotal').textContent = '0';
        document.getElementById('statTotalEarnings').textContent = '₱0.00';
        document.getElementById('statLatestNet').textContent = '₱0.00';
        return;
    }
    
    const total = payslips.length;
    const totalEarnings = payslips.reduce((sum, p) => sum + p.net_pay, 0);
    const latest = payslips[0].net_pay;
    
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statTotalEarnings').textContent = '₱' + totalEarnings.toFixed(2);
    document.getElementById('statLatestNet').textContent = '₱' + latest.toFixed(2);
}

function viewPayslip(payslipId) {
    const modal = document.getElementById('payslipDetailModal');
    const body = document.getElementById('payslipDetailBody');
    const printBtn = document.getElementById('printPayslipBtn');
    
    if (!modal || !body) return;
    
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;
    if (printBtn) printBtn.style.display = 'none';
    
    bootstrap.Offcanvas.getOrCreateInstance(modal).show();

    // Fetch the list and find the specific payslip
    fetch(`?page=api_get_payslip&p=1&limit=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const payslip = data.data.payslips.find(p => p.id == payslipId);
                if (payslip) {
                    renderPayslipDetail(payslip);
                    if (printBtn) {
                        printBtn.style.display = 'inline-block';
                        printBtn.onclick = function() {
                            window.print();
                        };
                    }
                } else {
                    body.innerHTML = `
                        <div class="text-center text-danger py-4">
                            Payslip not found
                        </div>
                    `;
                }
            } else {
                body.innerHTML = `
                    <div class="text-center text-danger py-4">
                        ${data.message || 'Failed to load payslip details'}
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

function renderPayslipDetail(payslip) {
    const body = document.getElementById('payslipDetailBody');
    if (!body) return;
    
    // Helper to format currency
    const fmt = (val) => '₱' + parseFloat(val || 0).toFixed(2);
    
    body.innerHTML = `
        <div class="text-center mb-4">
            <h5 class="fw-bold">ShelfSense Inc.</h5>
            <p class="text-muted small">Employee Payslip</p>
            <hr>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="payslip-detail-row">
                    <span class="label">Employee</span>
                    <span class="value">${payslip.first_name} ${payslip.last_name}</span>
                </div>
                <div class="payslip-detail-row">
                    <span class="label">Employee #</span>
                    <span class="value">${payslip.employee_number || 'N/A'}</span>
                </div>
                <div class="payslip-detail-row">
                    <span class="label">Pay Period</span>
                    <span class="value">${payslip.formatted_start} - ${payslip.formatted_end}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="payslip-detail-row">
                    <span class="label">Payment Date</span>
                    <span class="value">${payslip.formatted_payment_date}</span>
                </div>
                <div class="payslip-detail-row">
                    <span class="label">Monthly Salary</span>
                    <span class="value">${fmt(payslip.monthly_salary)}</span>
                </div>
                <div class="payslip-detail-row">
                    <span class="label">Status</span>
                    <span class="value"><span class="payslip-status-badge ${payslip.cycle_status}">${payslip.cycle_status.charAt(0).toUpperCase() + payslip.cycle_status.slice(1)}</span></span>
                </div>
            </div>
        </div>
        
        <h6 class="mt-3">Attendance Summary</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="payslip-detail-row">
                    <span class="label">Working Days</span>
                    <span class="value">${payslip.total_working_days || 0}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="payslip-detail-row">
                    <span class="label">Attended</span>
                    <span class="value">${payslip.attended_days || 0}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="payslip-detail-row">
                    <span class="label">Absent</span>
                    <span class="value">${payslip.absent_days || 0}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="payslip-detail-row">
                    <span class="label">Overtime Hours</span>
                    <span class="value">${payslip.total_overtime_hours || 0}</span>
                </div>
            </div>
        </div>
        
        <h6 class="mt-3">Earnings</h6>
        <div class="payslip-detail-row">
            <span class="label">Regular Pay</span>
            <span class="value">${fmt(payslip.regular_pay)}</span>
        </div>
        <div class="payslip-detail-row">
            <span class="label">Overtime Pay</span>
            <span class="value">${fmt(payslip.overtime_pay)}</span>
        </div>
        <div class="payslip-detail-row">
            <span class="label">Holiday Pay</span>
            <span class="value">${fmt(payslip.holiday_pay)}</span>
        </div>
        <div class="payslip-detail-row payslip-total-row">
            <span class="label">Gross Pay</span>
            <span class="value">${fmt(payslip.gross_pay)}</span>
        </div>
        
        <h6 class="mt-3">Deductions</h6>
        <div class="payslip-detail-row">
            <span class="label">Late Deduction</span>
            <span class="value">${fmt(payslip.late_deduction)}</span>
        </div>
        <div class="payslip-detail-row">
            <span class="label">Absent Deduction</span>
            <span class="value">${fmt(payslip.absent_deduction)}</span>
        </div>
        <div class="payslip-detail-row">
            <span class="label">Unpaid Leave Deduction</span>
            <span class="value">${fmt(payslip.unpaid_leave_deduction)}</span>
        </div>
        <div class="payslip-detail-row">
            <span class="label">Other Deductions</span>
            <span class="value">${fmt(payslip.other_deductions)}</span>
        </div>
        <div class="payslip-detail-row payslip-total-row">
            <span class="label">Total Deductions</span>
            <span class="value">${fmt(payslip.total_deductions)}</span>
        </div>
        
        <div class="payslip-detail-row payslip-total-row" style="border-top: 3px solid var(--brand-yellow); font-size: 1.2rem;">
            <span class="label">Net Pay</span>
            <span class="value text-success fw-bold">${fmt(payslip.net_pay)}</span>
        </div>
        
        ${payslip.notes ? `
            <div class="mt-3">
                <label class="text-muted small">Notes</label>
                <p class="small">${payslip.notes}</p>
            </div>
        ` : ''}
    `;
}