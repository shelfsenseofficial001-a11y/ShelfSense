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
    loadBudgetRequests();

    document.getElementById('monthFilter').addEventListener('change', function () { loadOverview(this.value); });
    document.getElementById('addDeptBtn').addEventListener('click', addDepartment);
    document.getElementById('confirmSetBudgetBtn').addEventListener('click', confirmSetBudget);
    document.getElementById('saveToleranceBtn').addEventListener('click', saveTolerance);
    document.getElementById('confirmRejectBudgetRequestBtn').addEventListener('click', confirmRejectBudgetRequest);

    // Live validation: block the keystrokes that are never valid, and show
    // the error as soon as a field is touched rather than on submit.
    const amountInput = document.getElementById('setBudgetAmount');
    fnBlockInvalidNumberKeys(amountInput, true);
    ['input', 'blur'].forEach(evt => amountInput.addEventListener(evt, () => {
        amountInput.dataset.touched = '1';
        validateSetBudget();
    }));

    FH_TOLERANCE_FIELDS.forEach(f => {
        const input = document.getElementById(f.id);
        if (!input) return;
        fnBlockInvalidNumberKeys(input, true);
        ['input', 'blur'].forEach(evt => input.addEventListener(evt, () => {
            input.dataset.touched = '1';
            validateTolerance();
        }));
    });

    ['newDeptName', 'newDeptCode'].forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;
        ['input', 'blur'].forEach(evt => input.addEventListener(evt, () => {
            input.dataset.touched = '1';
            validateNewDepartment();
        }));
    });
    validateNewDepartment();
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

function validateSetBudget() {
    const input = document.getElementById('setBudgetAmount');
    const error = fnValidateNumber(input.value, { required: true, min: 0, max: 99999999.99, label: 'Allocated amount' });
    fnSetFieldError(input, 'setBudgetAmountError', input.dataset.touched ? error : '');
    const btn = document.getElementById('confirmSetBudgetBtn');
    if (btn) btn.disabled = !!error;
    return !error;
}

function openSetBudget(departmentId, periodKey, name, currentAmount) {
    fhBudgetSetTarget = { departmentId, periodKey };
    document.getElementById('setBudgetDeptLabel').textContent = `${name} — ${periodKey}`;
    const input = document.getElementById('setBudgetAmount');
    input.value = currentAmount;
    delete input.dataset.touched;
    fnSetFieldError(input, 'setBudgetAmountError', '');
    document.getElementById('setBudgetReason').value = '';
    validateSetBudget();
    new bootstrap.Modal(document.getElementById('setBudgetModal')).show();
}

async function confirmSetBudget() {
    const amountInput = document.getElementById('setBudgetAmount');
    amountInput.dataset.touched = '1';
    if (!validateSetBudget()) { amountInput.focus(); return; }
    const amount = parseFloat(amountInput.value);
    const reason = document.getElementById('setBudgetReason').value.trim();
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

// Department code is optional, but when given it is an identifier used in
// reports -- restrict it to letters/digits/dashes rather than letting any
// free text through.
function validateNewDepartment() {
    const nameInput = document.getElementById('newDeptName');
    const codeInput = document.getElementById('newDeptCode');
    const name = nameInput.value.trim();
    const code = codeInput.value.trim();

    let nameError = '';
    if (!name) nameError = 'Department name is required.';
    else if (name.length < 2) nameError = 'Use at least 2 characters.';
    else if (!/^[A-Za-z0-9 &().,'\-]+$/.test(name)) nameError = 'Letters, numbers and basic punctuation only.';

    let codeError = '';
    if (code && !/^[A-Za-z0-9-]+$/.test(code)) codeError = 'Letters, numbers and dashes only -- no spaces.';

    fnSetFieldError(nameInput, 'newDeptNameError', nameInput.dataset.touched ? nameError : '');
    fnSetFieldError(codeInput, 'newDeptCodeError', codeInput.dataset.touched ? codeError : '');

    const btn = document.getElementById('addDeptBtn');
    if (btn) btn.disabled = !!(nameError || codeError);
    return !nameError && !codeError;
}

async function addDepartment() {
    const nameInput = document.getElementById('newDeptName');
    const codeInput = document.getElementById('newDeptCode');
    nameInput.dataset.touched = '1';
    codeInput.dataset.touched = '1';
    if (!validateNewDepartment()) {
        (document.querySelector('#deptTab .is-invalid') || nameInput).focus();
        return;
    }
    const name = nameInput.value.trim();
    const code = codeInput.value.trim();
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

// The three tolerance fields. Percentages are capped at 100 -- a tolerance
// above that would accept any invoice price at all, silently disabling the
// variance check these settings exist to enforce.
const FH_TOLERANCE_FIELDS = [
    { id: 'tolPricePercent', errorId: 'tolPricePercentError', max: 100, label: 'Price tolerance' },
    { id: 'tolPriceAmount', errorId: 'tolPriceAmountError', max: 999999.99, label: 'Price tolerance' },
    { id: 'tolQtyPercent', errorId: 'tolQtyPercentError', max: 100, label: 'Quantity tolerance' },
];

function validateTolerance() {
    let valid = true;
    FH_TOLERANCE_FIELDS.forEach(f => {
        const input = document.getElementById(f.id);
        if (!input) return;
        const error = fnValidateNumber(input.value, { required: true, min: 0, max: f.max, label: f.label });
        if (error) valid = false;
        fnSetFieldError(input, f.errorId, input.dataset.touched ? error : '');
    });
    const btn = document.getElementById('saveToleranceBtn');
    if (btn) btn.disabled = !valid;
    return valid;
}

async function saveTolerance() {
    let firstBad = null;
    FH_TOLERANCE_FIELDS.forEach(f => {
        const input = document.getElementById(f.id);
        if (input) input.dataset.touched = '1';
    });
    if (!validateTolerance()) {
        firstBad = document.querySelector('#toleranceTab .is-invalid');
        if (firstBad) firstBad.focus();
        return;
    }
    try {
        const res = await fetch('?page=api_variance_tolerance', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                price_tolerance_percent: parseFloat(document.getElementById('tolPricePercent').value),
                price_tolerance_amount: parseFloat(document.getElementById('tolPriceAmount').value),
                quantity_tolerance_percent: parseFloat(document.getElementById('tolQtyPercent').value),
            }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Saved', data.message, 'success');
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    }
}

// ============================================
// BUDGET REQUESTS (Store Manager / HR Head -> Finance Head)
// ============================================

let fhRejectBudgetRequestId = null;

async function loadBudgetRequests() {
    const tbody = document.getElementById('budgetRequestsTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fh_budget_request_list&status=pending');
        const rows = data.requests || [];
        const badge = document.getElementById('budgetRequestsBadge');
        badge.textContent = rows.length;
        badge.style.display = rows.length ? 'inline-block' : 'none';

        tbody.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td>${prEscapeHtml(r.department_name)}</td>
                <td>${prEscapeHtml(r.period_key)}</td>
                <td>${prCurrency(r.requested_amount)}</td>
                <td>${prEscapeHtml(r.reason)}</td>
                <td>${prEscapeHtml(r.requested_by_name)}</td>
                <td>${prStatusBadge(r.status)}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="reviewBudgetRequest(${r.id}, 'approve', this)"><i class="bi bi-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="openRejectBudgetRequest(${r.id})"><i class="bi bi-x"></i> Reject</button>
                </td>
            </tr>
        `).join('') : prEmptyRow(7, 'No pending budget requests.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(7, e.message);
    }
}

async function reviewBudgetRequest(id, action, btn) {
    const unlock = btn ? prLockButton(btn) : () => {};
    try {
        const res = await fetch('?page=api_fh_budget_request_review', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: id, action, rejection_reason: fhRejectBudgetRequestId === id ? document.getElementById('rejectBudgetRequestReason').value.trim() : undefined }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire(action === 'approve' ? 'Approved' : 'Rejected', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('rejectBudgetRequestModal'))?.hide();
        loadBudgetRequests();
        loadOverview(document.getElementById('monthFilter').value);
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

function openRejectBudgetRequest(id) {
    fhRejectBudgetRequestId = id;
    document.getElementById('rejectBudgetRequestReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectBudgetRequestModal')).show();
}

function confirmRejectBudgetRequest() {
    const reason = document.getElementById('rejectBudgetRequestReason').value.trim();
    if (!reason) {
        Swal.fire('Reason required', 'Please provide a rejection reason.', 'warning');
        return;
    }
    reviewBudgetRequest(fhRejectBudgetRequestId, 'reject', document.getElementById('confirmRejectBudgetRequestBtn'));
}
