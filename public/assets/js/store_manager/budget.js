// ============================================
// STORE MANAGER — REGISTER BUDGET (one card per register)
// ============================================

console.log('✅ store_manager/budget.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    loadRegisterStatus();
});

function loadRegisterStatus() {
    const container = document.getElementById('smBudgetContent');

    fetch('?page=api_store_manager_get_register_status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderBudgetPage(data.data.registers || []);
            } else {
                container.innerHTML = smErrorState(data.message || 'Failed to load register status');
            }
        })
        .catch(error => {
            console.error('Error loading register status:', error);
            container.innerHTML = smErrorState('An error occurred. Please refresh the page.');
        });
}

function renderBudgetPage(entries) {
    const container = document.getElementById('smBudgetContent');

    if (entries.length === 0) {
        container.innerHTML = smEmptyState('No registers yet. Ask the Owner to provision one for you.', 'bi-cash-stack');
        return;
    }

    container.innerHTML = entries.map(entry => renderRegisterCard(entry.register, entry.active_allocation, entry.live_sales)).join('');

    entries.forEach(entry => {
        const registerId = entry.register.id;
        if (!entry.active_allocation) {
            const form = document.getElementById('smAllocateForm' + registerId);
            if (form) form.addEventListener('submit', handleAllocateSubmit);
        }
        loadAllocationHistory(registerId);
    });
}

function renderRegisterCard(register, allocation, liveSales) {
    return `
        <div class="modern-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-cash-stack text-yellow me-2"></i>${escapeHtmlSM(register.name)}${register.pos_id ? ` <span class="text-muted small fw-normal">(${escapeHtmlSM(register.pos_id)})</span>` : ''}</h6>
                <span class="badge ${register.status === 'open' ? 'bg-success' : 'bg-secondary'}">${register.status === 'open' ? 'Open' : 'Closed'}</span>
            </div>
            <div class="row g-3">
                <div class="col-lg-6">
                    ${allocation ? renderActiveAllocation(allocation, liveSales) : renderNoAllocation()}
                </div>
                <div class="col-lg-6">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Allocate Budget</h6>
                    ${allocation ? `
                        <div class="text-muted small py-4 text-center">
                            <i class="bi bi-lock-fill fs-4 d-block mb-2"></i>
                            This register already has an active budget.<br>The cashier must cash out before a new budget can be allocated.
                        </div>
                    ` : renderAllocateForm(register.id)}
                </div>
            </div>
            <hr>
            <h6 class="fw-bold small text-uppercase text-muted mb-2"><i class="bi bi-clock-history me-1"></i>Allocation History</h6>
            <div class="sm-mini-table-scroll" style="max-height: 260px;">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Closed By</th><th>Initial Budget</th><th>Cash Sales</th><th>Online Sales</th><th>Total Pulled</th><th>Opened</th><th>Cashed Out</th></tr>
                    </thead>
                    <tbody id="smHistoryBody${register.id}">
                        <tr><td colspan="7" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderActiveAllocation(allocation, liveSales) {
    const cashSales = liveSales ? liveSales.cash_sales : 0;
    const onlineSales = liveSales ? liveSales.online_sales : 0;
    const expectedPull = (parseFloat(allocation.initial_budget) + cashSales).toFixed(2);

    return `
        <div class="sm-insight-row">
            <div class="sm-insight-label">Initial Budget</div>
            <div class="sm-insight-value">${smCurrency(allocation.initial_budget)}</div>
        </div>
        <div class="sm-insight-row">
            <div class="sm-insight-label">Cash Sales (live)</div>
            <div class="sm-insight-value">${smCurrency(cashSales)}</div>
        </div>
        <div class="sm-insight-row">
            <div class="sm-insight-label">Online Sales (live) <small class="text-muted">card / gcash / paymaya</small></div>
            <div class="sm-insight-value">${smCurrency(onlineSales)}</div>
        </div>
        <div class="sm-insight-row">
            <div class="sm-insight-label">Expected Cash Pull</div>
            <div class="sm-insight-value text-success">${smCurrency(expectedPull)}</div>
        </div>
        <div class="sm-insight-row">
            <div class="sm-insight-label">Opened</div>
            <div class="sm-insight-value">${smFormatDate(allocation.opened_at)}</div>
        </div>
    `;
}

function renderNoAllocation() {
    return smEmptyState('No active budget on this register', 'bi-cash');
}

function renderAllocateForm(registerId) {
    return `
        <form id="smAllocateForm${registerId}" data-register-id="${registerId}">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Initial Budget (₱)</label>
                <input type="number" class="form-control form-control-sm sm-initial-budget" min="1" step="0.01" placeholder="e.g. 3000" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Notes (optional)</label>
                <textarea class="form-control form-control-sm sm-allocate-notes" rows="2" maxlength="255"></textarea>
            </div>
            <button type="submit" class="btn btn-yellow-primary btn-sm w-100 sm-allocate-submit-btn">
                <i class="bi bi-plus-circle me-1"></i> Allocate Budget
            </button>
        </form>
    `;
}

function handleAllocateSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const registerId = form.dataset.registerId;
    const initialBudget = form.querySelector('.sm-initial-budget').value;
    const notes = form.querySelector('.sm-allocate-notes').value;
    const btn = form.querySelector('.sm-allocate-submit-btn');

    if (!initialBudget) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Allocating...';

    fetch('?page=api_store_manager_allocate_register_budget', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ register_id: registerId, initial_budget: initialBudget, notes: notes })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: 'Budget Allocated', timer: 1500, showConfirmButton: false });
                }
                loadRegisterStatus();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Allocate Budget';
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Could not allocate budget' });
                } else {
                    alert(data.message || 'Could not allocate budget');
                }
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Allocate Budget';
        });
}

function loadAllocationHistory(registerId) {
    fetch('?page=api_store_manager_get_register_allocation_history&register_id=' + encodeURIComponent(registerId) + '&limit=20')
        .then(r => r.json())
        .then(data => {
            const body = document.getElementById('smHistoryBody' + registerId);
            if (!body) return;
            if (!data.success || (data.data.history || []).length === 0) {
                body.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">No allocation history yet</td></tr>`;
                return;
            }
            body.innerHTML = data.data.history.map(h => `
                <tr>
                    <td>${h.first_name ? `${escapeHtmlSM(h.first_name)} ${escapeHtmlSM(h.last_name)}` : '—'}</td>
                    <td>${smCurrency(h.initial_budget)}</td>
                    <td>${h.cash_sales !== null ? smCurrency(h.cash_sales) : '—'}</td>
                    <td>${h.online_sales !== null ? smCurrency(h.online_sales) : '—'}</td>
                    <td>${h.total_pulled !== null ? smCurrency(h.total_pulled) : '—'}</td>
                    <td>${smFormatDate(h.opened_at)}</td>
                    <td>${h.cashed_out_at ? smFormatDate(h.cashed_out_at) : `<span class="badge bg-success">Active</span>`}</td>
                </tr>
            `).join('');
        })
        .catch(() => {
            const body = document.getElementById('smHistoryBody' + registerId);
            if (body) body.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">Failed to load history</td></tr>`;
        });
}
