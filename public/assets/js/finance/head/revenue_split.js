// ============================================
// FINANCE HEAD — REVENUE SPLIT
// ============================================

console.log('✅ finance/head/revenue_split.js loaded');

let rsRules = [];
let rsSelectedPeriod = null; // { year, month, half, start_date, end_date, label }
let rsCurrentPreview = null; // the computed split object

document.addEventListener('DOMContentLoaded', function () {
    loadRules();
    loadPeriods();
    loadHistory();

    document.getElementById('rsSaveRulesBtn').addEventListener('click', saveRules);
    document.getElementById('rsLoadPeriodsBtn').addEventListener('click', loadPeriods);
});

// ------------------------------------------------------------------
// Rules — the department list is fixed (HR, Store, Finance, General), not
// user-extensible: General Budget is always the remainder fallback, so only
// the other three departments' percentages are ever editable.
// ------------------------------------------------------------------

const RS_EDITABLE_DEPARTMENTS = ['hr', 'store', 'finance'];

function loadRules() {
    fetch('?page=api_finance_get_revenue_split_rules')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                rsRules = data.data.rules || [];
                renderRules();
            } else {
                document.getElementById('rsRulesTable').innerHTML = fnErrorState(data.message);
            }
        })
        .catch(() => {
            document.getElementById('rsRulesTable').innerHTML = fnErrorState('Failed to load rules');
        });
}

function renderRules() {
    const container = document.getElementById('rsRulesTable');
    const byDept = {};
    rsRules.forEach(r => { byDept[r.department] = r; });

    container.innerHTML = `
        <table class="table table-sm mb-0">
            <thead><tr><th>Department</th><th>%</th></tr></thead>
            <tbody id="rsRulesBody"></tbody>
        </table>
    `;
    const body = document.getElementById('rsRulesBody');
    RS_EDITABLE_DEPARTMENTS.forEach(dept => {
        const rule = byDept[dept] || { department: dept, percentage: 0 };
        body.appendChild(buildRuleRow(rule));
    });

    const generalRow = document.createElement('tr');
    generalRow.innerHTML = `
        <td>${fnEscapeHtml(fnDeptLabel('general'))}</td>
        <td class="text-muted small">Remainder (auto)</td>
    `;
    body.appendChild(generalRow);
}

function buildRuleRow(rule) {
    const tr = document.createElement('tr');
    tr.dataset.department = rule.department;
    tr.innerHTML = `
        <td>${fnEscapeHtml(fnDeptLabel(rule.department))}</td>
        <td style="width:130px;">
            <div class="input-group input-group-sm">
                <input type="number" class="form-control rs-pct-input" value="${rule.percentage ?? 0}" min="0" max="100" step="0.01">
                <span class="input-group-text">%</span>
            </div>
        </td>
    `;
    return tr;
}

function saveRules() {
    const rows = document.querySelectorAll('#rsRulesBody tr[data-department]');
    const rules = [];
    rows.forEach(row => {
        const department = row.dataset.department;
        const percentage = parseFloat(row.querySelector('.rs-pct-input').value) || 0;
        rules.push({ department, percentage, is_remainder: false });
    });
    // General Budget is always the fixed fallback -- never sent as editable.
    rules.push({ department: 'general', percentage: 0, is_remainder: true });

    const msgEl = document.getElementById('rsRulesMessage');
    const btn = document.getElementById('rsSaveRulesBtn');
    btn.disabled = true;

    fetch('?page=api_finance_set_revenue_split_rules', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rules })
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                rsRules = data.data.rules || [];
                renderRules();
                msgEl.innerHTML = `<div class="text-success small"><i class="bi bi-check-circle-fill"></i> Rules saved</div>`;
            } else {
                msgEl.innerHTML = `<div class="text-danger small"><i class="bi bi-exclamation-triangle-fill"></i> ${fnEscapeHtml(data.message)}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            msgEl.innerHTML = `<div class="text-danger small">Failed to save rules</div>`;
        });
}

// ------------------------------------------------------------------
// Periods / compute
// ------------------------------------------------------------------

function loadPeriods() {
    const picker = document.getElementById('rsMonthPicker').value; // YYYY-MM
    if (!picker) return;
    const [year, month] = picker.split('-').map(Number);

    const container = document.getElementById('rsPeriodsList');
    container.innerHTML = `<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>`;

    fetch(`?page=api_finance_get_revenue_periods&year=${year}&month=${month}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = fnErrorState(data.message);
                return;
            }
            renderPeriods(data.data.halves, year, month);
        })
        .catch(() => {
            container.innerHTML = fnErrorState('Failed to load periods');
        });
}

function renderPeriods(halves, year, month) {
    const container = document.getElementById('rsPeriodsList');
    container.innerHTML = halves.map(h => `
        <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2">
            <div>
                <div class="fw-semibold">${fnEscapeHtml(h.label)}</div>
                <div class="small text-muted">
                    ${h.applied ? `<span class="text-success"><i class="bi bi-check-circle-fill"></i> Applied ${fnCurrency(h.applied.total_revenue)} revenue</span>` : (h.draft ? `<span class="text-warning"><i class="bi bi-pencil-square"></i> Draft computed (${fnCurrency(h.draft.total_revenue)})</span>` : 'Not computed yet')}
                </div>
            </div>
            <button class="btn btn-yellow-outline btn-sm rs-compute-btn" data-year="${year}" data-month="${month}" data-half="${h.half}">
                <i class="bi bi-calculator"></i> Compute
            </button>
        </div>
    `).join('');

    container.querySelectorAll('.rs-compute-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            computeSplit(parseInt(this.dataset.year), parseInt(this.dataset.month), parseInt(this.dataset.half));
        });
    });
}

function computeSplit(year, month, half) {
    fetch('?page=api_finance_compute_revenue_split', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ year, month, half })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                rsCurrentPreview = data.data.split;
                renderPreview(rsCurrentPreview);
                loadPeriods();
            } else {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message });
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(() => {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Failed', text: 'Could not compute split' });
        });
}

function renderPreview(split) {
    const card = document.getElementById('rsPreviewCard');
    const content = document.getElementById('rsPreviewContent');
    card.style.display = '';

    const isApplied = split.status === 'applied';

    content.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="fw-semibold">${fnEscapeHtml(split.period_label)}</div>
                <div class="small text-muted">Total Revenue: <strong>${fnCurrency(split.total_revenue)}</strong></div>
            </div>
            <button class="btn btn-yellow-primary btn-sm" id="rsApplyBtn" ${isApplied ? 'disabled' : ''}>
                <i class="bi bi-${isApplied ? 'check-circle-fill' : 'send-check'}"></i> ${isApplied ? 'Already Applied' : 'Apply to Budgets'}
            </button>
        </div>
        <table class="table table-sm mb-0">
            <thead><tr><th>Department</th><th>%</th><th>Amount</th></tr></thead>
            <tbody>
                ${(split.shares || []).map(s => `
                    <tr>
                        <td>${fnEscapeHtml(fnDeptLabel(s.department))}</td>
                        <td>${s.percentage !== null ? s.percentage + '%' : '<span class="text-muted">remainder</span>'}</td>
                        <td>${fnCurrency(s.amount)}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;

    if (!isApplied) {
        document.getElementById('rsApplyBtn').addEventListener('click', () => applySplit(split.id));
    }
}

function applySplit(splitId) {
    const confirmAndApply = () => {
        const btn = document.getElementById('rsApplyBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Applying...';

        fetch('?page=api_finance_apply_revenue_split', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ split_id: splitId })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    rsCurrentPreview = data.data.split;
                    renderPreview(rsCurrentPreview);
                    loadPeriods();
                    loadHistory();
                    if (window.Swal) {
                        Swal.fire({ icon: 'success', title: 'Applied', text: 'Department budgets updated.', timer: 2000, showConfirmButton: false });
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-send-check"></i> Apply to Budgets';
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Failed', text: data.message });
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-check"></i> Apply to Budgets';
            });
    };

    if (window.Swal) {
        Swal.fire({
            icon: 'question',
            title: 'Apply this split?',
            text: 'This will add each department\'s share to that month\'s allocated budget.',
            showCancelButton: true,
            confirmButtonText: 'Yes, apply',
            confirmButtonColor: '#eeab1a'
        }).then(result => {
            if (result.isConfirmed) confirmAndApply();
        });
    } else if (confirm('Apply this split to department budgets?')) {
        confirmAndApply();
    }
}

// ------------------------------------------------------------------
// History
// ------------------------------------------------------------------

function loadHistory() {
    fetch('?page=api_finance_get_revenue_split_history&limit=20')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('rsHistoryTable');
            if (!data.success) {
                container.innerHTML = fnErrorState(data.message);
                return;
            }
            renderHistory(data.data.history || []);
        })
        .catch(() => {
            document.getElementById('rsHistoryTable').innerHTML = fnErrorState('Failed to load history');
        });
}

function renderHistory(history) {
    const container = document.getElementById('rsHistoryTable');
    if (history.length === 0) {
        container.innerHTML = fnEmptyState('No revenue splits computed yet', 'bi-clock-history');
        return;
    }

    container.innerHTML = `
        <table class="table table-sm mb-0">
            <thead><tr><th>Period</th><th>Revenue</th><th>Shares</th><th>Status</th><th>Computed</th></tr></thead>
            <tbody>
                ${history.map(h => `
                    <tr>
                        <td>${fnEscapeHtml(h.period_label)}</td>
                        <td>${fnCurrency(h.total_revenue)}</td>
                        <td class="small">${(h.shares || []).map(s => `${fnEscapeHtml(fnDeptLabel(s.department))}: ${fnCurrency(s.amount)}`).join(', ')}</td>
                        <td><span class="fn-status-badge status-${h.status === 'applied' ? 'approved' : 'pending'}">${h.status === 'applied' ? 'Applied' : 'Draft'}</span></td>
                        <td class="small text-muted">${fnFormatDate(h.computed_at)}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}
