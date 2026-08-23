<?php
$title = 'Employee Schedules - ShelfSense HR';
$pageTitle = 'Employee Schedules';
$activePage = 'schedules';
$additional_js = '<script src="/ShelfSense/public/assets/js/hr/schedules.js"></script>';

$db = \App\Core\Database::getInstance()->getConnection();
$employees = $db->query("
    SELECT user_id, first_name, last_name, employee_number, role 
    FROM users 
    WHERE is_active = 1 AND role != 'trainee' 
    ORDER BY first_name
")->fetchAll();

$employeeOptions = '';
foreach ($employees as $emp) {
    $displayRole = getRoleName($emp['role']);
    $employeeOptions .= '<option value="' . $emp['user_id'] . '">' . $emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $displayRole . ')</option>';
}

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
    .schedule-layout {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .schedule-top-section {
        display: grid;
        grid-template-columns: 1fr 3fr;
        gap: 20px;
    }
    @media (max-width: 768px) {
        .schedule-top-section {
            grid-template-columns: 1fr;
        }
    }
    /* Sync button - subtle but visible */
    #syncScheduleBtn {
        margin-right: 4px;
    }
</style>

<div class="schedule-top-section">
    <!-- Contract Info Card -->
    <div>
        <div class="modern-card p-3" id="contractCard">
            <h6 class="fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Contract Info</h6>
            <div id="contractInfoContent">
                <p class="text-muted small mb-0">Select an employee to view contract details.</p>
            </div>
        </div>
    </div>

    <!-- Schedule Grid -->
    <div class="modern-card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-week me-2"></i> <span id="scheduleEmployeeName">Schedule</span></span>
                <div>
                    <span class="text-muted small me-2" id="scheduleEmployeeInfo">-</span>
                    <button class="btn btn-sm btn-outline-primary" id="syncScheduleBtn" title="Sync schedule from contract" style="display:none;">
                        <i class="bi bi-arrow-repeat"></i> Sync from Contract
                    </button>
                    <button class="btn btn-sm btn-success" id="saveScheduleBtn">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="resetScheduleBtn">
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
                                Select an employee below to view their schedule.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">Set Time In/Out for each day. Check "Rest Day" for non-working days.</span>
                <button class="btn btn-sm btn-outline-secondary" id="resetScheduleBtn">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Employee List -->
<div class="modern-card p-3" id="employeeListCard">
    <div class="row align-items-center g-3">
        <div class="col-md-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Employee List</h6>
        </div>
        <div class="col-md-3">
            <select id="employeeSelect" class="form-select searchable-select" data-placeholder="Search or select employee...">
                <option value="">Select an employee...</option>
                $employeeOptions
            </select>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-yellow-primary btn-sm me-2" id="loadScheduleBtn">
                <i class="bi bi-eye"></i> Load
            </button>
            <button class="btn btn-yellow-outline btn-sm" id="refreshEmployeeListBtn">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>
    <hr class="my-2">
    <div class="mb-2">
        <input type="text" id="employeeSearch" class="form-control form-control-sm" placeholder="Search employees by name, role, or employee number...">
    </div>
    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Employee #</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="employeeListBody">
                <tr><td colspan="4" class="text-center py-2 text-muted">Loading employees...</td></tr>
            </tbody>
        </table>
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