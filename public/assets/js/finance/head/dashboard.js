// ============================================
// FINANCE HEAD - DASHBOARD
// ============================================

console.log('✅ finance/head/dashboard.js loaded');

document.addEventListener('DOMContentLoaded', function() {
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
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${data.message || 'Failed to load dashboard data'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading dashboard:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    An error occurred. Please refresh the page.
                </div>
            `;
        });
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const stats = data.stats;
    const activity = data.recent_activity || [];

    // ✅ Safely convert to numbers
    const pending = parseInt(stats.pending) || 0;
    const approved = parseInt(stats.approved) || 0;
    const rejected = parseInt(stats.rejected) || 0;
    const budgetTotal = parseFloat(stats.budget_total) || 0;
    const budgetUsed = parseFloat(stats.budget_used) || 0;
    const budgetRemaining = parseFloat(stats.budget_remaining) || 0;
    const budgetUsedPercentage = parseFloat(stats.budget_used_percentage) || 0;

    container.innerHTML = `
        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Pending Approvals</div>
                            <div class="stat-number warning">${pending}</div>
                        </div>
                        <div class="stat-icon">⏳</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Approved</div>
                            <div class="stat-number success">${approved}</div>
                        </div>
                        <div class="stat-icon">✅</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Rejected</div>
                            <div class="stat-number danger">${rejected}</div>
                        </div>
                        <div class="stat-icon">❌</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Budget Used</div>
                            <div class="stat-number ${budgetUsedPercentage > 80 ? 'danger' : 'success'}">${budgetUsedPercentage}%</div>
                        </div>
                        <div class="stat-icon">💰</div>
                    </div>
                    <small class="text-muted">₱${budgetUsed.toFixed(2)} / ₱${budgetTotal.toFixed(2)}</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="modern-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=finance_head_payment_requests" class="btn btn-yellow-primary btn-sm">
                    <i class="bi bi-check-circle me-1"></i> Approve Payments
                    ${pending > 0 ? `<span class="badge bg-danger ms-1">${pending}</span>` : ''}
                </a>
                <a href="?page=finance_head_budget" class="btn btn-yellow-outline btn-sm">
                    <i class="bi bi-pie-chart me-1"></i> Manage Budget
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-yellow me-2"></i>Recent Activity</h6>
            ${activity.length > 0 ? `
                ${activity.map(item => `
                    <div class="activity-item">
                        <div>
                            <strong>${item.requisition_number || 'N/A'}</strong>
                            <span class="status-${item.status}">${item.status}</span>
                            <small class="text-muted">— ${item.first_name || ''} ${item.last_name || ''}</small>
                        </div>
                        <small class="text-muted">${item.updated_at ? new Date(item.updated_at).toLocaleString() : 'N/A'}</small>
                    </div>
                `).join('')}
            ` : `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                    No recent activity
                </div>
            `}
        </div>
    `;

    // Update badge
    const badge = document.getElementById('headPendingBadge');
    if (badge) {
        if (pending > 0) {
            badge.textContent = pending;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}