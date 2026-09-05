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
    const readyToShip = data.ready_to_ship_pos || [];
    const recentInvoices = data.recent_invoices || [];
    const activeProducts = data.active_products || [];
    const recentPos = data.recent_pos || [];
    const maxActivity = Math.max(activity.received, activity.processed, activity.shipped, 1);

    container.innerHTML = `
        <!-- Dashboard Canvas: each row below is its own drag-reorderable
             zone (stats / content cards). Order is user-customizable (see
             dashboard-layout.js) and persisted per account via
             api_save_supplier_dashboard_layout / api_get_supplier_dashboard_layout. -->
        <div class="sp-stats-grid dash-canvas-row" id="spDashCanvasStats" data-widget-group="stats">
            <div class="dash-widget" data-widget-id="stat_pending">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sp-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sp-stat-label">Pending Requisitions</div>
                            <div class="sp-stat-number warning">${stats.pending_requisitions ?? 0}</div>
                        </div>
                        <div class="sp-stat-icon"><i class="bi bi-inbox-fill text-warning"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_invoiced">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sp-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sp-stat-label">Invoiced (Processed)</div>
                            <div class="sp-stat-number primary">${stats.invoiced_requisitions ?? 0}</div>
                        </div>
                        <div class="sp-stat-icon"><i class="bi bi-receipt text-primary"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_ready">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sp-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sp-stat-label">Ready to Ship (Paid)</div>
                            <div class="sp-stat-number success">${stats.ready_to_ship ?? 0}</div>
                        </div>
                        <div class="sp-stat-icon"><i class="bi bi-truck text-success"></i></div>
                    </div>
                </div>
            </div>
            <div class="dash-widget" data-widget-id="stat_revenue">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="sp-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="sp-stat-label">This Month Revenue</div>
                            <div class="sp-stat-number purple">${spCurrency(stats.month_revenue)}</div>
                        </div>
                        <div class="sp-stat-icon purple"><i class="bi bi-graph-up-arrow"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 dash-canvas-row" id="spDashCanvasContent" data-widget-group="content">
            <div class="col-lg-4 dash-widget" data-widget-id="table_ready">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 sp-dash-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-truck text-yellow me-2"></i>Ready to Ship</h6>
                        <a href="?page=supplier_requisitions&tab=paid" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>PO #</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                                ${readyToShip.length > 0 ? readyToShip.map(po => `
                                    <tr>
                                        <td>${escapeHtmlSP(po.po_number)}</td>
                                        <td>${spCurrency(po.total)}</td>
                                        <td>${spStatusBadge(po.status)}</td>
                                    </tr>
                                `).join('') : `<tr><td colspan="3" class="text-center text-muted py-3">Nothing ready to ship right now.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 dash-widget" data-widget-id="table_invoices">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 sp-dash-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-receipt text-yellow me-2"></i>Recent Invoices</h6>
                        <a href="?page=supplier_invoices" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Invoice #</th><th>PO #</th><th>Total</th><th>Match Status</th></tr></thead>
                            <tbody>
                                ${recentInvoices.length > 0 ? recentInvoices.map(inv => `
                                    <tr>
                                        <td>${escapeHtmlSP(inv.invoice_number)}</td>
                                        <td>${escapeHtmlSP(inv.po_number)}</td>
                                        <td>${spCurrency(inv.total)}</td>
                                        <td>${spStatusBadge(inv.match_status)}</td>
                                    </tr>
                                `).join('') : `<tr><td colspan="4" class="text-center text-muted py-3">No invoices submitted yet.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 dash-widget" data-widget-id="table_products">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 sp-dash-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-box-seam text-yellow me-2"></i>Active Products</h6>
                        <a href="?page=supplier_products" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>Product</th><th>Price</th></tr></thead>
                            <tbody>
                                ${activeProducts.length > 0 ? activeProducts.map(p => `
                                    <tr>
                                        <td>${escapeHtmlSP(p.name)}</td>
                                        <td>${spCurrency(p.price)}</td>
                                    </tr>
                                `).join('') : `<tr><td colspan="2" class="text-center text-muted py-3">No active products yet.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 dash-widget" data-widget-id="table_pos">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 sp-dash-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clipboard-check text-yellow me-2"></i>Purchase Orders</h6>
                        <a href="?page=supplier_requisitions" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>PO #</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                                ${recentPos.length > 0 ? recentPos.map(po => `
                                    <tr>
                                        <td>${escapeHtmlSP(po.po_number)}</td>
                                        <td>${spCurrency(po.total)}</td>
                                        <td>${spStatusBadge(po.status)}</td>
                                    </tr>
                                `).join('') : `<tr><td colspan="3" class="text-center text-muted py-3">No purchase orders yet.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 dash-widget" data-widget-id="table_recent">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 sp-dash-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-yellow me-2"></i>Needs Action</h6>
                        <a href="?page=supplier_requisitions&tab=pending" class="btn btn-yellow-outline btn-sm">View All</a>
                    </div>
                    <div class="mini-table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr><th>PO #</th><th>Total</th><th></th></tr></thead>
                            <tbody>
                                ${pending.length > 0 ? pending.map(r => `
                                    <tr>
                                        <td>${escapeHtmlSP(r.requisition_number)}</td>
                                        <td>${spCurrency(r.total)}</td>
                                        <td><button class="btn btn-sm btn-outline-primary view-requisition-btn" data-id="${r.id}"><i class="bi bi-eye"></i></button></td>
                                    </tr>
                                `).join('') : `<tr><td colspan="3" class="text-center text-muted py-3">Nothing needs your attention right now.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 dash-widget" data-widget-id="table_activity">
                <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
                <div class="modern-card p-3 h-100 sp-dash-card dash-card-accent">
                    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up text-yellow me-2"></i>Activity (Last 30 Days)</h6>
                    <div class="sp-dash-card-body">
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
                        <p class="text-muted small mb-0 mt-2">Based on each PO's last-updated time.</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.querySelectorAll('.view-requisition-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            window.location.href = `?page=supplier_requisitions&tab=pending&view=${this.dataset.id}`;
        });
    });

    document.dispatchEvent(new CustomEvent('sp-dashboard-rendered'));
}

