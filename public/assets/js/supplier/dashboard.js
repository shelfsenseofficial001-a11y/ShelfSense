// ============================================
// SUPPLIER DASHBOARD
// ============================================

console.log('✅ supplier/dashboard.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    loadDashboardData();
});

function loadDashboardData() {
    const container = document.getElementById('dashboardContent');

    fetch('?page=api_supplier_dashboard')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderDashboard(data.data);
            } else {
                container.innerHTML = spErrorState(data.message || 'Failed to load dashboard data');
            }
        })
        .catch(() => {
            container.innerHTML = spErrorState('An error occurred. Please refresh the page.');
        });
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const stats = data.stats || {};
    const activity = data.activity_30d || { received: 0, processed: 0, shipped: 0 };
    const pending = data.pending_requisitions || [];
    const maxActivity = Math.max(activity.received, activity.processed, activity.shipped, 1);

    container.innerHTML = `
        <div class="sp-stats-grid">
            <div class="sp-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sp-stat-label">Pending Requisitions</div>
                        <div class="sp-stat-number warning">${stats.pending_requisitions ?? 0}</div>
                    </div>
                    <div class="sp-stat-icon"><i class="bi bi-inbox-fill text-warning"></i></div>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sp-stat-label">Invoiced (Processed)</div>
                        <div class="sp-stat-number primary">${stats.invoiced_requisitions ?? 0}</div>
                    </div>
                    <div class="sp-stat-icon"><i class="bi bi-receipt text-primary"></i></div>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sp-stat-label">Ready to Ship (Paid)</div>
                        <div class="sp-stat-number success">${stats.ready_to_ship ?? 0}</div>
                    </div>
                    <div class="sp-stat-icon"><i class="bi bi-truck text-success"></i></div>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sp-stat-label">This Month Revenue</div>
                        <div class="sp-stat-number purple">${spCurrency(stats.month_revenue)}</div>
                    </div>
                    <div class="sp-stat-icon"><i class="bi bi-graph-up-arrow" style="color:#7c3aed;"></i></div>
                </div>
            </div>
        </div>

        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=supplier_requisitions&tab=pending" class="btn btn-yellow-primary btn-sm">
                    <i class="bi bi-inbox me-1"></i> View Pending
                </a>
                <a href="?page=supplier_requisitions&tab=paid" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-truck me-1"></i> Ship Goods
                </a>
                <a href="?page=supplier_requisitions&tab=all" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-clipboard-check me-1"></i> View All Requisitions
                </a>
                <a href="?page=supplier_invoices" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-receipt me-1"></i> View Invoices
                </a>
                <a href="?page=supplier_products" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-box-seam me-1"></i> Manage Products
                </a>
            </div>
        </div>

        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-graph-up text-yellow me-2"></i>Activity Overview (Last 30 Days)</h6>
            <div class="sp-activity-row">
                <div class="sp-activity-label">Received</div>
                <div class="sp-activity-track"><div class="sp-activity-fill" style="width:${(activity.received / maxActivity) * 100}%;"></div></div>
                <div class="sp-activity-count">${activity.received}</div>
            </div>
            <div class="sp-activity-row">
                <div class="sp-activity-label">Processed</div>
                <div class="sp-activity-track"><div class="sp-activity-fill" style="width:${(activity.processed / maxActivity) * 100}%;background:#2563eb;"></div></div>
                <div class="sp-activity-count">${activity.processed}</div>
            </div>
            <div class="sp-activity-row">
                <div class="sp-activity-label">Shipped</div>
                <div class="sp-activity-track"><div class="sp-activity-fill" style="width:${(activity.shipped / maxActivity) * 100}%;background:#059669;"></div></div>
                <div class="sp-activity-count">${activity.shipped}</div>
            </div>
            <p class="text-muted small mb-0 mt-2">"Processed" and "Shipped" are based on each requisition's last-updated time, since the project does not track a separate timestamp per workflow step.</p>
        </div>

        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-yellow me-2"></i>Recent Requisitions (Need Action)</h6>
            ${pending.length > 0 ? `
                <div class="sp-card-grid">
                    ${pending.map(r => renderPendingCard(r)).join('')}
                </div>
            ` : spEmptyState('Nothing needs your attention right now.', 'bi-check-circle')}
        </div>
    `;

    document.querySelectorAll('.view-requisition-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            window.location.href = `?page=supplier_requisitions&tab=pending&view=${this.dataset.id}`;
        });
    });

    document.dispatchEvent(new CustomEvent('sp-dashboard-rendered'));
}

function renderPendingCard(r) {
    return `
        <div class="sp-req-card">
            <div class="sp-req-header">
                <div>
                    <div class="sp-req-number">${escapeHtmlSP(r.requisition_number)}</div>
                    <div class="sp-req-store">Store: ${escapeHtmlSP(r.first_name)} ${escapeHtmlSP(r.last_name)}</div>
                </div>
                ${spStatusBadge(r.status)}
            </div>
            <div class="sp-req-meta">
                <div>Ordered: <strong>${spFormatDate(r.order_date)}</strong></div>
                <div>Items: <strong>${r.item_count ?? 0}</strong></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="sp-req-total">${spCurrency(r.total)}</div>
                <button class="btn btn-sm btn-outline-primary view-requisition-btn" data-id="${r.id}">
                    <i class="bi bi-eye"></i> View Details
                </button>
            </div>
        </div>
    `;
}
