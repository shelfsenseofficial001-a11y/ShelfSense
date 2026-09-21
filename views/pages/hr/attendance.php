<?php
$title = 'Attendance - ShelfSense HR';
$pageTitle = 'Attendance Management';
$activePage = 'attendance';
$additional_js = '<script src="/ShelfSense/public/assets/js/hr/attendance.js?v=20260921120000"></script>';

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
    .table-scroll-wrapper { overflow-x: auto; }
    .week-progress { height: 4px; background: var(--border-color); border-radius: 2px; overflow: hidden; }
    .week-progress .progress-fill { height: 100%; background: var(--brand-yellow); transition: width 0.3s ease; }

    /* Edit Attendance modal */
    .edit-attendance-modal .modal-subtitle { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }
    .edit-employee-row {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--bg-card-subtle);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 12px 14px;
    }
    .edit-employee-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: var(--light-yellow-accent); color: var(--brand-yellow);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .edit-toggle-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .edit-toggle-pill {
        position: relative;
        display: flex; flex-direction: column; align-items: center; gap: 4px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 10px 6px;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        text-align: center;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .edit-toggle-pill i { font-size: 1.1rem; }
    .edit-toggle-pill .form-check-input { position: absolute; opacity: 0; width: 0; height: 0; }
    .edit-toggle-pill:has(.form-check-input:checked) {
        background: var(--brand-dark);
        border-color: var(--brand-dark);
        color: var(--on-dark);
    }

    /* Load button matches the search bar's own height/scale so the two
       read as one control instead of a small button tacked on the side */
    .attendance-load-btn {
        height: 38px;
        padding: 0 20px;
        font-size: 0.95rem;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Stat cards match the search bar's flatter card style (same corner
       radius, no shadow) instead of the app's default heavier card look */
    body.hr-theme .attendance-stat-card {
        border-radius: 10px !important;
        box-shadow: none !important;
    }

    /* Employee list (left pane) — page-specific, not shared with Review */
    .atm-employee-list { max-height: 680px; overflow-y: auto; }
    .atm-emp-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 10px; cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.18s ease;
        border: 1px solid transparent;
    }
    .atm-emp-item:hover { background: var(--bg-card-subtle); }
    .atm-emp-item.active { background: var(--light-yellow-subtle); border-color: var(--brand-yellow); }
    .atm-emp-item.dragging { opacity: 0.35; }
    .atm-emp-grip { color: var(--text-muted); font-size: 0.9rem; cursor: grab; flex-shrink: 0; }
    .atm-emp-grip:active { cursor: grabbing; }
    /* Ghost slot shown while dragging -- mirrors the dragged card's own
       content (see attendance.js) so it previews where it will land,
       instead of just an empty gap. */
    .atm-emp-placeholder {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 10px;
        border: 1.5px dashed var(--brand-yellow);
        background: var(--light-yellow-subtle);
        opacity: 0.6;
        transition: transform 0.18s ease;
        pointer-events: none;
    }
    .atm-emp-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: var(--light-yellow-accent); color: var(--brand-yellow);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.8rem;
    }
    .atm-emp-name { font-weight: 600; font-size: 0.85rem; }
    .atm-emp-role { font-size: 0.7rem; color: var(--text-muted); }
    .atm-emp-badge { font-size: 0.6rem; padding: 1px 6px; border-radius: 10px; margin-left: auto; flex-shrink: 0; }
    /* Below Bootstrap's md breakpoint the list and timecard already stack
       (col-md-4/col-md-8 default to full width), but the list's fixed
       680px max-height still ate most of a phone screen before a timecard
       was even selected -- cap it shorter there. */
    @media (max-width: 767.98px) {
        .atm-employee-list { max-height: 320px; }
    }

    #employeeTimecardPanel { padding: 24px; }

    /* Department chip row -- replaces a click-to-open <select> with
       always-visible, one-click filter pills. */
    .atm-chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .atm-chip {
        border: 1px solid var(--border-color);
        background: var(--bg-card-subtle);
        color: var(--text-muted);
        border-radius: 999px;
        padding: 7px 18px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .atm-chip:hover { border-color: var(--brand-yellow); color: var(--text-main); }
    .atm-chip.active { background: var(--brand-yellow); border-color: var(--brand-yellow); color: #fff; }

    /* Compact week navigator -- replaces the inline Month/Year/Week selects;
       the selects still exist (in the Week Picker modal) so all existing
       load logic is untouched. */
    .atm-week-nav { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .atm-week-nav-arrow {
        width: 38px; height: 38px; border-radius: 10px;
        border: 1px solid var(--border-color); background: var(--bg-card-subtle);
        color: var(--text-muted); display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: border-color 0.15s ease, color 0.15s ease;
    }
    .atm-week-nav-arrow:hover { border-color: var(--brand-yellow); color: var(--text-main); }
    .atm-week-nav-arrow:disabled { opacity: 0.4; cursor: not-allowed; }
    .atm-week-nav-label {
        display: flex; align-items: center; gap: 8px;
        height: 38px; padding: 0 16px; border-radius: 10px;
        border: 1px solid var(--border-color); background: var(--bg-card-subtle);
        color: var(--text-main); font-weight: 600; font-size: 0.85rem;
        cursor: pointer; white-space: nowrap; transition: border-color 0.15s ease;
    }
    .atm-week-nav-label:hover { border-color: var(--brand-yellow); }
    .atm-week-nav-label i { color: var(--brand-yellow); }

    .atm-page-header { margin-bottom: 10px; display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap; }
    .atm-page-header .atm-page-title { font-weight: 700; margin: 0; line-height: 1.1; }
    .atm-page-header .atm-page-title i { color: var(--brand-yellow); margin-right: 8px; font-size: 0.85em; }
    .atm-page-header .atm-page-subtitle { color: var(--text-muted); font-size: 0.82rem; line-height: 1.1; margin-top: 0; }
</style>

<!-- Page header -->
<div class="atm-page-header">
    <h2 class="atm-page-title"><i class="bi bi-calendar-check-fill"></i> Attendance</h2>
    <div class="atm-page-subtitle" id="atmPageSubtitle">-</div>
</div>

<!-- Filters -->
<!-- Search + manual Refresh (all filters below already auto-reload; this is only for re-pulling data without changing a filter) -->
<div class="attendance-toolbar mb-3">
    <div class="attendance-search">
        <i class="bi bi-search"></i>
        <input type="text" id="attendanceSearch" class="form-control" placeholder="Search employee by name or employee #...">
    </div>
    <button class="btn btn-yellow-outline attendance-load-btn" id="loadAttendanceBtn" title="Refresh without changing filters"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
</div>

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <label class="form-label fw-semibold d-block">Department</label>
        <select id="filterDepartment" class="form-select d-none">
            <option value="all">All Departments</option>
            <option value="employee">Cashier</option>
            <option value="hr_staff">HR Staff</option>
            <option value="finance_staff">Finance Staff</option>
            <option value="hr_head">Head HR</option>
            <option value="finance_head">Head Finance</option>
        </select>
        <div class="atm-chip-row" id="filterDepartmentChips">
            <button type="button" class="atm-chip active" data-value="all">All Departments</button>
            <button type="button" class="atm-chip" data-value="employee">Cashier</button>
            <button type="button" class="atm-chip" data-value="hr_staff">HR Staff</button>
            <button type="button" class="atm-chip" data-value="finance_staff">Finance Staff</button>
            <button type="button" class="atm-chip" data-value="hr_head">Head HR</button>
            <button type="button" class="atm-chip" data-value="finance_head">Head Finance</button>
        </div>
    </div>
    <div class="atm-week-nav">
        <button type="button" class="atm-week-nav-arrow" id="weekNavPrev" title="Previous week"><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="atm-week-nav-label" id="weekNavLabel" data-bs-toggle="modal" data-bs-target="#weekPickerModal" title="Jump to a specific week">
            <i class="bi bi-calendar3"></i><span id="weekNavLabelText">Loading week...</span>
        </button>
        <button type="button" class="atm-week-nav-arrow" id="weekNavNext" title="Next week"><i class="bi bi-chevron-right"></i></button>
        <span class="badge bg-secondary d-none" id="weekStatusBadge">Draft</span>
        <button class="btn btn-sm btn-success" id="sendToHeadHrBtn" style="display:none;">
            <i class="bi bi-send"></i> Send to Head HR
        </button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<!-- Week Picker Modal (Month/Year/Week selects live here, driven by the
     nav bar above; kept as real <select>s so all existing load/filter-chip
     logic keeps working unchanged) -->
<div class="modal fade" id="weekPickerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar3"></i> Jump to Week</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Month</label>
                        <select id="monthSelect" class="form-select">$monthOptions</select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Year</label>
                        <select id="yearSelect" class="form-select">$yearOptions</select>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label fw-semibold">Week</label>
                    <select id="weekSelect" class="form-select"><option value="">Loading weeks...</option></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-yellow-primary btn-sm" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-2 mb-3">
    <div class="col"><div class="modern-card attendance-stat-card p-2 text-center"><small class="text-muted">Total</small><h5 class="mb-0" id="statTotal">0</h5></div></div>
    <div class="col"><div class="modern-card attendance-stat-card p-2 text-center"><small class="text-muted">Present</small><h5 class="mb-0 text-success" id="statPresent">0</h5></div></div>
    <div class="col"><div class="modern-card attendance-stat-card p-2 text-center"><small class="text-muted">Late</small><h5 class="mb-0 text-warning" id="statLate">0</h5></div></div>
    <div class="col"><div class="modern-card attendance-stat-card p-2 text-center"><small class="text-muted">Absent</small><h5 class="mb-0 text-danger" id="statAbsent">0</h5></div></div>
    <div class="col"><div class="modern-card attendance-stat-card p-2 text-center"><small class="text-muted">Leave</small><h5 class="mb-0 text-info" id="statLeave">0</h5></div></div>
    <div class="col"><div class="modern-card attendance-stat-card p-2 text-center"><small class="text-muted">Rest Day</small><h5 class="mb-0 text-secondary" id="statRestDay">0</h5></div></div>
</div>

<!-- Send status message (dynamically shown/hidden) -->
<div id="sendStatusMessage" style="display:none;"></div>

<!-- Week range/progress text still drives the active-filter-chips default
     comparison and per-employee completeness math elsewhere -- kept in the
     DOM but out of view now that the week nav bar + per-employee badges
     already show this information. -->
<div class="d-none">
    <strong id="weekRangeDisplay">Loading week...</strong>
    <span id="progressText">0 of 0 employees complete</span>
    <div class="week-progress"><div class="progress-fill" id="progressFill" style="width:0%;"></div></div>
</div>

<!-- Employee List + Timecard -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="modern-card">
            <div class="card-body p-2">
                <div class="atm-employee-list" id="employeeListPanel">
                    <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted small">Loading attendance...</p></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="modern-card">
            <div class="card-body" id="employeeTimecardPanel">
                <div class="hr-timecard-placeholder">
                    <i class="bi bi-person-lines-fill"></i>
                    Select an employee to view their timecard.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content edit-attendance-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Edit Attendance</h5>
                    <div class="modal-subtitle" id="editDateDisplay">-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAttendanceForm">
                <div class="modal-body">
                    <input type="hidden" id="editUserId" name="user_id">
                    <input type="hidden" id="editDate" name="date">
                    <input type="hidden" id="editScheduledIn" name="scheduled_in">
                    <input type="hidden" id="editScheduledOut" name="scheduled_out">

                    <div class="edit-employee-row">
                        <div class="edit-employee-avatar"><i class="bi bi-person-fill"></i></div>
                        <div>
                            <div class="fw-semibold" id="editEmployeeName">-</div>
                            <div class="text-muted small">Scheduled: <span id="editScheduledShift">-</span></div>
                        </div>
                        <div class="ms-auto" id="editStatusDisplay"><span class="badge bg-secondary">Auto-calculated</span></div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-5">
                            <label class="form-label fw-semibold">Time In</label>
                            <input type="time" id="editTimeIn" name="time_in" class="form-control">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Time Out</label>
                            <input type="time" id="editTimeOut" name="time_out" class="form-control">
                        </div>
                        <div class="col-2">
                            <label class="form-label fw-semibold">OT (hrs)</label>
                            <input type="number" id="editOvertime" name="overtime_hours" class="form-control" step="0.5" min="0" max="24" readonly>
                            <small class="text-muted">Auto</small>
                        </div>
                    </div>

                    <div class="edit-toggle-grid mt-3">
                        <label class="edit-toggle-pill" for="editOnLeave">
                            <input class="form-check-input" type="checkbox" id="editOnLeave" name="on_leave">
                            <i class="bi bi-calendar2-week"></i> On Leave
                        </label>
                        <label class="edit-toggle-pill" for="editRestDay">
                            <input class="form-check-input" type="checkbox" id="editRestDay" name="is_rest_day">
                            <i class="bi bi-moon-stars"></i> Rest Day
                        </label>
                        <label class="edit-toggle-pill" for="editHoliday">
                            <input class="form-check-input" type="checkbox" id="editHoliday" name="is_holiday">
                            <i class="bi bi-stars"></i> Holiday
                        </label>
                        <label class="edit-toggle-pill" for="editAbsent">
                            <input class="form-check-input" type="checkbox" id="editAbsent" name="is_absent">
                            <i class="bi bi-x-circle"></i> Absent
                        </label>
                    </div>

                    <div class="mt-3"><label class="form-label fw-semibold">Notes</label><textarea id="editNotes" name="notes" class="form-control" rows="2" placeholder="Optional note about this day..."></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm"><i class="bi bi-check2"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DTR Image View Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="dtrViewModal" tabindex="-1" style="--bs-offcanvas-width: 600px;">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body text-center" id="dtrViewBody">
        <img id="dtrViewImage" src="" alt="DTR Image" style="max-width:100%; max-height:600px; display:none;">
        <p id="dtrViewPlaceholder" class="text-muted">No image available.</p>
    </div>
    <div class="p-3 border-top text-end">
        <button type="button" class="btn btn-danger btn-sm" id="dtrDeleteBtn" style="display:none;">
            <i class="bi bi-trash"></i> Delete
        </button>
    </div>
</div>

<!-- Day Note View Modal -->
<div class="modal fade" id="hrTimecardNoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard-fill"></i> Day Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-muted small mb-2" id="hrTimecardNoteMeta">-</div>
                <p class="mb-0" id="hrTimecardNoteText">-</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
HTML;

require_once __DIR__ . '/../../layouts/hr.php';