<?php
$title = 'Front Department Schedules - Store Manager';
$pageTitle = 'Schedules';
$activePage = 'schedules';
$additional_js = '<script src="/ShelfSense/public/assets/js/shared/schedule-overrides.js?v=20260913100000"></script>'
    . '<script src="/ShelfSense/public/assets/js/store_manager/schedules.js?v=20260913100000"></script>';

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

        <div class="modern-card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-calendar2-range me-2"></i>Cutoff Schedule Changes</span>
                    <select class="form-select form-select-sm" style="width:auto;" id="periodSelect"></select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 schedule-grid-table">
                        <thead>
                            <tr>
                                <th style="min-width:100px; text-align:left;">Day</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Rest Day</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="overrideGridBody">
                            <tr><td colspan="6" class="text-center text-muted py-3">Select an employee to view.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="text" class="form-control form-control-sm" id="overrideReason" placeholder="Reason for this change (optional)" style="max-width:320px;">
                    <button class="btn btn-sm btn-success" id="saveOverrideBtn"><i class="bi bi-save"></i> Save Changes</button>
                    <button class="btn btn-sm btn-outline-secondary" id="resetOverrideBtn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                    <span class="text-muted small ms-auto">Only affects the selected cutoff period.</span>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

require_once __DIR__ . '/../../layouts/store_manager.php';
