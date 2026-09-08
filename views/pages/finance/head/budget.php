<?php
require_once __DIR__ . '/../../../../app/core/CutoffPeriod.php';

use App\Core\CutoffPeriod;
use App\Models\Budget;

define('SHELFSENSE_INTERNAL_INCLUDE', true);
require_once __DIR__ . '/../../../../app/handlers/finance/head/budget/get.php';

$title = 'Budget Management - Finance Head';
$pageTitle = 'Budget Management';
$activePage = 'head_budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260908150000"></script>'
    . '<script src="/ShelfSense/public/assets/js/finance/head/budget.js?v=20260908600000"></script>';

$defaultPeriod = CutoffPeriod::getCurrentKey();
$initialData = budget_overview_build_data(new Budget(), $defaultPeriod);
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$periodOptionsHtml = '';
foreach (CutoffPeriod::getRecentHalves(2, 1) as $half) {
    $selected = $half['key'] === $defaultPeriod ? ' selected' : '';
    $periodOptionsHtml .= '<option value="' . htmlspecialchars($half['key']) . '"' . $selected . '>' . htmlspecialchars($half['label']) . '</option>';
}

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>' . <<<EOT
<ul class="nav nav-tabs mb-3" id="budgetTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overviewTab" type="button">Overview</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#deptTab" type="button">Departments</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#historyTab" type="button">Transaction History</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#toleranceTab" type="button">Match Tolerance</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="overviewTab">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label fw-semibold mb-0">Period:</label>
                <select id="monthFilter" class="form-select form-select-sm" style="max-width:220px;">{$periodOptionsHtml}</select>
            </div>
        </div>
        <div id="fh-budget-table" class="modern-card p-3"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
    </div>

    <div class="tab-pane fade" id="deptTab">
        <div class="row g-2 mb-3">
            <div class="col-md-4"><input type="text" id="newDeptName" class="form-control" placeholder="Department name"></div>
            <div class="col-md-3"><input type="text" id="newDeptCode" class="form-control" placeholder="Code (optional)"></div>
            <div class="col-md-2"><button class="btn btn-yellow-primary" id="addDeptBtn">Add</button></div>
        </div>
        <table class="table table-sm" id="deptTable"><thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead><tbody id="deptTableBody"></tbody></table>
    </div>

    <div class="tab-pane fade" id="historyTab">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Date</th><th>Department</th><th>Period</th><th>Type</th><th>Amount</th><th>By</th><th>Note</th></tr></thead>
                <tbody id="historyTableBody"><tr><td colspan="7" class="text-center py-4">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="toleranceTab">
        <div class="row g-3" style="max-width:500px;">
            <div class="col-md-6"><label class="form-label">Price Tolerance (%)</label><input type="number" id="tolPricePercent" class="form-control" step="0.01" min="0"></div>
            <div class="col-md-6"><label class="form-label">Price Tolerance (₱ flat)</label><input type="number" id="tolPriceAmount" class="form-control" step="0.01" min="0"></div>
            <div class="col-md-6"><label class="form-label">Quantity Tolerance (%)</label><input type="number" id="tolQtyPercent" class="form-control" step="0.01" min="0"></div>
        </div>
        <p class="text-muted small mt-2">An invoice line passes the price check if it's within EITHER the percent OR the flat amount (whichever is looser).</p>
        <button class="btn btn-yellow-primary btn-sm" id="saveToleranceBtn">Save</button>
    </div>
</div>

<div class="modal fade" id="setBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Set Allocation</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p id="setBudgetDeptLabel"></p>
                <label class="form-label">New Allocated Amount</label>
                <input type="number" id="setBudgetAmount" class="form-control" min="0" step="0.01">
                <label class="form-label mt-2">Reason</label>
                <textarea id="setBudgetReason" class="form-control" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-yellow-primary btn-sm" id="confirmSetBudgetBtn">Save</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
