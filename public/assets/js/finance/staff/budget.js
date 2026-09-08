// ============================================
// FINANCE STAFF - BUDGET VIEW (read-only, ledger-based)
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    if (window.__INITIAL_DATA__) {
        const data = window.__INITIAL_DATA__;
        renderBudgetTable(data.statuses, data.period);
        document.getElementById('lastUpdated').textContent = `Period: ${data.period ? data.period.label : document.getElementById('monthFilter').value}`;
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        loadBudget(document.getElementById('monthFilter').value);
    }
    document.getElementById('monthFilter').addEventListener('change', function () { loadBudget(this.value); });
    document.getElementById('refreshBtn').addEventListener('click', function () { loadBudget(document.getElementById('monthFilter').value); });
});

async function loadBudget(periodKey) {
    const container = document.getElementById('fn-budget-table');
    container.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    try {
        const data = await prFetchJson(`?page=api_get_budget_overview&period_key=${encodeURIComponent(periodKey)}`);
        renderBudgetTable(data.statuses, data.period);
        document.getElementById('lastUpdated').textContent = `Period: ${data.period ? data.period.label : periodKey}`;
    } catch (e) {
        container.innerHTML = `<div class="text-danger text-center py-4"><i class="bi bi-exclamation-triangle"></i> ${prEscapeHtml(e.message)}</div>`;
    }
}

function renderBudgetTable(statuses) {
    const container = document.getElementById('fn-budget-table');
    if (!statuses || statuses.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No departments found.</div>`;
        return;
    }
    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Department</th><th>Allocated</th><th>Used</th><th>Reserved</th><th>Available</th><th>Status</th></tr></thead>
                <tbody>
                    ${statuses.map(b => `
                        <tr>
                            <td class="fw-semibold">${prEscapeHtml(b.department_name)}</td>
                            <td>${b.allocated > 0 ? prCurrency(b.allocated) : '<span class="text-muted">—</span>'}</td>
                            <td>${prCurrency(b.used)}</td>
                            <td>${prCurrency(b.reserved)}</td>
                            <td class="fw-semibold">${prCurrency(b.available)}</td>
                            <td>${prStatusBadge(b.status)} ${b.used_percentage !== null ? `<span class="text-muted small">(${b.used_percentage}% committed)</span>` : ''}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0">Every allocation, reservation, and payment is a ledger entry — figures here are always live, never a stale snapshot.</p>
    `;
}
