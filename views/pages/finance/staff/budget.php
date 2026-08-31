<?php
require_once __DIR__ . '/../../../../app/core/CutoffPeriod.php';

use App\Core\CutoffPeriod;

$title = 'Budget Status - Finance Staff';
$pageTitle = 'Budget Status';
$activePage = 'staff_budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/staff/budget.js?v=20260901010000"></script>';

$defaultPeriod = CutoffPeriod::getCurrentKey();
$periodOptionsHtml = '';
foreach (CutoffPeriod::getRecentHalves(2, 1) as $half) {
    $selected = $half['key'] === $defaultPeriod ? ' selected' : '';
    $periodOptionsHtml .= '<option value="' . htmlspecialchars($half['key']) . '"' . $selected . '>' . htmlspecialchars($half['label']) . '</option>';
}

$content = <<<EOT
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <label class="form-label fw-semibold mb-0">Period:</label>
        <select id="monthFilter" class="form-select form-select-sm" style="max-width:220px;">{$periodOptionsHtml}</select>
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
