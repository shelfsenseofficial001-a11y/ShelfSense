<?php
$title = 'My Payment Requests - Finance Staff';
$pageTitle = 'My Payment Requests';
$activePage = 'staff_payment_requests';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/staff/payment_requests.js?v=20260831061347"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs fn-tabs mb-3" id="prTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-status="pending" type="button">Pending <span class="badge bg-secondary ms-1" id="countPending">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-status="approved" type="button">Approved <span class="badge bg-secondary ms-1" id="countApproved">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-status="rejected" type="button">Rejected <span class="badge bg-secondary ms-1" id="countRejected">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-status="" type="button">All <span class="badge bg-secondary ms-1" id="countAll">0</span></button></li>
</ul>

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search requisition # or invoice #...">
        </div>
    </div>
    <div class="col-md-2">
        <input type="date" id="dateFrom" class="form-control" title="From date">
    </div>
    <div class="col-md-2">
        <input type="date" id="dateTo" class="form-control" title="To date">
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<div class="modern-card p-3 sm-fill-card">
    <div id="fn-pr-container" class="fn-card-grid">
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading payment requests...</p>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-muted small" id="tableInfo">Loading...</span>
        <nav><ul class="pagination pagination-sm mb-0" id="paginationContainer"></ul></nav>
    </div>
</div>

<!-- Request Detail Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="requestDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="requestDetailBody">
        <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
