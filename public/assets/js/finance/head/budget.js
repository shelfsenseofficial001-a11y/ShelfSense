// ============================================
// FINANCE HEAD - BUDGET MANAGEMENT
// ============================================

console.log('✅ finance/head/budget.js loaded');

let fhbDepartments = [];
let fhbLastOverview = null;
let fhbHistoryPage = 1;

document.addEventListener('DOMContentLoaded', function () {
    const monthFilter = document.getElementById('monthFilter');
    const budgetMonth = document.getElementById('budgetMonth');

    loadBudgetPage();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'department', type: 'select', elementId: 'departmentFilter' },
        ]);
    }

    monthFilter.addEventListener('change', function () {
        budgetMonth.value = this.value;
        loadBudgetPage();
    });
    document.getElementById('departmentFilter').addEventListener('change', function () {
        if (this.value) {
            document.getElementById('budgetDepartment').value = this.value;
            window.refreshSearchableSelect && window.refreshSearchableSelect(document.getElementById('budgetDepartment'));
        }
        loadUsageAndHistory();
    });
    document.getElementById('refreshBtn').addEventListener('click', loadBudgetPage);
    document.getElementById('printBtn').addEventListener('click', () => window.print());
    document.getElementById('exportBtn').addEventListener('click', exportOverviewCsv);

    document.getElementById('budgetDepartment').addEventListener('change', updateCurrentBudgetInfo);
    document.getElementById('budgetMonth').addEventListener('change', function () {
        monthFilter.value = this.value;
        loadBudgetPage();
    });
    document.getElementById('budgetAmount').addEventListener('input', updateAdjustmentPreview);
    document.getElementById('setBudgetForm').addEventListener('submit', submitBudget);
});

function currentMonth() {
    return document.getElementById('monthFilter').value || new Date().toISOString().slice(0, 7);
}
function currentDepartment() {
    return document.getElementById('departmentFilter').value || '';
}

function loadBudgetPage() {
    const monthYear = currentMonth();
    const container = document.getElementById('fn-overview-table');
    container.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;

    const dept = currentDepartment();
    fetch(`?page=api_finance_get_budget&month=${encodeURIComponent(monthYear)}${dept ? '&department=' + encodeURIComponent(dept) : ''}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = fnErrorState(data.message);
                return;
            }
            fhbLastOverview = data.data;
            fhbDepartments = data.data.departments || [];
            populateDepartmentSelects(data.data.selected_department);
            renderOverviewTable(data.data.department_statuses, monthYear);
            renderNearLimit(data.data.departments_near_limit);
            renderUsageTable(data.data.usage, data.data.selected_department, monthYear);
            document.getElementById('lastUpdated').textContent = `Last updated: ${new Date(data.data.generated_at.replace(' ', 'T')).toLocaleString()}`;
            updateCurrentBudgetInfo();
            loadAdjustmentHistory(1);
        })
        .catch(() => { container.innerHTML = fnErrorState(); });
}

function populateDepartmentSelects(selectedDept) {
    const filterSel = document.getElementById('departmentFilter');
    const formSel = document.getElementById('budgetDepartment');

    const filterCurrent = filterSel.value;
    filterSel.innerHTML = '<option value="">All Departments</option>' +
        fhbDepartments.map(d => `<option value="${fnEscapeHtml(d)}">${fnEscapeHtml(fhbDeptLabel(d))}</option>`).join('');
    filterSel.value = filterCurrent || '';

    const formCurrent = formSel.value || selectedDept;
    formSel.innerHTML = fhbDepartments.map(d => `<option value="${fnEscapeHtml(d)}">${fnEscapeHtml(fhbDeptLabel(d))}</option>`).join('');
    if (formCurrent) formSel.value = formCurrent;
    document.getElementById('budgetMonth').value = currentMonth();

    window.refreshSearchableSelect && window.refreshSearchableSelect(filterSel);
    window.refreshSearchableSelect && window.refreshSearchableSelect(formSel);
}

function fhbDeptLabel(d) {
    return d.charAt(0).toUpperCase() + d.slice(1);
}

function renderOverviewTable(statuses, monthYear) {
    const container = document.getElementById('fn-overview-table');
    if (!statuses || statuses.length === 0) {
        container.innerHTML = fnEmptyState(`No departments found for ${monthYear}.`);
        return;
    }
    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Department</th><th>Allocated</th><th>Used</th><th>Reserved</th><th>Available</th><th>% Used</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${statuses.map(d => `
                        <tr>
                            <td class="fw-semibold text-capitalize">${fnEscapeHtml(d.department)}</td>
                            <td>${d.has_allocation ? fnCurrency(d.allocated) : '<span class="text-muted">—</span>'}</td>
                            <td>${fnCurrency(d.used)}</td>
                            <td>${fnCurrency(d.reserved)}</td>
                            <td class="fw-semibold">${d.has_allocation ? fnCurrency(d.available) : '<span class="text-muted">—</span>'}</td>
                            <td>${d.used_percentage !== null ? d.used_percentage + '%' : '—'}</td>
                            <td>${fnBudgetStatusBadge(d.status)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function renderNearLimit(nearLimit) {
    const box = document.getElementById('fn-near-limit-box');
    if (!nearLimit || nearLimit.length === 0) {
        box.innerHTML = `<div class="alert alert-success small mb-3"><i class="bi bi-check-circle me-1"></i>No departments are currently approaching their budget limit (80%).</div>`;
        return;
    }
    box.innerHTML = `
        <div class="fn-budget-warning mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Departments approaching/at budget limit (&gt;80%):</strong>
            ${nearLimit.map(d => `${fnEscapeHtml(fhbDeptLabel(d.department))} (${d.used_percentage}%)`).join(', ')}
        </div>
    `;
}

function renderUsageTable(usage, department, monthYear) {
    const container = document.getElementById('fn-usage-table');
    if (!department) {
        container.innerHTML = fnEmptyState('No department selected.');
        return;
    }
    if (!usage || usage.length === 0) {
        container.innerHTML = fnEmptyState(`No requisitions booked against ${fhbDeptLabel(department)} for ${monthYear}.`);
        return;
    }
    const total = usage.reduce((sum, r) => sum + parseFloat(r.total || 0), 0);
    container.innerHTML = `
        <p class="text-muted small mb-2">Department: <strong class="text-capitalize">${fnEscapeHtml(department)}</strong> — Period: <strong>${fnEscapeHtml(monthYear)}</strong></p>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead><tr><th>Requisition #</th><th>Supplier</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    ${usage.map(r => `
                        <tr>
                            <td>${fnEscapeHtml(r.requisition_number)}</td>
                            <td>${fnEscapeHtml(r.company_name)}</td>
                            <td>${fnCurrency(r.total)}</td>
                            <td>${fnFormatDate(r.order_date)}</td>
                            <td>${fnReqStatusBadge(r.status)}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr><th colspan="2" class="text-end">Total</th><th>${fnCurrency(total)}</th><th colspan="2"></th></tr>
                </tfoot>
            </table>
        </div>
    `;
}

function loadUsageAndHistory() {
    if (!fhbLastOverview) return;
    const dept = currentDepartment() || fhbLastOverview.selected_department;
    const monthYear = currentMonth();
    fetch(`?page=api_finance_get_budget&month=${encodeURIComponent(monthYear)}&department=${encodeURIComponent(dept)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            fhbLastOverview = data.data;
            renderUsageTable(data.data.usage, data.data.selected_department, monthYear);
            updateCurrentBudgetInfo();
        });
    loadAdjustmentHistory(1);
}

function updateCurrentBudgetInfo() {
    const dept = document.getElementById('budgetDepartment').value;
    if (!fhbLastOverview || !fhbLastOverview.department_statuses) return;
    const status = fhbLastOverview.department_statuses.find(d => d.department === dept) || fhbLastOverview.selected;
    const info = document.getElementById('currentBudgetInfo');
    if (!status) { info.innerHTML = ''; return; }
    info.innerHTML = `Current: <strong>${status.has_allocation ? fnCurrency(status.allocated) : '₱0.00'}</strong> allocated &middot; ` +
        `Used: <strong>${fnCurrency(status.used)}</strong> (${status.used_percentage !== null ? status.used_percentage : 0}%) &middot; Reserved: <strong>${fnCurrency(status.reserved)}</strong>`;
    updateAdjustmentPreview();
}

function updateAdjustmentPreview() {
    const dept = document.getElementById('budgetDepartment').value;
    const newAmount = parseFloat(document.getElementById('budgetAmount').value);
    const preview = document.getElementById('adjustmentPreview');
    if (!fhbLastOverview || isNaN(newAmount)) { preview.innerHTML = ''; return; }
    const status = (fhbLastOverview.department_statuses || []).find(d => d.department === dept);
    if (!status) { preview.innerHTML = ''; return; }

    const current = status.has_allocation ? status.allocated : 0;
    const diff = newAmount - current;
    const committed = status.used + status.reserved;
    let html = `This will ${diff >= 0 ? 'increase' : 'decrease'} the allocated budget by ${fnCurrency(Math.abs(diff))}. ` +
        `New available: <strong>${fnCurrency(newAmount - committed)}</strong>.`;
    if (newAmount < committed) {
        html += `<br><span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Warning: this is below the ${fnCurrency(committed)} already used/reserved for this period.</span>`;
    }
    preview.innerHTML = html;
}

function submitBudget(e) {
    e.preventDefault();
    const btn = document.getElementById('setBudgetBtn');
    const department = document.getElementById('budgetDepartment').value;
    const monthYear = document.getElementById('budgetMonth').value;
    const amount = document.getElementById('budgetAmount').value;
    const reason = document.getElementById('budgetReason').value.trim();
    const msgBox = document.getElementById('budgetMessage');

    if (!department || !monthYear || amount === '' || parseFloat(amount) < 0) {
        msgBox.innerHTML = `<div class="alert alert-danger small mb-0">Please provide a department, month, and a non-negative amount.</div>`;
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    msgBox.innerHTML = '';

    fetch('?page=api_finance_set_budget', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ department, month_year: monthYear, allocated_budget: parseFloat(amount), reason })
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Save Budget';
            if (data.success) {
                msgBox.innerHTML = `<div class="alert alert-success small mb-0">${fnEscapeHtml(data.message)}</div>`;
                document.getElementById('budgetReason').value = '';
                document.getElementById('budgetAmount').value = '';
                document.getElementById('adjustmentPreview').innerHTML = '';
                document.getElementById('monthFilter').value = monthYear;
                loadBudgetPage();
            } else {
                msgBox.innerHTML = `<div class="alert alert-danger small mb-0">${fnEscapeHtml(data.message || 'Failed to save budget.')}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save"></i> Save Budget';
            msgBox.innerHTML = `<div class="alert alert-danger small mb-0">Something went wrong. Please try again.</div>`;
        });
}

function loadAdjustmentHistory(page) {
    fhbHistoryPage = page;
    const dept = document.getElementById('budgetDepartment').value;
    const monthYear = document.getElementById('budgetMonth').value;
    const container = document.getElementById('fn-adjustment-history');
    container.innerHTML = `<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>`;

    const params = new URLSearchParams({ p: page, limit: 5 });
    if (dept) params.append('department', dept);
    if (monthYear) params.append('month', monthYear);

    fetch(`?page=api_finance_get_budget_adjustments&${params}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { container.innerHTML = fnErrorState(data.message); return; }
            renderAdjustmentHistory(data.data.adjustments);
            fnRenderPagination(
                document.getElementById('historyPagination'),
                document.getElementById('historyInfo'),
                data.data.pagination,
                'adjustments',
                (p) => loadAdjustmentHistory(p)
            );
        })
        .catch(() => { container.innerHTML = fnErrorState(); });
}

function renderAdjustmentHistory(rows) {
    const container = document.getElementById('fn-adjustment-history');
    if (!rows || rows.length === 0) {
        container.innerHTML = fnEmptyState('No allocation adjustments recorded yet.');
        return;
    }
    container.innerHTML = rows.map(a => `
        <div class="border-bottom py-2 small">
            <div class="d-flex justify-content-between">
                <strong class="text-capitalize">${fnEscapeHtml(a.department)}</strong>
                <span class="text-muted">${fnFormatDate(a.created_at, true)}</span>
            </div>
            <div>${fnCurrency(a.previous_allocated)} → ${fnCurrency(a.new_allocated)}
                <span class="${a.adjustment_amount >= 0 ? 'text-success' : 'text-danger'}">(${a.adjustment_amount >= 0 ? '+' : ''}${fnCurrency(a.adjustment_amount)})</span>
                — ${fnEscapeHtml(a.month_year)}
            </div>
            <div class="text-muted">By ${fnEscapeHtml(a.first_name)} ${fnEscapeHtml(a.last_name)}${a.reason ? ' — ' + fnEscapeHtml(a.reason) : ''}</div>
        </div>
    `).join('');
}

function exportOverviewCsv() {
    if (!fhbLastOverview || !fhbLastOverview.department_statuses) return;
    const rows = [['Department', 'Month', 'Allocated', 'Used', 'Reserved', 'Available', '% Used', 'Status']];
    fhbLastOverview.department_statuses.forEach(d => {
        rows.push([d.department, d.month_year, d.allocated, d.used, d.reserved, d.available, d.used_percentage ?? '', d.status]);
    });
    const csv = rows.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `budget_report_${currentMonth()}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
