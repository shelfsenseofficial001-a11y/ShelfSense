<?php
$title = 'Budget Management - Finance Head';
$pageTitle = 'Budget Management';
$activePage = 'head_budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/head/budget.js?v=20260829181340"></script>';

$defaultMonth = date('Y-m');

$content = <<<EOT
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <label class="form-label fw-semibold mb-0">Period:</label>
        <input type="month" id="monthFilter" class="form-control form-control-sm" style="max-width:180px;" value="{$defaultMonth}">
        <label class="form-label fw-semibold mb-0 ms-2">Department:</label>
        <select id="departmentFilter" class="form-select form-select-sm searchable-select" style="min-width:160px;" data-placeholder="All departments"></select>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        <button class="btn btn-yellow-outline btn-sm" id="exportBtn"><i class="bi bi-download"></i> Export CSV</button>
        <button class="btn btn-yellow-outline btn-sm" id="printBtn"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<div id="fn-near-limit-box"></div>

<div class="modern-card p-3 mb-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-building text-yellow me-2"></i>Budget Overview — All Departments</h6>
    <div id="fn-overview-table">
        <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
    </div>
    <p class="text-muted small mb-0 mt-2" id="lastUpdated"></p>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="modern-card p-3 mb-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square text-yellow me-2"></i>Set / Adjust Budget</h6>
            <form id="setBudgetForm">
                <div class="mb-2">
                    <label class="form-label fw-semibold">Department</label>
                    <select id="budgetDepartment" class="form-select searchable-select" required></select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Month</label>
                    <input type="month" id="budgetMonth" class="form-control" value="{$defaultMonth}" required>
                </div>
                <div id="currentBudgetInfo" class="small text-muted mb-2"></div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">New Allocated Budget</label>
                    <input type="number" id="budgetAmount" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div id="adjustmentPreview" class="small mb-2"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reason / Notes (Optional)</label>
                    <textarea id="budgetReason" class="form-control" rows="2" placeholder="e.g. Increased for Q3 restocking"></textarea>
                </div>
                <button type="submit" class="btn btn-yellow-primary btn-sm w-100" id="setBudgetBtn">
                    <i class="bi bi-save"></i> Save Budget
                </button>
            </form>
            <div id="budgetMessage" class="mt-2"></div>
        </div>

        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-yellow me-2"></i>Allocation Adjustment History</h6>
            <div id="fn-adjustment-history">
                <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="text-muted small" id="historyInfo"></span>
                <nav><ul class="pagination pagination-sm mb-0" id="historyPagination"></ul></nav>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="modern-card p-3" id="budgetReportSection">
            <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-bar-graph text-yellow me-2"></i>Budget Usage by Requisition</h6>
            <div id="fn-usage-table">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
