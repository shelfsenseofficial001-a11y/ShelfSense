<?php
$title = 'Employee Schedules - ShelfSense HR';
$pageTitle = 'Employee Schedules';
$activePage = 'schedules';
$additional_js = '<script src="/ShelfSense/public/assets/js/hr/schedules.js?v=20260830123402"></script>';

$content = <<<HTML
<style>
    .schedule-grid-table th, .schedule-grid-table td {
        text-align: center;
        vertical-align: middle;
        padding: 6px 4px;
        font-size: 0.85rem;
    }
    .schedule-grid-table .employee-name-cell {
        text-align: left;
        font-weight: 500;
        white-space: nowrap;
    }
    .schedule-time-input {
        width: 120px;
        padding: 6px 8px;
        font-size: 1rem;
        text-align: center;
    }
    .schedule-rest-day {
        background: #e5e7eb;
        color: #6b7280;
    }
    [data-bs-theme="dark"] .schedule-rest-day {
        background: #374151;
        color: #9ca3af;
    }
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
    /* Sync button - subtle but visible */
    #syncScheduleBtn {
        margin-right: 4px;
    }

    /* Master-detail layout: employee list is the entry point on the left,
       Contract Info + Schedule fill in as the detail panel on the right
       once someone is picked -- replaces the old top-row-of-2-cards +
       separate full-width table below, which had two different "pick an
       employee" controls (a dropdown and a table) fighting for the same
       job and a lot of dead empty-state space above the fold. */
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
    /* Schedule card (not Contract Info, which stays its natural short
       height) absorbs the rest of the detail column's height, and its
       table area grows with it, so the white card reaches all the way
       down instead of leaving gray page background showing below it. */
    .schedule-detail-col > .modern-card:last-child {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .schedule-detail-col > .modern-card:last-child > .card-body.p-0 {
        flex: 1 1 auto;
        min-height: 0;
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
        <h6 class="fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Contract Info</h6>
        <div id="contractInfoContent">
            <p class="text-muted small mb-0">Select an employee to view contract details.</p>
        </div>
    </div>

    <!-- Schedule Grid -->
    <div class="modern-card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-week me-2"></i> <span id="scheduleEmployeeName">Schedule</span></span>
                <div>
                    <span class="text-muted small me-2" id="scheduleEmployeeInfo" style="display:none;"></span>
                    <button class="btn btn-sm btn-outline-primary" id="syncScheduleBtn" title="Sync schedule from contract" style="display:none;">
                        <i class="bi bi-arrow-repeat"></i> Sync from Contract
                    </button>
                    <button class="btn btn-sm btn-success" id="saveScheduleBtn">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <button class="btn btn-sm btn-outline-secondary reset-schedule-btn" id="resetScheduleBtn">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 schedule-grid-table" id="scheduleGridTable">
                    <thead>
                        <tr>
                            <th style="min-width:120px; text-align:left;">Day</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Rest Day</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleGridBody">
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Select an employee on the left to view their schedule.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <span class="text-muted small">Set Time In/Out for each day. Check "Rest Day" for non-working days.</span>
        </div>
    </div>
    </div>
</div>

<!-- Contract Detail Modal -->
<div class="modal fade" id="contractDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contract Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contractDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const DAY_NAMES = {
    'monday': 'Monday',
    'tuesday': 'Tuesday',
    'wednesday': 'Wednesday',
    'thursday': 'Thursday',
    'friday': 'Friday',
    'saturday': 'Saturday',
    'sunday': 'Sunday'
};
const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
</script>

HTML;

require_once __DIR__ . '/../../layouts/hr.php';