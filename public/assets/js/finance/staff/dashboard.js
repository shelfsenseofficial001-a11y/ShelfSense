// ============================================
// FINANCE STAFF DASHBOARD
// ============================================

console.log('✅ finance/staff/dashboard.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    loadDashboardData();
});

function loadDashboardData() {
    const container = document.getElementById('dashboardContent');
    fetch('?page=api_finance_staff_dashboard_stats')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderDashboard(data.data);
            } else {
                container.innerHTML = fnErrorState(data.message || 'Failed to load dashboard data');
            }
        })
        .catch(() => { container.innerHTML = fnErrorState('An error occurred. Please refresh the page.'); });
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const s = data.stats || {};
    const activity = data.recent_activity || [];

    const budgetStatus = {
        department: s.budget_department,
        month_year: s.budget_month_year,
        has_allocation: s.budget_has_allocation,
        allocated: s.budget_allocated,
        used: s.budget_used,
        reserved: s.budget_reserved,
        available: s.budget_available,
        requested: 0,
        exceeded: false,
        shortfall: 0,
        status: s.budget_used_percentage >= 100 ? 'exceeded' : (s.budget_used_percentage >= 90 ? 'near_limit' : 'within_budget'),
        used_percentage: s.budget_used_percentage
    };

    container.innerHTML = `
        <div class="fn-stats-grid">
            <div class="fn-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><div class="fn-stat-label">Pending Requisitions (To Review)</div><div class="fn-stat-number warning">${s.pending_requisitions ?? 0}</div></div>
                    <div class="fn-stat-icon"><i class="bi bi-inbox-fill text-warning"></i></div>
                </div>
            </div>
            <div class="fn-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><div class="fn-stat-label">Pending Payment Requests</div><div class="fn-stat-number primary">${s.pending_payment_requests ?? 0}</div></div>
                    <div class="fn-stat-icon"><i class="bi bi-hourglass-split text-primary"></i></div>
                </div>
            </div>
            <div class="fn-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><div class="fn-stat-label">Budget Available (Store, this month)</div><div class="fn-stat-number success">${fnCurrency(s.budget_available)}</div></div>
                    <div class="fn-stat-icon"><i class="bi bi-graph-up text-success"></i></div>
                </div>
            </div>
            <div class="fn-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div><div class="fn-stat-label">Budget Exceeded</div><div class="fn-stat-number ${s.budget_exceeded_count > 0 ? 'danger' : 'success'}">${s.budget_exceeded_count ?? 0}</div></div>
                    <div class="fn-stat-icon"><i class="bi bi-exclamation-triangle text-danger"></i></div>
                </div>
            </div>
        </div>

        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=finance_staff_requisitions&tab=to_review" class="btn btn-yellow-primary btn-sm"><i class="bi bi-inbox me-1"></i> Review Pending</a>
                <a href="?page=finance_staff_requisitions&tab=budget_exceeded" class="btn btn-yellow-outline btn-sm"><i class="bi bi-exclamation-triangle me-1"></i> Budget Exceeded</a>
                <a href="?page=finance_staff_payment_requests" class="btn btn-yellow-outline btn-sm"><i class="bi bi-cash me-1"></i> My Payment Requests</a>
                <a href="?page=finance_staff_budget" class="btn btn-yellow-outline btn-sm"><i class="bi bi-pie-chart me-1"></i> View Budget</a>
            </div>
        </div>

        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart text-yellow me-2"></i>Budget Status — ${fnEscapeHtml(s.budget_department)} (${fnEscapeHtml(s.budget_month_year)})</h6>
            ${fnBudgetBox(budgetStatus)}
        </div>

        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-yellow me-2"></i>Recent Activity</h6>
            ${activity.length > 0 ? `
                <div class="list-group list-group-flush">
                    ${activity.map(a => `
                        <div class="list-group-item bg-transparent border-bottom px-0 py-2">
                            <div>${fnEscapeHtml(a.message)}</div>
                            <small class="text-muted">${fnFormatDate(a.created_at, true)}</small>
                        </div>
                    `).join('')}
                </div>
            ` : fnEmptyState('No recent activity yet.', 'bi-inbox')}
        </div>
    `;
}
