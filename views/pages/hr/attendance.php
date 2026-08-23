<?php
$title = 'Attendance - ShelfSense HR';
$pageTitle = 'Attendance Management';
$activePage = 'attendance';
$additional_js = '<script src="/ShelfSense/public/assets/js/hr/attendance.js"></script>';

// Month/year options
$currentMonth = date('m');
$currentYear = date('Y');
$monthOptions = '';
for ($m=1;$m<=12;$m++) {
    $val = str_pad($m,2,'0',STR_PAD_LEFT);
    $label = date('F', mktime(0,0,0,$m,1,$currentYear));
    $sel = ($m == $currentMonth) ? 'selected' : '';
    $monthOptions .= "<option value=\"$val\" $sel>$label</option>";
}
$yearOptions = '';
for ($y = $currentYear-1; $y <= $currentYear+1; $y++) {
    $sel = ($y == $currentYear) ? 'selected' : '';
    $yearOptions .= "<option value=\"$y\" $sel>$y</option>";
}

$content = <<<HTML
<style>
    .attendance-grid-table th, .attendance-grid-table td {
        text-align: center;
        vertical-align: middle;
        padding: 6px 4px;
        font-size: 0.85rem;
    }
    .attendance-grid-table .employee-name-cell { text-align: left; font-weight: 500; white-space: nowrap; }
    .attendance-grid-table .employee-role-cell { font-size: 0.7rem; color: var(--text-muted); }
    .attendance-cell { cursor: pointer; border-radius: 4px; padding: 4px 8px; transition: all 0.2s ease; min-width: 60px; display: inline-block; }
    .attendance-cell:hover { transform: scale(1.05); box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
    .attendance-cell.status-present { background: #d1fae5; color: #065f46; }
    .attendance-cell.status-late { background: #fef3c7; color: #92400e; }
    .attendance-cell.status-absent { background: #fecaca; color: #991b1b; }
    .attendance-cell.status-leave { background: #dbeafe; color: #1e40af; }
    .attendance-cell.status-rest-day { background: #e5e7eb; color: #4b5563; }
    .attendance-cell.status-holiday { background: #f3e8ff; color: #6d28d9; }
    .attendance-cell .time-display { font-size: 0.7rem; font-weight: 500; }
    .attendance-cell .status-icon { font-size: 0.85rem; }
    [data-bs-theme="dark"] .attendance-cell.status-present { background: #064e3b; color: #6ee7b7; }
    [data-bs-theme="dark"] .attendance-cell.status-late { background: #78350f; color: #fcd34d; }
    [data-bs-theme="dark"] .attendance-cell.status-absent { background: #7f1d1d; color: #fca5a5; }
    [data-bs-theme="dark"] .attendance-cell.status-leave { background: #1e3a5f; color: #93c5fd; }
    [data-bs-theme="dark"] .attendance-cell.status-rest-day { background: #374151; color: #9ca3af; }
    [data-bs-theme="dark"] .attendance-cell.status-holiday { background: #3b1e5f; color: #c4b5fd; }
    .day-header { font-weight: 600; font-size: 0.75rem; }
    .day-header .day-number { font-weight: 400; font-size: 0.65rem; color: var(--text-muted); }
    .table-scroll-wrapper { overflow-x: auto; }
    .week-progress { height: 4px; background: var(--border-color); border-radius: 2px; overflow: hidden; }
    .week-progress .progress-fill { height: 100%; background: var(--brand-yellow); transition: width 0.3s ease; }
    .employee-complete-badge { font-size: 0.6rem; padding: 1px 6px; border-radius: 10px; }
</style>

<!-- Filters -->
<div class="row g-2 mb-3">
    <div class="col-md-2">
        <label class="form-label fw-semibold">Month</label>
        <select id="monthSelect" class="form-select">$monthOptions</select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Year</label>
        <select id="yearSelect" class="form-select">$yearOptions</select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Week</label>
        <select id="weekSelect" class="form-select"><option value="">Loading weeks...</option></select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Department</label>
        <select id="filterDepartment" class="form-select">
            <option value="all">All Departments</option>
            <option value="cashier">Cashier</option>
            <option value="hr_staff">HR Staff</option>
            <option value="finance_staff">Finance Staff</option>
            <option value="hr_head">Head HR</option>
            <option value="finance_head">Head Finance</option>
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end gap-2">
        <button class="btn btn-yellow-primary btn-sm w-100" id="loadAttendanceBtn"><i class="bi bi-refresh"></i> Load</button>
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-2 mb-3">
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Total</small><h5 class="mb-0" id="statTotal">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Present</small><h5 class="mb-0 text-success" id="statPresent">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Late</small><h5 class="mb-0 text-warning" id="statLate">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Absent</small><h5 class="mb-0 text-danger" id="statAbsent">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Leave</small><h5 class="mb-0 text-info" id="statLeave">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Rest Day</small><h5 class="mb-0 text-secondary" id="statRestDay">0</h5></div></div>
</div>

<!-- Send status message (dynamically shown/hidden) -->
<div id="sendStatusMessage" style="display:none;"></div>

<!-- Week Info & Progress -->
<div class="modern-card p-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <strong id="weekRangeDisplay">Loading week...</strong>
            <span class="badge bg-secondary ms-2" id="weekStatusBadge">Draft</span>
        </div>
        <div class="col-md-6">
            <div class="d-flex justify-content-end align-items-center gap-3">
                <span class="text-muted small" id="progressText">0 of 0 employees complete</span>
                <div class="week-progress" style="width:120px;"><div class="progress-fill" id="progressFill" style="width:0%;"></div></div>
                <button class="btn btn-sm btn-success" id="sendToHeadHrBtn" style="display:none;">
                    <i class="bi bi-send"></i> Send to Head HR
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Grid -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-scroll-wrapper">
            <table class="table table-hover mb-0 attendance-grid-table" id="attendanceGridTable">
                <thead id="attendanceGridHead"><tr><th style="min-width:160px; text-align:left;">Employee</th><th style="min-width:70px;">Role</th></tr></thead>
                <tbody id="attendanceGridBody"><tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading attendance...</p></td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editAttendanceForm">
                <div class="modal-body">
                    <input type="hidden" id="editUserId" name="user_id">
                    <input type="hidden" id="editDate" name="date">
                    <input type="hidden" id="editScheduledIn" name="scheduled_in">
                    <input type="hidden" id="editScheduledOut" name="scheduled_out">
                    <div class="row">
                        <div class="col-md-6"><label class="form-label fw-semibold">Employee</label><p id="editEmployeeName" class="fw-semibold mb-0">-</p></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Date</label><p id="editDateDisplay" class="fw-semibold mb-0">-</p></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Scheduled Shift</label><p id="editScheduledShift" class="text-muted small">-</p></div>
                    <div class="row g-2">
                        <div class="col-5"><label class="form-label fw-semibold">Time In</label><input type="time" id="editTimeIn" name="time_in" class="form-control"></div>
                        <div class="col-5"><label class="form-label fw-semibold">Time Out</label><input type="time" id="editTimeOut" name="time_out" class="form-control"></div>
                        <div class="col-2"><label class="form-label fw-semibold">OT (hrs)</label><input type="number" id="editOvertime" name="overtime_hours" class="form-control" step="0.5" min="0" max="24" readonly><small class="text-muted">Auto</small></div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="editOnLeave" name="on_leave"><label class="form-check-label" for="editOnLeave">On Leave</label></div></div>
                        <div class="col-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="editRestDay" name="is_rest_day"><label class="form-check-label" for="editRestDay">Rest Day</label></div></div>
                        <div class="col-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="editHoliday" name="is_holiday"><label class="form-check-label" for="editHoliday">Holiday</label></div></div>
                        <div class="col-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="editAbsent" name="is_absent"><label class="form-check-label" for="editAbsent">Absent</label></div></div>
                    </div>
                    <div class="mb-3 mt-3"><label class="form-label fw-semibold">Status</label><p id="editStatusDisplay" class="mb-0"><span class="badge bg-secondary">Auto-calculated</span></p></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Notes</label><textarea id="editNotes" name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DTR Image View Modal -->
<div class="modal fade" id="dtrViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">DTR Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="dtrViewBody">
                <img id="dtrViewImage" src="" alt="DTR Image" style="max-width:100%; max-height:600px; display:none;">
                <p id="dtrViewPlaceholder" class="text-muted">No image available.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger btn-sm" id="dtrDeleteBtn" style="display:none;">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/ShelfSense/public/assets/js/hr/attendance.js"></script>
HTML;

require_once __DIR__ . '/../../layouts/hr.php';