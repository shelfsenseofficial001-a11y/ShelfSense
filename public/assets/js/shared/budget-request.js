// ============================================
// DEPARTMENT BUDGET REQUEST (shared: Store Manager 'store', HR Head 'hr')
// Expects these DOM ids on the host page:
//   bdgPeriodSelect, bdgStatusCard, requestBudgetBtn,
//   bdgRequestModal (bdgRequestAmount, bdgRequestReason, bdgSubmitRequestBtn),
//   bdgMyRequestsBody
// ============================================

let bdgCurrentPeriod = null;

document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('bdgStatusCard')) return; // not on this page
    loadMyBudgetStatus();
    loadMyBudgetRequests();
    document.getElementById('requestBudgetBtn')?.addEventListener('click', openRequestBudgetModal);
    document.getElementById('bdgSubmitRequestBtn')?.addEventListener('click', submitBudgetRequest);
    document.getElementById('bdgPeriodSelect')?.addEventListener('change', function () {
        bdgCurrentPeriod = this.value;
        loadMyBudgetStatus();
    });
});

async function loadMyBudgetStatus() {
    const card = document.getElementById('bdgStatusCard');
    card.innerHTML = '<div class="text-center text-muted py-3">Loading...</div>';
    try {
        const params = new URLSearchParams();
        if (bdgCurrentPeriod) params.set('period_key', bdgCurrentPeriod);
        const data = await prFetchJson(`?page=api_budget_request_my_department_status&${params}`);
        bdgCurrentPeriod = data.period_key;

        const select = document.getElementById('bdgPeriodSelect');
        if (select && !select.dataset.populated) {
            select.innerHTML = data.recent_periods.map(p => `<option value="${p.key}" ${p.key === bdgCurrentPeriod ? 'selected' : ''}>${prEscapeHtml(p.label)}</option>`).join('');
            select.dataset.populated = '1';
        }

        const s = data.status;
        if (!s) {
            card.innerHTML = '<div class="alert alert-info mb-0">No budget has been allocated for your department yet.</div>';
            return;
        }
        card.innerHTML = `
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3"><div class="text-muted small">Allocated</div><div class="fw-bold fs-5">${prCurrency(s.allocated)}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Reserved</div><div class="fw-bold fs-5">${prCurrency(s.reserved)}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Used</div><div class="fw-bold fs-5">${prCurrency(s.used)}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Available</div><div class="fw-bold fs-5 ${s.available < 0 ? 'text-danger' : 'text-success'}">${prCurrency(s.available)}</div></div>
            </div>
        `;
    } catch (e) {
        card.innerHTML = `<div class="alert alert-danger mb-0">${prEscapeHtml(e.message)}</div>`;
    }
}

function openRequestBudgetModal() {
    document.getElementById('bdgRequestAmount').value = '';
    document.getElementById('bdgRequestReason').value = '';
    new bootstrap.Modal(document.getElementById('bdgRequestModal')).show();
}

async function submitBudgetRequest() {
    const amount = parseFloat(document.getElementById('bdgRequestAmount').value);
    const reason = document.getElementById('bdgRequestReason').value.trim();
    if (isNaN(amount) || amount < 0) {
        Swal.fire('Check the amount', 'Enter a valid, non-negative amount.', 'warning');
        return;
    }
    if (!reason) {
        Swal.fire('Reason required', 'Explain why this amount is needed.', 'warning');
        return;
    }
    const unlock = prLockButton(document.getElementById('bdgSubmitRequestBtn'));
    try {
        const res = await fetch('?page=api_budget_request_create', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ period_key: bdgCurrentPeriod, amount, reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Submitted', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('bdgRequestModal'))?.hide();
        loadMyBudgetRequests();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function loadMyBudgetRequests() {
    const tbody = document.getElementById('bdgMyRequestsBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_budget_request_list_mine');
        const rows = data.requests || [];
        tbody.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td>${prEscapeHtml(r.period_key)}</td>
                <td>${prCurrency(r.requested_amount)}</td>
                <td>${prEscapeHtml(r.reason)}</td>
                <td>${prStatusBadge(r.status)}</td>
                <td class="small text-muted">${r.status === 'rejected' ? prEscapeHtml(r.rejection_reason || '') : (r.reviewed_by_name ? 'by ' + prEscapeHtml(r.reviewed_by_name) : '')}</td>
            </tr>
        `).join('') : prEmptyRow(5, 'No budget requests yet.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}
