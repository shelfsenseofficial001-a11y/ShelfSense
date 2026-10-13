<?php
$title = 'Procurement History - Store Manager';
$pageTitle = 'Procurement History';
$activePage = 'procurement_history';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20261013000002"></script>'
    . '<script src="/ShelfSense/public/assets/js/procurement/history.js?v=20261014000001"></script>';

$content = <<<'EOT'
<div class="row g-3">
    <div class="col-lg-6">
        <div class="modern-card p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-search text-yellow me-2"></i>Search Procurement History</h6>
            <div class="row g-2 mb-3">
                <div class="col-12">
                    <input type="text" id="phQuery" class="form-control form-control-sm" placeholder="Search by REQ/PO/GR/Invoice number...">
                </div>
                <div class="col-6">
                    <select id="phTypeFilter" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="requisition">Requisitions</option>
                        <option value="po">Purchase Orders</option>
                        <option value="gr">Goods Receipts</option>
                        <option value="invoice">Invoices</option>
                    </select>
                </div>
                <div class="col-3">
                    <input type="date" id="phFromDate" class="form-control form-control-sm" title="From date">
                </div>
                <div class="col-3">
                    <input type="date" id="phToDate" class="form-control form-control-sm" title="To date">
                </div>
                <div class="col-12">
                    <button class="btn btn-yellow-primary btn-sm w-100" id="phSearchBtn"><i class="bi bi-search"></i> Search</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Type</th><th>Number</th><th>Party</th><th>Date</th><th>Status</th><th></th></tr></thead>
                    <tbody id="phResultsBody"><tr><td colspan="6" class="text-center py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="modern-card p-3 h-100">
            <div id="phDetailBody">
                <div class="text-center text-muted py-5">Select a result to view its detail and history.</div>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
