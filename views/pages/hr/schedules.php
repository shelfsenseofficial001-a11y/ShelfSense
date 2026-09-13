<?php
use App\Core\Database;

define('SHELFSENSE_INTERNAL_INCLUDE', true);
require_once __DIR__ . '/../../../app/handlers/hr/get_all_employees.php';
$initialData = hr_all_employees_build_data(Database::getInstance()->getConnection(), 'all');
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$title = 'Employee Schedules - ShelfSense HR';
$pageTitle = 'Employee Schedules';
$activePage = 'schedules';
$additional_js = '<script src="/ShelfSense/public/assets/js/shared/schedule-overrides.js?v=20260913910000"></script>'
    . '<script src="/ShelfSense/public/assets/js/hr/schedules.js?v=20260913900000"></script>';

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>' . <<<HTML
<style>
    .contract-info-card {
        background: var(--bg-card-subtle);
        border-left: 4px solid var(--brand-yellow);
        border-radius: 8px;
        padding: 12px 16px;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .contract-info-card:hover {
        background: var(--light-yellow-accent);
        border-color: var(--brand-yellow-hover);
    }
    .contract-info-card .contract-shift {
        font-weight: 600;
        color: var(--brand-yellow-hover);
    }
    [data-bs-theme="dark"] .contract-info-card .contract-shift {
        color: var(--brand-yellow);
    }
    /* Master-detail layout: employee list is the entry point on the left,
       Contract Info + Cutoff Schedule fill in as the detail panel on the
       right once someone is picked. */
    .schedule-master-detail {
        display: grid;
        grid-template-columns: 420px 1fr;
        gap: 20px;
        align-items: stretch;
        flex: 1 1 auto;
    }
    @media (max-width: 900px) {
        .schedule-master-detail {
            grid-template-columns: 1fr;
        }
    }
    .schedule-detail-col {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    /* Employee List card stretches to match the detail column's full
       height (grid align-items:stretch) and the scroll area grows to
       fill it, instead of stopping at a fixed height and leaving dead
       white space below the last row. */
    #employeeListCard {
        display: flex;
        flex-direction: column;
    }
    #employeeListScroll {
        flex: 1 1 auto;
        overflow-y: auto;
    }
    .employee-row {
        cursor: pointer;
        transition: background-color 0.12s ease;
    }
    body.hr-theme .employee-row:hover td {
        background-color: var(--bg-card-subtle);
    }
    body.hr-theme .employee-row.active td {
        background-color: var(--light-yellow-subtle);
    }
</style>

<div class="schedule-master-detail">
    <!-- Employee List: the entry point. Click a row to load their
         contract + schedule in the panel on the right. -->
    <div class="modern-card p-3" id="employeeListCard">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Employees</h6>
            <button class="btn btn-sm btn-outline-secondary" id="refreshEmployeeListBtn" title="Refresh list">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
        <input type="text" id="employeeSearch" class="form-control form-control-sm mb-2" placeholder="Search by name, role, or employee #...">
        <div id="employeeListScroll">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody id="employeeListBody">
                    <tr><td colspan="2" class="text-center py-2 text-muted">Loading employees...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="schedule-detail-col">
    <!-- Contract Info Card -->
    <div class="modern-card p-3" id="contractCard">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2"></i>Contract Info</h6>
            <button class="btn btn-sm btn-outline-primary" id="syncScheduleBtn" title="Sync the standing schedule from this employee's contract" style="display:none;">
                <i class="bi bi-arrow-repeat"></i> Sync from Contract
            </button>
        </div>
        <div id="contractInfoContent" class="mt-2">
            <p class="text-muted small mb-0">Select an employee to view contract details.</p>
        </div>
    </div>

    <!-- Cutoff Schedule Calendar: the effective schedule (standing + any
         per-period overrides) for the selected cutoff, one cell per date.
         Normally, clicking a day sets its Time In/Out for this period only.
         "Edit Rest Days" swaps into a mode where clicking picks a rest/work
         pair to swap instead -- removing a rest day always means
         allocating it to another day in the same period. -->
    <div class="modern-card p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-calendar2-range me-2"></i>Cutoff Schedule</h6>
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm" style="width:auto;" id="periodSelect"></select>
                <button type="button" class="btn btn-sm btn-outline-primary sched-rest-edit-btn" id="restEditBtn" title="Swap which days are rest days this period">
                    <i class="bi bi-arrow-left-right"></i> Edit Rest Days
                </button>
            </div>
        </div>
        <div id="restEditStatus" class="sched-rest-status mb-2" style="display:none;">
            <span id="restEditStatusText" class="small"></span>
            <div id="restEditStatusActions" class="d-flex align-items-center gap-2" style="display:none;">
                <input type="text" class="form-control form-control-sm" id="restEditReason" placeholder="Reason (required)" style="width:200px;">
                <button type="button" class="btn btn-sm btn-success" id="restEditSaveBtn"><i class="bi bi-save"></i> Save</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="restEditCancelBtn">Cancel</button>
            </div>
        </div>
        <div id="scheduleCalendarGrid" class="sched-calendar mb-2">
            <p class="text-muted small mb-0">Select an employee to view.</p>
        </div>

        <div id="overrideForm" class="border rounded p-3 mt-2" style="display:none;">
            <p class="small fw-semibold mb-2" id="overrideFormTitle">Edit day</p>
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Time In</label>
                    <input type="time" class="form-control form-control-sm" id="overrideFormTimeIn">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Time Out</label>
                    <input type="time" class="form-control form-control-sm" id="overrideFormTimeOut">
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label small mb-1">Reason (required)</label>
                <input type="text" class="form-control form-control-sm" id="overrideFormReason" placeholder="e.g. employee requested different hours">
            </div>
            <div class="mt-2 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success" id="overrideFormSaveBtn"><i class="bi bi-save"></i> Save</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="overrideFormCancelBtn">Cancel</button>
            </div>
        </div>
        <p class="text-muted small mb-0 mt-2">Only affects the selected cutoff period -- the standing schedule above is untouched.</p>

        <div class="mt-3 pt-3 border-top">
            <p class="small fw-semibold mb-2"><i class="bi bi-clock-history me-1"></i>Change History (this cutoff)</p>
            <div id="scheduleChangesList" class="small">
                <p class="text-muted small mb-0">Select an employee to view.</p>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Contract Detail Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="contractDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="contractDetailBody">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>

HTML;

require_once __DIR__ . '/../../layouts/hr.php';