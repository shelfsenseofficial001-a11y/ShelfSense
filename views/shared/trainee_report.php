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

require_once __DIR__ . '/../../app/core/Auth.php';
use App\Core\Auth;

$role = $_SESSION['role'] ?? 'cashier';
$isHRHeadJs = (Auth::isHRHead() || Auth::isSuperAdmin()) ? 'true' : 'false';
$canManageTraineesJs = Auth::canAccessModule('hr_head') ? 'true' : 'false';
$layout = 'cashier';
if (in_array($role, ['hr_head', 'hr_staff', 'owner'])) {
    $layout = 'hr';
} elseif ($role === 'store_manager') {
    $layout = 'store_manager';
} elseif (in_array($role, ['finance_head', 'finance_staff'])) {
    $layout = 'finance';
}

$additional_js = '<script src="/ShelfSense/public/assets/js/shared/trainee_report.js?v=20261013120500"></script>';
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
    /* ---- Fill the page height instead of leaving dead space below the
       card (same "stretch to fill" convention as dashboard-theme.css). The
       three board columns then scroll internally rather than the page. ---- */
    #traineeReportRoot { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
    #trReviewBoard { flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0; overflow: hidden; }
    #trReviewBoard > .row { flex: 1 1 auto; min-height: 0; }
    .tr-board-col { display: flex; flex-direction: column; min-height: 0; }

    /* Review board (dept head / HR Staff / HR Head) */
    .tr-board-trainee-row { cursor: pointer; padding: 10px 12px; border: none; text-align: left; background: transparent; width: 100%; }
    .tr-board-trainee-row:hover { background: var(--light-yellow-subtle); }
    .tr-board-trainee-row.active { background: var(--light-yellow-subtle); font-weight: 600; box-shadow: inset 3px 0 0 var(--brand-yellow); }
    .tr-board-outcome { font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; color: #fff; }
    .tr-board-outcome.accepted { background: #198754; }
    .tr-board-outcome.failed { background: #dc3545; }
    /* Amber "needs you" counter + per-trainee progress text in the list */
    .tr-trainee-need { font-size: 0.6rem; font-weight: 700; padding: 1px 7px; border-radius: 10px; background: #fef3c7; color: #92400e; }
    [data-bs-theme="dark"] .tr-trainee-need { background: #78350f; color: #fcd34d; }
    .tr-trainee-progress { font-size: 0.62rem; color: var(--text-muted); font-weight: 600; }

    /* ---- Week column: progress header + status-dot list + legend ---- */
    .tr-week-progress { padding: 12px 10px 10px; border-bottom: 1px solid var(--border-color, #e5e7eb); }
    .tr-week-progress-label { font-size: 0.68rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .03em; }
    .tr-week-progress-count { font-size: 0.72rem; font-weight: 700; color: var(--text-main); }
    .tr-week-bar { height: 6px; border-radius: 4px; background: var(--border-color, #e5e7eb); overflow: hidden; }
    .tr-week-bar > span { display: block; height: 100%; background: #198754; border-radius: 4px; transition: width .25s ease; }
    .tr-week-need-pill { font-size: 0.62rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; background: #fef3c7; color: #92400e; }
    [data-bs-theme="dark"] .tr-week-need-pill { background: #78350f; color: #fcd34d; }
    .tr-week-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; }

    .tr-week-tab {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 10px;
        border-bottom: 1px solid var(--border-color, #e5e7eb);
        cursor: not-allowed;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-muted);
    }
    .tr-week-tab.tr-week-clickable { cursor: pointer; }
    .tr-week-tab.tr-week-clickable:hover { background: var(--light-yellow-subtle); }
    .tr-week-dot { width: 9px; height: 9px; border-radius: 50%; flex: 0 0 auto; background: var(--border-color, #d1d5db); }
    .tr-week-dot.submitted, .tr-week-dot.forwarded, .tr-week-dot.hr_staff_reviewed { background: #3b82f6; }
    .tr-week-dot.hr_reviewed { background: #198754; }
    .tr-week-dot.actionable { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.25); }
    .tr-week-label { flex: 1 1 auto; }
    .tr-week-state { font-size: 0.6rem; font-weight: 700; margin-left: auto; }
    .tr-week-tab.tr-week-actionable { color: #92400e; background: #fef3c7; }
    [data-bs-theme="dark"] .tr-week-tab.tr-week-actionable { background: #78350f; color: #fcd34d; }
    .tr-week-tab.tr-week-done .tr-week-state { color: #198754; }
    .tr-week-tab.tr-week-selected {
        background: var(--bg-card-subtle);
        color: var(--text-main);
        box-shadow: inset 3px 0 0 var(--brand-yellow);
    }
    .tr-week-legend { border-top: 1px solid var(--border-color, #e5e7eb); padding: 8px 10px; display: flex; flex-wrap: wrap; gap: 4px 12px; }
    .tr-week-legend-item { display: inline-flex; align-items: center; gap: 5px; font-size: 0.6rem; color: var(--text-muted); font-weight: 600; }

    #trBoardTraineeList { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    #trBoardContent { overflow-y: auto; }
    .tr-content-placeholder { flex: 1 1 auto; min-height: 160px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: var(--text-muted); }
    .tr-content-placeholder i { font-size: 1.8rem; margin-bottom: 8px; opacity: .6; }

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
                <div class="list-group list-group-flush" id="trBoardTraineeList"></div>
                <div class="p-2 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="trBoardHistoryToggle">
                        <i class="bi bi-clock-history me-1"></i><span class="tr-history-toggle-label">View History</span>
                    </button>
                </div>
            </div>
            <div class="col-md-2 border-end tr-board-col" id="trBoardWeekTabs"></div>
            <div class="col-md-7 tr-board-col p-3" id="trBoardContent">
                <div class="tr-content-placeholder">
                    <i class="bi bi-clipboard2-check"></i>
                    <p class="small mb-0">Select a trainee, then a week,<br>to view their report.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center text-muted py-5 d-none" id="trEmptyState">
        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
        Nothing to show here right now.
    </div>
</div>
EOT;

$content = '<script>window.HR_IS_HEAD = ' . $isHRHeadJs . '; window.HR_CAN_MANAGE_TRAINEES = ' . $canManageTraineesJs . ';</script>' . $content;

if ($layout === 'hr') {
    require_once __DIR__ . '/../layouts/hr.php';
} elseif ($layout === 'store_manager') {
    require_once __DIR__ . '/../layouts/store_manager.php';
} elseif ($layout === 'finance') {
    require_once __DIR__ . '/../layouts/finance.php';
} else {
    require_once __DIR__ . '/../layouts/cashier.php';
}
