// ============================================
// FINANCE STAFF DASHBOARD
// ============================================

console.log('✅ finance/staff/dashboard.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    if (window.__INITIAL_DATA__) {
        renderDashboard(window.__INITIAL_DATA__);
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        loadDashboardData();
    }
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

// Rows for the two requisition preview tables. Flags the budget column
// red when the requisition would push the department over its allocation,
// which is the whole reason Finance Staff looks at this queue.
function fnReqRows(rows, emptyMessage) {
    if (!rows || !rows.length) {
        return `<tr><td colspan="4" class="text-center text-muted py-3">${fnEscapeHtml(emptyMessage)}</td></tr>`;
    }
    return rows.map(r => `
        <tr>
            <td>${fnEscapeHtml(r.requisition_number || '')}</td>
            <td>${fnEscapeHtml(fnDeptLabel(r.department))}</td>
            <td>${fnCurrency(r.subtotal)}</td>
            <td class="${r.exceeded ? 'text-danger fw-semibold' : 'text-success'}">${r.exceeded ? 'Over' : fnCurrency(r.available)}</td>
        </tr>
    `).join('');
}

function fnPaymentRows(rows) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="4" class="text-center text-muted py-3">You have not raised any payment requests yet.</td></tr>';
    }
    const badge = { pending: 'warning', approved: 'success', rejected: 'danger' };
    return rows.map(r => `
        <tr>
            <td>${fnEscapeHtml(r.po_number || '--')}</td>
            <td>${fnEscapeHtml(r.supplier_name || '--')}</td>
            <td>${fnCurrency(r.amount)}</td>
            <td><span class="badge bg-${badge[r.status] || 'secondary'}">${fnEscapeHtml(r.status)}</span></td>
        </tr>
    `).join('');
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const s = data.stats || {};
    const activity = data.recent_activity || [];
    const toReview = data.to_review || [];
    const exceeded = data.budget_exceeded || [];
    const myPayments = data.my_payment_requests || [];

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
        <div class="fn-stats-grid dash-canvas-row" id="fnDashCanvasStats" data-widget-group="stats">
            <div class="dash-widget" data-widget-id="stat_pending_req">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="fn-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><div class="fn-stat-label">Pending Requisitions (To Review)</div><div class="fn-stat-number warning">${s.pending_requisitions ?? 0}</div></div>
                        <div class="fn-stat-icon"><i class="bi bi-inbox-fill text-warning"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_pending_pay">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="fn-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><div class="fn-stat-label">Pending Payment Requests</div><div class="fn-stat-number primary">${s.pending_payment_requests ?? 0}</div></div>
                        <div class="fn-stat-icon"><i class="bi bi-hourglass-split text-primary"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_budget_avail">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="fn-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><div class="fn-stat-label">Budget Available (Store, this month)</div><div class="fn-stat-number success">${fnCurrency(s.budget_available)}</div></div>
                        <div class="fn-stat-icon"><i class="bi bi-graph-up text-success"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_budget_exceeded">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="fn-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div><div class="fn-stat-label">Budget Exceeded</div><div class="fn-stat-number ${s.budget_exceeded_count > 0 ? 'danger' : 'success'}">${s.budget_exceeded_count ?? 0}</div></div>
                        <div class="fn-stat-icon"><i class="bi bi-exclamation-triangle text-danger"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 dash-canvas-row" id="fnDashCanvasContent" data-widget-group="content">
            <div class="col-lg-6 dash-widget" data-widget-id="table_to_review">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-inbox text-yellow me-2"></i>Requisitions to Review</h6>
                        <a href="?page=finance_staff_requisitions&tab=to_review" class="btn btn-yellow-outline btn-sm"><i class="bi bi-arrow-right-short"></i> View More</a>
                    </div>
                    <div class="fn-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>No.</th><th>Department</th><th>Total</th><th>Budget</th></tr></thead>
                            <tbody>${fnReqRows(toReview, 'No requisitions waiting on you.')}</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 dash-widget" data-widget-id="table_budget_exceeded">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-yellow me-2"></i>Budget Exceeded</h6>
                        <a href="?page=finance_staff_requisitions&tab=budget_exceeded" class="btn btn-yellow-outline btn-sm"><i class="bi bi-arrow-right-short"></i> View More</a>
                    </div>
                    <div class="fn-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>No.</th><th>Department</th><th>Total</th><th>Budget</th></tr></thead>
                            <tbody>${fnReqRows(exceeded, 'Nothing is over budget right now.')}</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 dash-widget" data-widget-id="table_my_payments">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-cash text-yellow me-2"></i>My Payment Requests</h6>
                        <a href="?page=finance_staff_payment_requests" class="btn btn-yellow-outline btn-sm"><i class="bi bi-arrow-right-short"></i> View More</a>
                    </div>
                    <div class="fn-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>PO</th><th>Supplier</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>${fnPaymentRows(myPayments)}</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 dash-widget" data-widget-id="panel_budget_status">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart text-yellow me-2"></i>Budget Status — ${fnEscapeHtml(fnDeptLabel(s.budget_department))} (${fnEscapeHtml(fnCutoffLabel(s.budget_month_year))})</h6>
                        <a href="?page=finance_staff_budget" class="btn btn-yellow-outline btn-sm flex-shrink-0"><i class="bi bi-arrow-right-short"></i> View More</a>
                    </div>
                    ${fnBudgetBox(budgetStatus)}
                </div>
            </div>

            <div class="col-lg-6 dash-widget" data-widget-id="panel_recent_activity">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 dash-card-accent">
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
            </div>
        </div>
    `;

    document.dispatchEvent(new CustomEvent('fn-staff-dashboard-rendered'));
}
