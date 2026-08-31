<?php
$title = 'Revenue Split - Finance Head';
$pageTitle = 'Revenue Split';
$activePage = 'head_revenue_split';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/head/revenue_split.js?v=20260901010000"></script>';

$defaultMonthPicker = date('Y-m');

$content = <<<EOT
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-sliders text-yellow me-2"></i>Split Rules</h6>
            <p class="text-muted small">Percentage of total store revenue each department gets per cutoff. General Budget always absorbs whatever's left.</p>
            <div id="rsRulesTable">
                <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
            </div>
            <button class="btn btn-yellow-primary btn-sm mt-2 w-100" id="rsSaveRulesBtn"><i class="bi bi-save"></i> Save Rules</button>
            <div id="rsRulesMessage" class="mt-2"></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-calculator text-yellow me-2"></i>Compute Split for a Cutoff</h6>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <label class="form-label fw-semibold mb-0">Period:</label>
                <input type="month" id="rsMonthPicker" class="form-control form-control-sm" style="max-width:180px;" value="{$defaultMonthPicker}">
                <button class="btn btn-yellow-outline btn-sm" id="rsLoadPeriodsBtn"><i class="bi bi-arrow-clockwise"></i> Load</button>
            </div>
            <div id="rsPeriodsList">
                <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<div class="modern-card p-3 mb-3" id="rsPreviewCard" style="display:none;">
    <h6 class="fw-bold mb-3"><i class="bi bi-eye text-yellow me-2"></i>Preview</h6>
    <div id="rsPreviewContent"></div>
</div>

<div class="modern-card p-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-yellow me-2"></i>History</h6>
    <div id="rsHistoryTable">
        <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
