<?php
$title = 'Budget - Finance Head';
$pageTitle = 'Budget Management';
$activePage = 'head_budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/head/budget.js"></script>';

$defaultMonth = date('Y-m');

$additional_css = '
<style>
    .budget-card {
        padding: 20px;
        border-radius: 12px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        text-align: center;
    }
    .budget-card .amount {
        font-size: 2rem;
        font-weight: 700;
    }
    .budget-card .label {
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .budget-card .amount.positive { color: #059669; }
    .budget-card .amount.negative { color: #dc2626; }
    .budget-card .amount.warning { color: #d97706; }

    /* ✅ CRITICAL FIX: Prevent infinite height expansion */
    #budgetChartWrapper {
        position: relative;
        height: 300px !important;
        width: 100% !important;
        overflow: hidden !important;
    }
    #budgetChart {
        max-height: 300px !important;
        width: 100% !important;
    }
    /* ✅ Prevent table from expanding */
    .finance-page-content table,
    .finance-page-content .table,
    .finance-page-content .modern-card {
        height: auto !important;
        max-height: none !important;
    }
    /* ✅ Prevent any inline height from being set */
    .finance-page-content * {
        max-height: none !important;
    }
</style>
';

$content = <<<EOT
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="budget-card">
            <div class="label">Total Budget</div>
            <div class="amount" id="totalBudget">₱0.00</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="budget-card">
            <div class="label">Used Budget</div>
            <div class="amount" id="usedBudget">₱0.00</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="budget-card">
            <div class="label">Remaining Budget</div>
            <div class="amount" id="remainingBudget">₱0.00</div>
        </div>
    </div>
</div>

<div class="modern-card p-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-pencil text-yellow me-2"></i>Set Budget</h6>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Department</label>
            <select id="budgetDepartment" class="form-select">
                <option value="store">Store</option>
                <option value="hr">Human Resources</option>
                <option value="finance">Finance</option>
                <option value="general">General</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Month</label>
            <input type="month" id="budgetMonth" class="form-control" value="{$defaultMonth}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Allocated Budget</label>
            <input type="number" id="budgetAmount" class="form-control" step="0.01" placeholder="0.00">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-yellow-primary btn-sm w-100" id="setBudgetBtn">Set Budget</button>
        </div>
    </div>
    <div id="budgetMessage" class="mt-2"></div>
</div>

<!-- ✅ WRAPPED IN FIXED CONTAINER -->
<div class="modern-card p-3 mt-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart text-yellow me-2"></i>Budget Usage</h6>
    <div id="budgetChartWrapper">
        <canvas id="budgetChart"></canvas>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';