// ============================================
// FINANCE HEAD - DASHBOARD
// ============================================

console.log('✅ finance/head/dashboard.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    loadDashboardData();
});

function loadDashboardData() {
    const container = document.getElementById('dashboardContent');

    fetch('?page=api_finance_head_dashboard_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDashboard(data.data);
            } else {
                container.innerHTML = fnErrorState(data.message || 'Failed to load dashboard data');
            }
        })
        .catch(() => {
            container.innerHTML = fnErrorState();
        });
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const stats = data.stats;
    const activity = data.recent_activity || [];
    const departments = data.budget_departments || [];
    const nearLimit = data.departments_near_limit || [];

    const pending = parseInt(stats.pending) || 0;
    const approvedThisMonth = parseInt(stats.approved_this_month) || 0;
    const rejectedThisMonth = parseInt(stats.rejected_this_month) || 0;
    const overallPct = stats.budget_used_percentage === null ? null : parseFloat(stats.budget_used_percentage);

    container.innerHTML = `
        <!-- Stats -->
        <div class="row g-3 mb-4" id="fnHeadStatsRow">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Pending Payment Requests</div>
                            <div class="stat-number warning">${pending}</div>
                            ${pending > 0 ? '<small class="text-danger">⚠️ Action needed</small>' : ''}
                        </div>
                        <div class="stat-icon">⏳</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Approved This Month</div>
                            <div class="stat-number success">${approvedThisMonth}</div>
                        </div>
                        <div class="stat-icon">✅</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Rejected This Month</div>
                            <div class="stat-number danger">${rejectedThisMonth}</div>
                        </div>
                        <div class="stat-icon">❌</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Overall Budget Used</div>
                            <div class="stat-number ${overallPct !== null && overallPct > 80 ? 'danger' : 'success'}">${overallPct !== null ? overallPct + '%' : '—'}</div>
                        </div>
                        <div class="stat-icon">📊</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=finance_head_payment_requests&tab=pending" class="btn btn-yellow-primary btn-sm">
                    <i class="bi bi-clock-history me-1"></i> Review Pending
                    ${pending > 0 ? `<span class="badge bg-danger ms-1">${pending}</span>` : ''}
                </a>
                <a href="?page=finance_head_budget" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-pie-chart me-1"></i> Manage Budget
                </a>
                <a href="?page=finance_head_payment_requests&tab=all" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-clipboard-data me-1"></i> Approval History
                </a>
                <a href="?page=finance_head_budget" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Budget Report
                </a>
            </div>
        </div>

        ${nearLimit.length > 0 ? `
        <div class="fn-budget-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>${nearLimit.length}</strong> department${nearLimit.length > 1 ? 's are' : ' is'} at or above 80% of its allocated budget:
            ${nearLimit.map(d => `${fnEscapeHtml(fnDeptLabel(d.department))} (${d.used_percentage}%)`).join(', ')}.
        </div>
        ` : ''}

        <!-- Budget Overview -->
        <div class="modern-card p-3 mb-4" id="fnHeadBudgetCard">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart text-yellow me-2"></i>Budget Overview (${fnEscapeHtml(fnCutoffLabel(stats.month_year))})</h6>
            ${departments.length > 0 ? departments.map(d => fnDeptBudgetRow(d)).join('') : fnEmptyState('No department budgets found for this period.')}
        </div>

        <!-- Recent Activity -->
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-yellow me-2"></i>Recent Activity</h6>
            ${activity.length > 0 ? activity.map(item => fnActivityRow(item)).join('') : fnEmptyState('No recent activity')}
        </div>
    `;

    const badge = document.getElementById('headPendingBadge');
    if (badge) {
        if (pending > 0) {
            badge.textContent = pending;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    document.dispatchEvent(new CustomEvent('fn-head-dashboard-rendered'));
}

function fnDeptBudgetRow(d) {
    if (!d.has_allocation) {
        return `
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <strong>${fnEscapeHtml(fnDeptLabel(d.department))}</strong>
                    <span class="text-muted">No budget allocated</span>
                </div>
            </div>
        `;
    }
    const pct = Math.min(100, d.used_percentage || 0);
    const barClass = d.status === 'exceeded' ? 'exceeded' : (d.status === 'near_limit' ? 'near_limit' : '');
    return `
        <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
                <strong class="text-capitalize">${fnEscapeHtml(d.department)}</strong>
                <span>Allocated: <strong>${fnCurrency(d.allocated)}</strong> &nbsp; Used+Reserved: <strong>${fnCurrency(d.used + d.reserved)}</strong> &nbsp; ${d.used_percentage}% Used</span>
            </div>
            <div class="fn-budget-bar-track"><div class="fn-budget-bar-fill ${barClass}" style="width:${pct}%;"></div></div>
        </div>
    `;
}

function fnActivityRow(item) {
    let icon = '⏳';
    let label = 'Awaiting review';
    if (item.status === 'approved') { icon = '✅'; label = 'Approved'; }
    else if (item.status === 'rejected') { icon = '❌'; label = 'Rejected' + (item.rejection_reason ? ' (' + item.rejection_reason + ')' : ''); }

    return `
        <div class="activity-item d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                ${icon} <strong>${fnEscapeHtml(item.requisition_number)}</strong>
                <span class="text-muted">— ${fnEscapeHtml(item.company_name)} (${fnCurrency(item.requisition_total)})</span>
                <div class="small text-muted">${label}${item.approved_first ? ' by ' + fnEscapeHtml(item.approved_first) + ' ' + fnEscapeHtml(item.approved_last) : ''}</div>
            </div>
            <small class="text-muted">${fnFormatDate(item.updated_at, true)}</small>
        </div>
    `;
}
