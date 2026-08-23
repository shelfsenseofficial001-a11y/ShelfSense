<?php
$title = 'Budget Status - Finance Staff';
$pageTitle = 'Budget Status';
$activePage = 'staff_budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/staff/budget.js"></script>';

$content = <<<'EOT'
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <label class="form-label fw-semibold mb-0">Period:</label>
        <input type="month" id="monthFilter" class="form-control form-control-sm" style="max-width:180px;">
    </div>
    <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Budget allocation is managed by the Finance Head. This view is read-only and shows the same figures used to evaluate payment requests.
</div>

<div id="fn-budget-table" class="modern-card p-3">
    <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading budget status...</p>
    </div>
</div>

<p class="text-muted small mt-2" id="lastUpdated"></p>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
