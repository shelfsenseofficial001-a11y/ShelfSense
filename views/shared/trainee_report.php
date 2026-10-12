<?php
// views/shared/trainee_report.php
//
// Shared across every staff role that can be a Trainer, a department-head
// reviewer, or HR Head: a Store Manager/Finance Head/Finance Staff/Employee/
// HR Staff/HR Head all land here on the same page, which shows only the
// section(s) that apply to them (see public/assets/js/shared/trainee_report.js).
// Modeled on views/shared/leave.php's role->layout auto-detect.

$title = 'Trainee Report - ShelfSense';
$pageTitle = 'Trainee Report';
$activePage = 'trainee_report';

$role = $_SESSION['role'] ?? 'cashier';
$layout = 'cashier';
if (in_array($role, ['hr_head', 'hr_staff', 'owner'])) {
    $layout = 'hr';
} elseif ($role === 'store_manager') {
    $layout = 'store_manager';
} elseif (in_array($role, ['finance_head', 'finance_staff'])) {
    $layout = 'finance';
}

$additional_js = '<script src="/ShelfSense/public/assets/js/shared/trainee_report.js?v=20261011000003"></script>';
$additional_css = '
<style>
    .tr-status-chip {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .tr-status-chip.submitted { background: #fef3c7; color: #92400e; }
    .tr-status-chip.forwarded { background: #dbeafe; color: #1e40af; }
    .tr-status-chip.hr_staff_reviewed { background: #ede9fe; color: #5b21b6; }
    .tr-status-chip.hr_reviewed { background: #d1fae5; color: #065f46; }
    [data-bs-theme="dark"] .tr-status-chip.submitted { background: #78350f; color: #fcd34d; }
    [data-bs-theme="dark"] .tr-status-chip.forwarded { background: #1e3a5f; color: #93c5fd; }
    [data-bs-theme="dark"] .tr-status-chip.hr_staff_reviewed { background: #4c1d95; color: #ddd6fe; }
    [data-bs-theme="dark"] .tr-status-chip.hr_reviewed { background: #064e3b; color: #6ee7b7; }

    .tr-timeline-item {
        border-left: 2px solid var(--border-color);
        padding: 2px 0 16px 18px;
        position: relative;
    }
    .tr-timeline-item:last-child { padding-bottom: 2px; }
    .tr-timeline-item::before {
        content: "";
        position: absolute;
        left: -6px;
        top: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--brand-yellow);
    }
    /* Review board (dept head / HR Staff / HR Head) */
    .tr-board-col { min-height: 480px; }
    .tr-board-trainee-row { cursor: pointer; padding: 8px 12px; border: none; text-align: left; background: transparent; width: 100%; }
    .tr-board-trainee-row:hover { background: var(--light-yellow-subtle); }
    .tr-board-trainee-row.active { background: var(--light-yellow-subtle); font-weight: 600; }
    .tr-board-outcome { font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; color: #fff; }
    .tr-board-outcome.accepted { background: #198754; }
    .tr-board-outcome.failed { background: #dc3545; }

    .tr-week-tab {
        padding: 10px 8px;
        border: 1px solid var(--border-color, #e5e7eb);
        border-right: none;
        cursor: not-allowed;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-muted);
    }
    .tr-week-tab + .tr-week-tab { border-top: none; }
    .tr-week-tab.tr-week-clickable { cursor: pointer; }
    .tr-week-tab.tr-week-actionable { color: #92400e; background: #fef3c7; }
    [data-bs-theme="dark"] .tr-week-tab.tr-week-actionable { background: #78350f; color: #fcd34d; }
    .tr-week-tab.tr-week-done { color: #065f46; }
    .tr-week-tab.tr-week-selected {
        background: var(--bg-card);
        color: var(--text-main);
        margin-right: -1px;
        border-right: 1px solid var(--bg-card);
        position: relative;
        z-index: 2;
    }

    .tr-day-box {
        flex: 1 1 0;
        min-width: 50px;
        text-align: center;
        padding: 8px 4px;
        border-radius: 6px;
        background: var(--bg-card-subtle);
        border: 1px solid var(--border-color, #e5e7eb);
        position: relative;
    }
    .tr-day-box[role="button"] { cursor: pointer; }
    .tr-day-box[role="button"]:hover { background: var(--light-yellow-subtle); }
    .tr-day-name { font-size: 0.65rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
    .tr-day-num { font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
    .tr-day-tag { font-size: 0.6rem; font-weight: 600; color: #4b5563; }
    [data-bs-theme="dark"] .tr-day-tag { color: #9ca3af; }
    .tr-day-rest { background: #e5e7eb; cursor: not-allowed; }
    [data-bs-theme="dark"] .tr-day-rest { background: #374151; }
    .tr-day-selected { outline: 2px solid var(--brand-yellow); outline-offset: -2px; }
    .tr-day-filled { border-color: #198754; }
    .tr-day-check { color: #198754; font-size: 0.7rem; }
</style>
';

$content = <<<'EOT'
<div id="traineeReportRoot">
    <div class="text-center py-5" id="trReportLoading">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <!-- TRAINER SECTION -->
    <div class="modern-card p-4 mb-4 d-none" id="trTrainerSection">
        <h5 class="mb-3"><i class="bi bi-person-badge"></i> Your Trainee</h5>
        <div id="trTraineeSummary" class="mb-3"></div>
        <div id="trSubmitFormWrap" class="mb-4"></div>
        <h6 class="mb-3">Weekly Report Log</h6>
        <div id="trTrainerTimeline"></div>
    </div>

    <!-- REVIEW BOARD: dept head (submitted), HR Staff (screening), HR Head
         (final review) all share this one board, scoped server-side to
         whichever stage(s) the current role can act on. -->
    <div class="modern-card p-0 mb-4 d-none" id="trReviewBoard">
        <div class="row g-0">
            <div class="col-md-3 border-end tr-board-col">
                <div class="p-2 border-bottom">
                    <input type="text" class="form-control form-control-sm" id="trBoardSearch" placeholder="Search trainees...">
                </div>
                <div class="list-group list-group-flush" id="trBoardTraineeList" style="max-height:440px; overflow-y:auto;"></div>
                <div class="p-2 border-top text-center">
                    <button type="button" class="btn btn-link btn-sm" id="trBoardHistoryToggle">View History</button>
                </div>
            </div>
            <div class="col-md-2 border-end tr-board-col" id="trBoardWeekTabs"></div>
            <div class="col-md-7 tr-board-col p-3" id="trBoardContent">
                <p class="text-muted small mb-0">Select a trainee, then a week, to view their report.</p>
            </div>
        </div>
    </div>

    <div class="text-center text-muted py-5 d-none" id="trEmptyState">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
        Nothing to show here right now.
    </div>
</div>
EOT;

if ($layout === 'hr') {
    require_once __DIR__ . '/../layouts/hr.php';
} elseif ($layout === 'store_manager') {
    require_once __DIR__ . '/../layouts/store_manager.php';
} elseif ($layout === 'finance') {
    require_once __DIR__ . '/../layouts/finance.php';
} else {
    require_once __DIR__ . '/../layouts/cashier.php';
}
