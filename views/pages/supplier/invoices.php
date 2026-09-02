<?php
$title = 'Invoices - Supplier';
$pageTitle = 'My Invoices';
$activePage = 'invoices';
$additional_js = '<script src="/ShelfSense/public/assets/js/supplier/invoices.js?v=20260831061347"></script>';

$content = <<<'EOT'
<ul class="nav nav-pills sp-tabs mb-3" id="invoiceStatusTabs">
    <li class="nav-item">
        <button type="button" class="nav-link active" data-status="">All <span class="badge bg-secondary ms-1" id="countAll">0</span></button>
    </li>
    <li class="nav-item">
        <button type="button" class="nav-link" data-status="pending">Pending <span class="badge bg-secondary ms-1" id="countPending">0</span></button>
    </li>
    <li class="nav-item">
        <button type="button" class="nav-link" data-status="verified">Verified <span class="badge bg-secondary ms-1" id="countVerified">0</span></button>
    </li>
    <li class="nav-item">
        <button type="button" class="nav-link" data-status="paid">Paid <span class="badge bg-secondary ms-1" id="countPaid">0</span></button>
    </li>
</ul>

<div class="row g-2 mb-3">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search invoice # or requisition #...">
        </div>
    </div>
    <div class="col-md-6 text-end">
        <a href="?page=supplier_requisitions&tab=pending" class="btn btn-yellow-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Create Invoice
        </a>
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<div class="modern-card p-3 sm-fill-card">
    <div id="invoiceCardsContainer" class="sp-card-grid">
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading invoices...</p>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-muted small" id="tableInfo">Loading...</span>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                <li class="page-item disabled"><span class="page-link">1</span></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Invoice Detail Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="invoiceDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="invoiceDetailBody">
        <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
