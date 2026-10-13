// ============================================
// FINANCE HEAD - DASHBOARD
// ============================================

console.log('✅ finance/head/dashboard.js loaded');

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

// Rows for the Head's two approval queues. Both are "waiting on you"
// lists, so the empty state reads as reassurance rather than an error.
function fnHeadReqRows(rows) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="4" class="text-center text-muted py-3">Nothing waiting on your approval.</td></tr>';
    }
    return rows.map(r => `
        <tr>
            <td>${fnEscapeHtml(r.requisition_number || '')}</td>
            <td>${fnEscapeHtml(fnDeptLabel(r.department_name))}</td>
            <td>${fnEscapeHtml(r.supplier_name || '--')}</td>
            <td>${fnCurrency(r.subtotal)}</td>
        </tr>
    `).join('');
}

function fnHeadPaymentRows(rows) {
    if (!rows || !rows.length) {
        return '<tr><td colspan="4" class="text-center text-muted py-3">No payment requests waiting on you.</td></tr>';
    }
    return rows.map(r => {
        const requester = [r.first_name, r.last_name].filter(Boolean).join(' ') || '--';
        return `
        <tr>
            <td>${fnEscapeHtml(r.po_number || '--')}</td>
            <td>${fnEscapeHtml(r.supplier_name || '--')}</td>
            <td>${fnEscapeHtml(requester)}</td>
            <td>${fnCurrency(r.amount)}</td>
        </tr>
    `;
    }).join('');
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const stats = data.stats;
    const activity = data.recent_activity || [];
    const departments = data.budget_departments || [];
    const nearLimit = data.departments_near_limit || [];
    const reqsToApprove = data.requisitions_to_approve || [];
    const paymentsToApprove = data.payments_to_approve || [];

    const pending = parseInt(stats.pending) || 0;
    const approvedThisMonth = parseInt(stats.approved_this_month) || 0;
    const rejectedThisMonth = parseInt(stats.rejected_this_month) || 0;
    const overallPct = stats.budget_used_percentage === null ? null : parseFloat(stats.budget_used_percentage);

    container.innerHTML = `
        <!-- Stats -->
        <div class="row g-3 mb-4 dash-canvas-row" id="fnDashCanvasStats" data-widget-group="stats">
            <div class="col-md-3 dash-widget" data-widget-id="stat_pending_pay">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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
            <div class="col-md-3 dash-widget" data-widget-id="stat_approved">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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
            <div class="col-md-3 dash-widget" data-widget-id="stat_rejected">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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
            <div class="col-md-3 dash-widget" data-widget-id="stat_budget_used">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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

        ${nearLimit.length > 0 ? `
        <div class="fn-budget-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>${nearLimit.length}</strong> department${nearLimit.length > 1 ? 's are' : ' is'} at or above 80% of its allocated budget:
            ${nearLimit.map(d => `${fnEscapeHtml(fnDeptLabel(d.department))} (${d.used_percentage}%)`).join(', ')}.
        </div>
        ` : ''}

        <div class="row g-3 mb-4 dash-canvas-row" id="fnDashCanvasContent" data-widget-group="content">
            <div class="col-lg-6 dash-widget" data-widget-id="table_reqs_to_approve">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-check2-square text-yellow me-2"></i>Requisitions to Approve</h6>
                        <a href="?page=finance_head_payment_requests&tab=pending" class="btn btn-yellow-outline btn-sm"><i class="bi bi-arrow-right-short"></i> View More</a>
                    </div>
                    <div class="fn-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>No.</th><th>Department</th><th>Supplier</th><th>Total</th></tr></thead>
                            <tbody>${fnHeadReqRows(reqsToApprove)}</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 dash-widget" data-widget-id="table_payments_to_approve">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-cash-stack text-yellow me-2"></i>Payment Requests to Approve</h6>
                        <a href="?page=finance_head_payment_requests&tab=pending" class="btn btn-yellow-outline btn-sm"><i class="bi bi-arrow-right-short"></i> View More</a>
                    </div>
                    <div class="fn-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>PO</th><th>Supplier</th><th>Requested By</th><th>Amount</th></tr></thead>
                            <tbody>${fnHeadPaymentRows(paymentsToApprove)}</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 dash-widget" data-widget-id="panel_budget_overview">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100" id="fnHeadBudgetCard">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart text-yellow me-2"></i>Budget Overview (${fnEscapeHtml(fnCutoffLabel(stats.month_year))})</h6>
                        <a href="?page=finance_head_budget" class="btn btn-yellow-outline btn-sm flex-shrink-0"><i class="bi bi-arrow-right-short"></i> View More</a>
                    </div>
                    ${departments.length > 0 ? departments.map(d => fnDeptBudgetRow(d)).join('') : fnEmptyState('No department budgets found for this period.')}
                </div>
            </div>

            <div class="col-lg-6 dash-widget" data-widget-id="panel_recent_activity">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 dash-card-accent">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-yellow me-2"></i>Recent Activity</h6>
                        <a href="?page=finance_head_revenue_split" class="btn btn-yellow-outline btn-sm flex-shrink-0"><i class="bi bi-arrow-right-short"></i> Revenue Split</a>
                    </div>
                    ${activity.length > 0 ? activity.map(item => fnActivityRow(item)).join('') : fnEmptyState('No recent activity')}
                </div>
            </div>
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
    if (item.status === 'cancelled') { icon = '❌'; label = 'Rejected' + (item.rejection_reason ? ' (' + item.rejection_reason + ')' : ''); }
    else { icon = '✅'; label = 'Approved'; }

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
