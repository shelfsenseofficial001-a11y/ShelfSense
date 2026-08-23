<?php
$title = 'Cashier Dashboard - ShelfSense POS';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/pos/dashboard.js"></script>';
$additional_css = '
<style>
    .icon-box-sm {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
</style>
';

$content = <<<'EOT'
<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Today's Sales</small>
                    <h3 class="mb-0 text-success" id="todaySales">₱0.00</h3>
                </div>
                <div class="icon-box-sm bg-light-yellow rounded">
                    <i class="bi bi-cash-stack text-yellow fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Transactions Today</small>
                    <h3 class="mb-0 text-primary" id="todayTransactions">0</h3>
                </div>
                <div class="icon-box-sm bg-light-yellow rounded">
                    <i class="bi bi-receipt text-yellow fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Top Product Today</small>
                    <h6 class="mb-0 text-truncate" id="topProduct">—</h6>
                    <small class="text-muted" id="topProductQty">0 sold</small>
                </div>
                <div class="icon-box-sm bg-light-yellow rounded">
                    <i class="bi bi-trophy text-yellow fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=pos_checkout" class="btn btn-yellow-primary btn-lg">
                    <i class="bi bi-cart-plus me-2"></i> New Sale
                </a>
                <a href="?page=pos_orders" class="btn btn-yellow-outline btn-lg">
                    <i class="bi bi-clock-history me-2"></i> View Orders
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="row">
    <div class="col-12">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-list-ul text-yellow me-2"></i>Recent Transactions</h6>
            <div id="recentTransactions">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading transactions...</p>
                </div>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/cashier.php';