// ============================================
// FINANCE STAFF - BUDGET VIEW (read-only)
// ============================================

console.log('✅ finance/staff/budget.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    // The period <select> is pre-populated server-side with real cutoff
    // options and already has the current cutoff selected -- just read it.
    loadBudget(document.getElementById('monthFilter').value);

    document.getElementById('monthFilter').addEventListener('change', function () {
        loadBudget(this.value);
    });
    document.getElementById('refreshBtn').addEventListener('click', function () {
        loadBudget(document.getElementById('monthFilter').value);
    });
});

function loadBudget(monthYear) {
    const container = document.getElementById('fn-budget-table');
    container.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;

    fetch(`?page=api_finance_staff_get_budget&month=${encodeURIComponent(monthYear)}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderBudgetTable(data.data.budgets, data.data.month_year);
                document.getElementById('lastUpdated').textContent = `Last updated: ${new Date(data.data.generated_at.replace(' ', 'T')).toLocaleString()}`;
            } else {
                container.innerHTML = fnErrorState(data.message || 'Failed to load budget status');
            }
        })
        .catch(() => { container.innerHTML = fnErrorState(); });
}

function renderBudgetTable(budgets, monthYear) {
    const container = document.getElementById('fn-budget-table');

    if (!budgets || budgets.length === 0) {
        container.innerHTML = fnEmptyState(`No budget allocations or requisitions found for ${fnCutoffLabel(monthYear)}.`);
        return;
    }

    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Allocated</th>
                        <th>Used</th>
                        <th>Reserved</th>
                        <th>Available</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${budgets.map(b => `
                        <tr>
                            <td class="fw-semibold">${fnEscapeHtml(fnDeptLabel(b.department))}</td>
                            <td>${b.has_allocation ? fnCurrency(b.allocated) : '<span class="text-muted">—</span>'}</td>
                            <td>${fnCurrency(b.used)}</td>
                            <td>${fnCurrency(b.reserved)}</td>
                            <td class="fw-semibold">${b.has_allocation ? fnCurrency(b.available) : '<span class="text-muted">—</span>'}</td>
                            <td>${fnBudgetStatusBadge(b.status)} ${b.has_allocation && b.used_percentage !== null ? `<span class="text-muted small">(${b.used_percentage}% committed)</span>` : ''}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0">Budget updates automatically when payment requests are created (reserved), approved (used), or rejected (released).</p>
    `;
}
