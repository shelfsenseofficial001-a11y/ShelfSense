<?php
$title = 'Order History - ShelfSense POS';
$pageTitle = 'Order History';
$activePage = 'orders';
$additional_js = '<script src="/ShelfSense/public/assets/js/pos/orders.js?v=20260831250000"></script>';
$additional_js .= '
<script>
window.dashboardTourSteps = [
    {
        target: ".sidebar-nav",
        title: "Your navigation",
        desc: "Everything you need lives here -- Order History, Checkout, and your personal Leaves and Payslip pages."
    },
    {
        target: "#posOrdersStatsRow",
        title: "Quick stats",
        desc: "A snapshot of your orders: totals, completed, voided, and total sales."
    },
    {
        target: "#ordersTableBody",
        title: "Order history",
        desc: "Every order you\'ve processed, with search and filters above -- click any row for full details, a reprint, or to void it."
    },
    {
        target: ".user-edit-btn",
        fallbackTarget: ".user-profile-link",
        title: "You’re all set!",
        desc: "You can turn this tour back on or off anytime -- click here to open your Profile, then look for \"Preferences\". Enjoy exploring the dashboard!"
    }
];
</script>
<script src="/ShelfSense/public/assets/js/shared/dashboard-tour.js?v=20260903100000"></script>';
$additional_css = '
<style>
    .order-row:hover {
        background: var(--light-yellow-subtle);
        cursor: pointer;
    }
    .order-status-completed { color: #059669; }
    .order-status-voided { color: #dc2626; }
</style>
';

$content = <<<'EOT'
<!-- Filters -->
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchOrderInput" class="form-control" placeholder="Search by order #...">
        </div>
    </div>
    <div class="col-md-2">
        <select id="filterStatus" class="form-select searchable-select" data-placeholder="Filter by status...">
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="voided">Voided</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" id="filterDate" class="form-control">
    </div>
    <div class="col-md-2">
        <button class="btn btn-yellow-primary btn-sm w-100" id="applyFiltersBtn">
            <i class="bi bi-funnel"></i> Filter
        </button>
    </div>
    <div class="col-md-2 text-end">
        <button class="btn btn-yellow-outline btn-sm" id="refreshOrdersBtn">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<!-- Stats -->
<div class="row g-2 mb-3" id="posOrdersStatsRow">
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Total Orders</small>
            <h5 class="mb-0" id="statTotal">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Completed</small>
            <h5 class="mb-0 text-success" id="statCompleted">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Voided</small>
            <h5 class="mb-0 text-danger" id="statVoided">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Total Sales</small>
            <h5 class="mb-0" id="statTotalSales">₱0.00</h5>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date & Time</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading orders...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="tableInfo">Loading...</span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                    <li class="page-item disabled"><span class="page-link">1</span></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="orderDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="orderDetailBody">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
    <div class="p-3 border-top d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-primary btn-sm" id="reprintReceiptBtn" style="display:none;">
            <i class="bi bi-printer"></i> Reprint Receipt
        </button>
        <button type="button" class="btn btn-danger btn-sm" id="voidOrderBtn" style="display:none;">
            <i class="bi bi-x-circle"></i> Void Order
        </button>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/cashier.php';