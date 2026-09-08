// ============================================
// FINANCE HEAD — BUDGET MANAGEMENT (ledger-based)
// ============================================

let fhBudgetSetTarget = { departmentId: null, periodKey: null };

document.addEventListener('DOMContentLoaded', function () {
    const period = document.getElementById('monthFilter').value;
    if (window.__INITIAL_DATA__) {
        renderOverviewTable(window.__INITIAL_DATA__.statuses, period);
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        loadOverview(period);
    }
    loadDepartments();
    loadHistory();
    loadTolerance();

    document.getElementById('monthFilter').addEventListener('change', function () { loadOverview(this.value); });
    document.getElementById('addDeptBtn').addEventListener('click', addDepartment);
    document.getElementById('confirmSetBudgetBtn').addEventListener('click', confirmSetBudget);
    document.getElementById('saveToleranceBtn').addEventListener('click', saveTolerance);
});

function renderOverviewTable(statuses, periodKey) {
    const container = document.getElementById('fh-budget-table');
    statuses = statuses || [];
    container.innerHTML = statuses.length ? `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Department</th><th>Allocated</th><th>Used</th><th>Reserved</th><th>Available</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    ${statuses.map(b => `
                        <tr>
                            <td class="fw-semibold">${prEscapeHtml(b.department_name)}</td>
                            <td>${prCurrency(b.allocated)}</td>
                            <td>${prCurrency(b.used)}</td>
                            <td>${prCurrency(b.reserved)}</td>
                            <td class="fw-semibold">${prCurrency(b.available)}</td>
                            <td>${prStatusBadge(b.status)} ${b.used_percentage !== null ? `<span class="text-muted small">(${b.used_percentage}%)</span>` : ''}</td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="openSetBudget(${b.department_id}, '${periodKey}', '${prEscapeHtml(b.department_name)}', ${b.allocated})">Set Allocation</button></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    ` : `<div class="text-center text-muted py-4">No departments yet — add one in the Departments tab.</div>`;
}

async function loadOverview(periodKey) {
    const container = document.getElementById('fh-budget-table');
    container.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>`;
    try {
        const data = await prFetchJson(`?page=api_get_budget_overview&period_key=${encodeURIComponent(periodKey)}`);
        renderOverviewTable(data.statuses, periodKey);
    } catch (e) {
        container.innerHTML = `<div class="text-danger text-center py-4">${prEscapeHtml(e.message)}</div>`;
    }
}

function openSetBudget(departmentId, periodKey, name, currentAmount) {
    fhBudgetSetTarget = { departmentId, periodKey };
    document.getElementById('setBudgetDeptLabel').textContent = `${name} — ${periodKey}`;
    document.getElementById('setBudgetAmount').value = currentAmount;
    document.getElementById('setBudgetReason').value = '';
    new bootstrap.Modal(document.getElementById('setBudgetModal')).show();
}

async function confirmSetBudget() {
    const amount = parseFloat(document.getElementById('setBudgetAmount').value);
    const reason = document.getElementById('setBudgetReason').value.trim();
    if (isNaN(amount) || amount < 0) { Swal.fire('Invalid amount', '', 'warning'); return; }
    try {
        const res = await fetch('?page=api_fh_set_budget', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ department_id: fhBudgetSetTarget.departmentId, period_key: fhBudgetSetTarget.periodKey, amount, reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('setBudgetModal'))?.hide();
        Swal.fire('Saved', data.message, 'success');
        loadOverview(document.getElementById('monthFilter').value);
        loadHistory();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    }
}

async function loadDepartments() {
    const tbody = document.getElementById('deptTableBody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_departments');
        const rows = data.departments || [];
        tbody.innerHTML = rows.length ? rows.map(d => `
            <tr>
                <td>${prEscapeHtml(d.name)}</td>
                <td>${prEscapeHtml(d.code || '')}</td>
                <td>${d.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                <td><button class="btn btn-sm btn-outline-secondary" onclick="toggleDept(${d.id}, ${d.is_active ? 0 : 1})">${d.is_active ? 'Deactivate' : 'Activate'}</button></td>
            </tr>
        `).join('') : '<tr><td colspan="4" class="text-center text-muted py-3">No departments yet.</td></tr>';
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center py-3">${prEscapeHtml(e.message)}</td></tr>`;
    }
}

async function addDepartment() {
    const name = document.getElementById('newDeptName').value.trim();
    const code = document.getElementById('newDeptCode').value.trim();
    if (!name) { Swal.fire('Name required', '', 'warning'); return; }
    try {
        const res = await fetch('?page=api_departments', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', name, code }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        document.getElementById('newDeptName').value = '';
        document.getElementById('newDeptCode').value = '';
        loadDepartments();
        loadOverview(document.getElementById('monthFilter').value);
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    }
}

async function toggleDept(id, active) {
    try {
        const res = await fetch('?page=api_departments', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle', id, is_active: active }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        loadDepartments();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    }
}

async function loadHistory() {
    const tbody = document.getElementById('historyTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_get_budget_history&limit=30');
        const rows = data.transactions || [];
        tbody.innerHTML = rows.length ? rows.map(t => `
            <tr>
                <td>${prFormatDate(t.created_at)}</td>
                <td>${prEscapeHtml(t.department_name)}</td>
                <td>${prEscapeHtml(t.period_key)}</td>
                <td><span class="badge bg-secondary">${prEscapeHtml(t.type)}</span></td>
                <td>${prCurrency(t.amount)}</td>
                <td>${prEscapeHtml(t.first_name)} ${prEscapeHtml(t.last_name)}</td>
                <td class="small text-muted">${prEscapeHtml(t.notes || '')}</td>
            </tr>
        `).join('') : prEmptyRow(7, 'No transactions yet.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(7, e.message);
    }
}

async function loadTolerance() {
    try {
        const data = await prFetchJson('?page=api_variance_tolerance');
        document.getElementById('tolPricePercent').value = data.price_tolerance_percent;
        document.getElementById('tolPriceAmount').value = data.price_tolerance_amount;
        document.getElementById('tolQtyPercent').value = data.quantity_tolerance_percent;
    } catch (e) {
        console.error(e);
    }
}

async function saveTolerance() {
    try {
        const res = await fetch('?page=api_variance_tolerance', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                price_tolerance_percent: parseFloat(document.getElementById('tolPricePercent').value) || 0,
                price_tolerance_amount: parseFloat(document.getElementById('tolPriceAmount').value) || 0,
                quantity_tolerance_percent: parseFloat(document.getElementById('tolQtyPercent').value) || 0,
            }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Saved', data.message, 'success');
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    }
}
