<?php
$title = 'Front Department Schedules - Store Manager';
$pageTitle = 'Schedules';
$activePage = 'schedules';
$additional_js = '<script src="/ShelfSense/public/assets/js/shared/schedule-overrides.js?v=20260913800000"></script>'
    . '<script src="/ShelfSense/public/assets/js/store_manager/schedules.js?v=20260913800000"></script>';

$content = <<<'HTML'
<style>
    .schedule-grid-table th, .schedule-grid-table td {
        text-align: center;
        vertical-align: middle;
        padding: 6px 4px;
        font-size: 0.85rem;
    }
    .sm-schedule-master-detail {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 20px;
        align-items: stretch;
    }
    @media (max-width: 900px) {
        .sm-schedule-master-detail { grid-template-columns: 1fr; }
    }
    .sm-schedule-detail-col {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    #smEmployeeListScroll { max-height: 520px; overflow-y: auto; }
    .sm-employee-row { cursor: pointer; }
    .sm-employee-row.active td { background-color: var(--light-yellow-subtle); }
</style>

<div class="sm-schedule-master-detail">
    <div class="modern-card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Front Department</h6>
            <button class="btn btn-sm btn-outline-secondary" id="smRefreshEmployeesBtn" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
        <input type="text" id="smEmployeeSearch" class="form-control form-control-sm mb-2" placeholder="Search by name or employee #...">
        <div id="smEmployeeListScroll">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Name</th><th>Role</th></tr></thead>
                <tbody id="smEmployeeListBody">
                    <tr><td colspan="2" class="text-center py-2 text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="sm-schedule-detail-col">
        <div class="modern-card p-3" id="smContractCard">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2"></i>Contract Info</h6>
                <button class="btn btn-sm btn-outline-primary" id="smSyncScheduleBtn" style="display:none;" title="Sync the standing schedule from this employee's contract">
                    <i class="bi bi-arrow-repeat"></i> Sync from Contract
                </button>
            </div>
            <div id="smContractInfoContent" class="mt-2">
                <p class="text-muted small mb-0">Select an employee to view contract details.</p>
            </div>
        </div>

        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar2-range me-2"></i>Cutoff Schedule</h6>
                <select class="form-select form-select-sm" style="width:auto;" id="periodSelect"></select>
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
            <p class="text-muted small mb-0 mt-2">Only affects the selected cutoff period.</p>

            <div class="mt-3 pt-3 border-top">
                <p class="small fw-semibold mb-2"><i class="bi bi-clock-history me-1"></i>Change History (this cutoff)</p>
                <div id="scheduleChangesList" class="small">
                    <p class="text-muted small mb-0">Select an employee to view.</p>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

require_once __DIR__ . '/../../layouts/store_manager.php';
