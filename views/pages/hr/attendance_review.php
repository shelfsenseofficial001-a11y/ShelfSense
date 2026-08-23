<?php
$title = 'Attendance Review - ShelfSense HR';
$pageTitle = 'Attendance Review';
$activePage = 'attendance_review';
$additional_js = '<script src="/ShelfSense/public/assets/js/hr/attendance_review.js"></script>';

$content = <<<HTML
<style>
    .half-section {
        margin-bottom: 2rem;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        background: var(--bg-card);
    }
    .half-header {
        padding: 12px 20px;
        background: var(--light-yellow-subtle);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .half-header .half-title {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .half-header .half-status {
        font-size: 0.85rem;
    }
    .half-header .half-status .badge {
        font-size: 0.75rem;
    }
    .half-header .half-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .half-table {
        width: 100%;
        border-collapse: collapse;
    }
    .half-table th, .half-table td {
        padding: 6px 8px;
        text-align: center;
        border-bottom: 1px solid var(--border-color);
    }
    .half-table th {
        background: var(--bg-card-subtle);
        font-weight: 600;
        font-size: 0.8rem;
    }
    .half-table .total-row {
        background: var(--light-yellow-accent);
        font-weight: 600;
    }
    .half-table .total-row td {
        border-top: 2px solid var(--brand-yellow);
    }
    .week-status-badge {
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 10px;
    }
    .week-status-badge.draft { background: #e5e7eb; color: #4b5563; }
    .week-status-badge.sent { background: #fef3c7; color: #92400e; }
    .week-status-badge.locked { background: #d1fae5; color: #065f46; }
    .week-status-badge.approved { background: #d1fae5; color: #065f46; }
    .week-status-badge.rejected { background: #fecaca; color: #991b1b; }
    .half-payroll-status {
        font-size: 0.8rem;
        margin-left: 8px;
    }
    .sent-info {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-left: 8px;
    }
    .action-btn-sm {
        font-size: 0.7rem;
        padding: 2px 6px;
    }
    .placeholder-text {
        padding: 40px 0;
        text-align: center;
        color: var(--text-muted);
    }
    .placeholder-text i {
        font-size: 3rem;
        display: block;
        margin-bottom: 16px;
        opacity: 0.5;
    }
</style>

<!-- Month Selector -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <label class="form-label fw-semibold">Month</label>
        <select id="monthSelect" class="form-select searchable-select">
            <option value="01">January</option>
            <option value="02">February</option>
            <option value="03">March</option>
            <option value="04">April</option>
            <option value="05">May</option>
            <option value="06">June</option>
            <option value="07">July</option>
            <option value="08" selected>August</option>
            <option value="09">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Year</label>
        <select id="yearSelect" class="form-select searchable-select">
            <option value="2024">2024</option>
            <option value="2025">2025</option>
            <option value="2026" selected>2026</option>
            <option value="2027">2027</option>
            <option value="2028">2028</option>
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-yellow-primary btn-sm" id="loadReviewBtn">
            <i class="bi bi-eye"></i> Load Month
        </button>
    </div>
    <div class="col-md-4 text-end d-flex align-items-end justify-content-end">
        <span class="text-muted small" id="reviewStatusDisplay">Select a month to review</span>
    </div>
</div>

<!-- Placeholder (outside dynamic container) -->
<div id="loadingPlaceholder" class="placeholder-text">
    <i class="bi bi-inbox"></i>
    Select a month and click "Load Month".
</div>

<!-- Dynamic content container -->
<div id="reviewContent"></div>

<script>
const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
</script>

HTML;

require_once __DIR__ . '/../../layouts/hr.php';