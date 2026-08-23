// ============================================
// STORE MANAGER DASHBOARD
// ============================================

console.log('✅ store_manager/dashboard.js loaded');

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
    const activity = data.activity_30d || { created: 0, sent: 0, completed: 0 };
    const recent = data.recent_requisitions || [];

    const maxActivity = Math.max(activity.created, activity.sent, activity.completed, 1);

    container.innerHTML = `
        <!-- Summary cards -->
        <div class="sm-stats-grid">
            <div class="sm-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sm-stat-label">Total Requisitions</div>
                        <div class="sm-stat-number primary">${stats.total_requisitions ?? 0}</div>
                    </div>
                    <div class="sm-stat-icon"><i class="bi bi-clipboard-data text-primary"></i></div>
                </div>
            </div>
            <div class="sm-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sm-stat-label">Pending Supplier</div>
                        <div class="sm-stat-number warning">${stats.pending_supplier ?? 0}</div>
                    </div>
                    <div class="sm-stat-icon"><i class="bi bi-hourglass-split text-warning"></i></div>
                </div>
            </div>
            <div class="sm-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sm-stat-label">Awaiting Finance</div>
                        <div class="sm-stat-number" style="color:#9a3412;">${stats.awaiting_finance ?? 0}</div>
                    </div>
                    <div class="sm-stat-icon"><i class="bi bi-cash-coin" style="color:#9a3412;"></i></div>
                </div>
            </div>
            <div class="sm-stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="sm-stat-label">Low Stock Items</div>
                        <div class="sm-stat-number ${stats.low_stock_count > 0 ? 'danger' : 'success'}">${stats.low_stock_count ?? 0}</div>
                    </div>
                    <div class="sm-stat-icon"><i class="bi bi-exclamation-triangle text-danger"></i></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=store_manager_requisitions&tab=create" class="btn btn-yellow-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Requisition
                </a>
                <a href="?page=store_manager_requisitions&tab=mine" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-list me-1"></i> View Requisitions
                </a>
                <a href="?page=store_manager_inventory" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-box-seam me-1"></i> Inventory
                </a>
                <a href="?page=store_manager_requisitions&tab=history" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-clock-history me-1"></i> Requisition History
                </a>
                <a href="?page=store_manager_requisitions&tab=awaiting-finance" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-send me-1"></i> Forward to Finance
                </a>
            </div>
        </div>

        <!-- Requisition Activity (Last 30 Days) -->
        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-graph-up text-yellow me-2"></i>Requisition Activity (Last 30 Days)</h6>
            <div class="sm-activity-row">
                <div class="sm-activity-label">Created</div>
                <div class="sm-activity-track"><div class="sm-activity-fill" style="width:${(activity.created / maxActivity) * 100}%;"></div></div>
                <div class="sm-activity-count">${activity.created}</div>
            </div>
            <div class="sm-activity-row">
                <div class="sm-activity-label">Sent</div>
                <div class="sm-activity-track"><div class="sm-activity-fill" style="width:${(activity.sent / maxActivity) * 100}%;background:#2563eb;"></div></div>
                <div class="sm-activity-count">${activity.sent}</div>
            </div>
            <div class="sm-activity-row">
                <div class="sm-activity-label">Completed</div>
                <div class="sm-activity-track"><div class="sm-activity-fill" style="width:${(activity.completed / maxActivity) * 100}%;background:#059669;"></div></div>
                <div class="sm-activity-count">${activity.completed}</div>
            </div>
            <p class="text-muted small mb-0 mt-2">"Sent" and "Completed" are based on each requisition's last-updated time, since the project does not yet track a separate timestamp per workflow step.</p>
        </div>

        <!-- Recent Requisitions -->
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-yellow me-2"></i>Recent Requisitions</h6>
            ${recent.length > 0 ? `
                <div class="sm-requisition-grid">
                    ${recent.map(r => renderRecentCard(r)).join('')}
                </div>
            ` : smEmptyState('No requisitions yet', 'bi-inbox')}
        </div>
    `;

    document.querySelectorAll('.view-requisition-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            window.location.href = `?page=store_manager_requisitions&tab=mine&view=${this.dataset.id}`;
        });
    });
}

function renderRecentCard(r) {
    return `
        <div class="sm-req-card">
            <div class="sm-req-header">
                <div>
                    <div class="sm-req-number">${escapeHtmlSM(r.requisition_number)}</div>
                    <div class="sm-req-supplier">${escapeHtmlSM(r.company_name)}</div>
                </div>
                ${smStatusBadge(r.status)}
            </div>
            <div class="sm-req-meta">
                <div>Order Date: <strong>${smFormatDate(r.order_date)}</strong></div>
                <div>Expected: <strong>${smFormatDate(r.expected_delivery)}</strong></div>
                ${r.actual_delivery_date ? `<div>Delivered: <strong>${smFormatDate(r.actual_delivery_date)}</strong></div>` : ''}
                <div>Items: <strong>${r.item_count ?? 0}</strong></div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="sm-req-total">${smCurrency(r.total)}</div>
                <button class="btn btn-sm btn-outline-primary view-requisition-btn" data-id="${r.id}">
                    <i class="bi bi-eye"></i> View
                </button>
            </div>
        </div>
    `;
}
