// ============================================
// STORE MANAGER DASHBOARD
// ============================================

console.log('✅ store_manager/dashboard.js loaded');

let smTrendChart = null;
let smStatusChart = null;

document.addEventListener('DOMContentLoaded', function () {
    loadDashboardData();
});

function loadDashboardData() {
    const container = document.getElementById('dashboardContent');

    fetch('?page=api_store_manager_dashboard')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDashboard(data.data);
            } else {
                container.innerHTML = smErrorState(data.message || 'Failed to load dashboard data');
            }
        })
        .catch(error => {
            console.error('Error loading dashboard:', error);
            container.innerHTML = smErrorState('An error occurred. Please refresh the page.');
        });
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const stats = data.stats || {};
    const recent = data.recent_requisitions || [];
    const insights = data.insights || {};

    container.innerHTML = `
        <!-- Dashboard Canvas: each row below is its own drag-reorderable
             zone (stats / mini-tables+charts). Order is user-customizable
             (see dashboard-layout.js) and persisted per account via
             api_save_store_manager_dashboard_layout / api_get_store_manager_dashboard_layout. -->

        <!-- Summary cards -->
        <div class="sm-stats-grid dash-canvas-row" id="smDashCanvasStats" data-widget-group="stats">
            <div class="dash-widget" data-widget-id="stat_total">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sm-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sm-stat-label">Total Requisitions</div>
                            <div class="sm-stat-number primary">${stats.total_requisitions ?? 0}</div>
                            ${stats.created_this_week ? `<div class="sm-stat-trend up"><i class="bi bi-graph-up-arrow"></i> +${stats.created_this_week} this week</div>` : `<div class="sm-stat-trend muted">No new ones this week</div>`}
                        </div>
                        <div class="sm-stat-icon"><i class="bi bi-clipboard-data text-primary"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_pending">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sm-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sm-stat-label">Pending Supplier</div>
                            <div class="sm-stat-number warning">${stats.pending_supplier ?? 0}</div>
                            ${stats.pending_supplier_this_week ? `<div class="sm-stat-trend up"><i class="bi bi-graph-up-arrow"></i> +${stats.pending_supplier_this_week} this week</div>` : `<div class="sm-stat-trend muted">No new ones this week</div>`}
                        </div>
                        <div class="sm-stat-icon"><i class="bi bi-hourglass-split text-warning"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_finance">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sm-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sm-stat-label">Awaiting Finance</div>
                            <div class="sm-stat-number" style="color:#9a3412;">${stats.awaiting_finance ?? 0}</div>
                            ${stats.awaiting_finance_this_week ? `<div class="sm-stat-trend up"><i class="bi bi-graph-up-arrow"></i> +${stats.awaiting_finance_this_week} this week</div>` : `<div class="sm-stat-trend muted">No new ones this week</div>`}
                        </div>
                        <div class="sm-stat-icon"><i class="bi bi-cash-coin" style="color:#9a3412;"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_lowstock">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sm-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sm-stat-label">Low Stock Items</div>
                            <div class="sm-stat-number ${stats.low_stock_count > 0 ? 'danger' : 'success'}">${stats.low_stock_count ?? 0}</div>
                            <div class="sm-stat-trend ${stats.low_stock_count > 0 ? 'warn' : 'muted'}"><i class="bi bi-box-seam"></i> of ${stats.active_product_count ?? 0} products</div>
                        </div>
                        <div class="sm-stat-icon"><i class="bi bi-exclamation-triangle text-danger"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live preview tables + charts (always visible, no click/hover needed) -->
        <div class="row g-3 mb-4 dash-canvas-row" id="smDashCanvasContent" data-widget-group="content">
            <div class="col-lg-6 dash-widget" data-widget-id="table_mine">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-list text-yellow me-2"></i>Your Requisitions</h6>
                        <a href="?page=store_manager_requisitions&tab=mine" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="sm-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>No.</th><th>Supplier</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody id="smQaMineBody">
                                <tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 dash-widget" data-widget-id="table_lowstock">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-box-seam text-yellow me-2"></i>Low Stock Items</h6>
                        <a href="?page=store_manager_inventory" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="sm-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Product</th><th>Stock</th><th>Reorder At</th></tr></thead>
                            <tbody id="smQaLowStockBody">
                                <tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 dash-widget" data-widget-id="table_finance">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-send text-yellow me-2"></i>Awaiting Finance</h6>
                        <a href="?page=store_manager_requisitions&tab=awaiting-finance" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="sm-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>No.</th><th>Supplier</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody id="smQaFinanceBody">
                                <tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 dash-widget" data-widget-id="table_history">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-yellow me-2"></i>Recently Completed</h6>
                        <a href="?page=store_manager_requisitions&tab=history" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="sm-mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>No.</th><th>Supplier</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody id="smQaHistoryBody">
                                <tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-flex dash-widget" data-widget-id="chart_trend">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 w-100">
                    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up text-yellow me-2"></i>Requisition Trend (Last 14 Days)</h6>
                    <div class="sm-chart-wrap">
                        <canvas id="smTrendChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-flex dash-widget" data-widget-id="chart_status">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 w-100">
                    <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-yellow me-2"></i>Requisitions by Status</h6>
                    <div class="sm-chart-wrap sm-chart-wrap-donut">
                        <canvas id="smStatusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 dash-widget" data-widget-id="list_recent">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-yellow me-2"></i>Recent Requisitions</h6>
                        <a href="?page=store_manager_requisitions&tab=mine" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="sm-mini-table-scroll">
                        ${recent.length > 0 ? recent.map((r, i) => renderRecentRow(r, i + 1)).join('') : smEmptyState('No requisitions yet', 'bi-inbox')}
                    </div>
                </div>
            </div>
            <div class="col-lg-6 dash-widget" data-widget-id="panel_insights">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb-fill text-yellow me-2"></i>Business Insights</h6>
                    ${renderInsights(insights)}
                </div>
            </div>
        </div>
    `;

    document.querySelectorAll('.view-requisition-btn, .sm-recent-row').forEach(el => {
        el.addEventListener('click', function () {
            window.location.href = `?page=store_manager_requisitions&tab=mine&view=${this.dataset.id}`;
        });
    });

    loadQuickPreviewTables();

    renderTrendChart(data.daily_trend || []);
    renderStatusChart(data.status_breakdown || {});

    // The canvas rows above only exist in the DOM from this point on --
    // dashboard-layout.js waits for this event before wiring up drag
    // listeners and restoring any saved widget order.
    document.dispatchEvent(new CustomEvent('sm-dashboard-rendered'));
}

function bindMiniRowClicks(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.querySelectorAll('.sm-mini-row').forEach(row => {
        row.addEventListener('click', function () {
            window.location.href = `?page=store_manager_requisitions&tab=mine&view=${this.dataset.id}`;
        });
    });
}

function renderRecentRow(r, rank) {
    return `
        <div class="sm-recent-row" data-id="${r.id}">
            <div class="sm-recent-rank">${rank}</div>
            <div class="sm-recent-info">
                <div class="sm-recent-name">${escapeHtmlSM(r.requisition_number)}</div>
                <div class="sm-recent-sub">${escapeHtmlSM(r.company_name)} &middot; ${r.item_count ?? 0} item${(r.item_count ?? 0) === 1 ? '' : 's'}</div>
            </div>
            <div class="sm-recent-right">
                <div class="sm-recent-amount">${smCurrency(r.total)}</div>
                ${smStatusBadge(r.status)}
            </div>
        </div>
    `;
}

function renderInsights(insights) {
    const pct = insights.month_change_pct ?? 0;
    const pctClass = pct > 0 ? 'text-success' : (pct < 0 ? 'text-danger' : 'text-muted');
    const pctIcon = pct > 0 ? 'bi-arrow-up-short' : (pct < 0 ? 'bi-arrow-down-short' : 'bi-dash');

    const topSupplier = insights.top_supplier;
    const lowStock = insights.most_urgent_low_stock;

    return `
        <div class="sm-insight-row">
            <div class="sm-insight-label">Requisitions This Month</div>
            <div class="sm-insight-value">
                ${insights.requisitions_this_month ?? 0}
                <span class="sm-insight-delta ${pctClass}"><i class="bi ${pctIcon}"></i>${Math.abs(pct)}%</span>
            </div>
        </div>
        <div class="sm-insight-row">
            <div class="sm-insight-label">Most Active Supplier <small class="text-muted">(30 days)</small></div>
            <div class="sm-insight-value">${topSupplier ? `${escapeHtmlSM(topSupplier.name)} <span class="text-muted small">(${topSupplier.count})</span>` : '—'}</div>
        </div>
        <div class="sm-insight-row">
            <div class="sm-insight-label">Most Urgent Restock</div>
            <div class="sm-insight-value">
                ${lowStock
                    ? `${escapeHtmlSM(lowStock.name)} <span class="text-danger small">(${lowStock.stock_quantity}/${lowStock.reorder_level})</span>`
                    : `<span class="text-success"><i class="bi bi-check-circle-fill"></i> All stocked</span>`
                }
            </div>
        </div>
    `;
}

function renderTrendChart(dailyTrend) {
    const canvas = document.getElementById('smTrendChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (smTrendChart) smTrendChart.destroy();

    const labels = dailyTrend.map(d => {
        const dt = new Date(d.date + 'T00:00:00');
        return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    smTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Created',
                    data: dailyTrend.map(d => d.created),
                    borderColor: '#f2632b',
                    backgroundColor: 'rgba(242, 99, 43, 0.1)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 2
                },
                {
                    label: 'Completed',
                    data: dailyTrend.map(d => d.completed),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true, pointStyle: 'circle' } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
}

function renderStatusChart(breakdown) {
    const canvas = document.getElementById('smStatusChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (smStatusChart) smStatusChart.destroy();

    const entries = [
        { label: 'Pending Supplier', value: breakdown.pending_supplier ?? 0, color: '#eeab1a' },
        { label: 'In Finance Review', value: breakdown.in_finance_review ?? 0, color: '#2563eb' },
        { label: 'Completed', value: breakdown.completed ?? 0, color: '#059669' },
        { label: 'Rejected', value: breakdown.rejected ?? 0, color: '#dc2626' }
    ].filter(e => e.value > 0);

    const total = entries.reduce((sum, e) => sum + e.value, 0);

    if (total === 0) {
        canvas.parentElement.innerHTML = smEmptyState('No requisitions yet', 'bi-pie-chart');
        return;
    }

    smStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: entries.map(e => e.label),
            datasets: [{
                data: entries.map(e => e.value),
                backgroundColor: entries.map(e => e.color),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } }
            }
        }
    });
}

// ============================================
// QUICK PREVIEW TABLES
// ============================================
// Always-visible mini-tables (same pattern as the HR dashboard) showing live
// data for each Quick Action, so nothing needs to be clicked or hovered to
// see it. Each table is fed by the same endpoint its "View All" link goes
// to, so the preview can never drift out of sync with the real page.

function loadQuickPreviewTables() {
    fetchQaJson('?page=api_get_requisitions&scope=mine&limit=5').then(reqs => {
        const body = document.getElementById('smQaMineBody');
        if (body) body.innerHTML = renderRequisitionRows(reqs, 4, 'No requisitions yet');
        bindMiniRowClicks('smQaMineBody');
    });

    fetchQaJson('?page=api_store_manager_inventory&stock_status=low&limit=5&sort_by=stock&sort_dir=asc', 'products').then(products => {
        const body = document.getElementById('smQaLowStockBody');
        if (body) body.innerHTML = renderLowStockRows(products);
    });

    fetchQaJson('?page=api_get_requisitions&scope=all&group=awaiting_finance&limit=5').then(reqs => {
        const body = document.getElementById('smQaFinanceBody');
        if (body) body.innerHTML = renderRequisitionRows(reqs, 4, 'Nothing awaiting finance');
        bindMiniRowClicks('smQaFinanceBody');
    });

    fetchQaJson('?page=api_get_requisitions&scope=all&group=history&limit=5').then(reqs => {
        const body = document.getElementById('smQaHistoryBody');
        if (body) body.innerHTML = renderRequisitionRows(reqs, 4, 'No history yet');
        bindMiniRowClicks('smQaHistoryBody');
    });
}

function fetchQaJson(url, key = 'requisitions') {
    return fetch(url)
        .then(r => r.json())
        .then(data => data.success ? (data.data[key] || []) : null)
        .catch(() => null);
}

function renderRequisitionRows(requisitions, colCount, emptyText) {
    if (requisitions === null) {
        return `<tr><td colspan="${colCount}" class="text-center text-danger py-3">Failed to load</td></tr>`;
    }
    if (requisitions.length === 0) {
        return `<tr><td colspan="${colCount}" class="text-center text-muted py-3">${emptyText}</td></tr>`;
    }
    return requisitions.map(r => `
        <tr class="sm-mini-row" data-id="${r.id}">
            <td>${escapeHtmlSM(r.requisition_number)}</td>
            <td>${escapeHtmlSM(r.company_name)}</td>
            <td>${smCurrency(r.total)}</td>
            <td>${smStatusBadge(r.status)}</td>
        </tr>
    `).join('');
}

function renderLowStockRows(products) {
    if (products === null) {
        return `<tr><td colspan="3" class="text-center text-danger py-3">Failed to load</td></tr>`;
    }
    if (products.length === 0) {
        return `<tr><td colspan="3" class="text-center text-success py-3"><i class="bi bi-check-circle-fill me-1"></i>All stocked</td></tr>`;
    }
    return products.map(p => `
        <tr>
            <td>${escapeHtmlSM(p.name)}</td>
            <td class="text-danger fw-semibold">${p.stock_quantity}</td>
            <td>${p.reorder_level}</td>
        </tr>
    `).join('');
}
