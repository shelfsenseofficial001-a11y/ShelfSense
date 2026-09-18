// ============================================
// ATTENDANCE REVIEW – GROUPED BY PAYROLL HALVES
// ============================================
console.log('✅ attendance_review.js loaded');

let currentMonthYear = '';

function formatDateDisplay(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function ordinalSuffix(n) { const s = ['th', 'st', 'nd', 'rd'], v = n % 100; return n + (s[(v - 20) % 10] || s[v] || s[0]); }
function formatOrdinalDate(dateStr) { const d = new Date(dateStr + 'T00:00:00'); return `${ordinalSuffix(d.getDate())} ${d.toLocaleDateString('en-US', { month: 'short' })} ${d.getFullYear()}`; }
function getDayAbbr(dateStr) { const d = new Date(dateStr + 'T00:00:00'); return d.toLocaleDateString('en-US', { weekday: 'short' }); }
function hoursBetween(t1, t2) { if (!t1 || !t2) return 0; let [h1, m1] = t1.split(':').map(Number), [h2, m2] = t2.split(':').map(Number); let start = h1 * 60 + m1, end = h2 * 60 + m2; if (end < start) end += 24 * 60; return Math.max(0, (end - start) / 60); }
function formatTimeShort(t) { if (!t) return '-'; let p = t.split(':'); return p[0] + ':' + p[1]; }
function escapeHtmlReview(t) { if (!t) return ''; let d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

function getWeekStatusBadge(status) {
    const labels = {
        'draft': '<i class="bi bi-pencil-square"></i> Draft',
        'sent': '<i class="bi bi-send-fill"></i> Sent',
        'locked': '<i class="bi bi-lock-fill"></i> Locked',
        'approved': '<i class="bi bi-check-circle-fill"></i> Approved',
        'rejected': '<i class="bi bi-x-circle-fill"></i> Rejected',
        'in_progress': '<i class="bi bi-hourglass-split"></i> In Progress'
    };
    return `<span class="week-status-badge ${status}">${labels[status] || status}</span>`;
}

// Mirrors the equivalent helpers in attendance.js -- kept local since the two
// scripts never load on the same page, but the status vocabulary (the
// attendance.status enum) is identical so the mapping must stay identical too.
function getDayStatusClass(s, rd) { if (rd || s === 'rest_day') return 'status-rest-day'; let m = { present: 'status-present', late: 'status-late', absent: 'status-absent', leave_paid: 'status-leave', leave_unpaid: 'status-leave', holiday_no_work: 'status-holiday', holiday_work: 'status-present' }; return m[s] || 'status-absent'; }
function getDayStatusLabel(s, rd, re) { if (rd || s === 'rest_day') return 'Rest Day'; if (!re) return 'No Record'; let m = { present: 'Present', late: 'Late', absent: 'Absent', leave_paid: 'Leave (Paid)', leave_unpaid: 'Leave (Unpaid)', holiday_no_work: 'Holiday (No Work)', holiday_work: 'Holiday (Work)' }; return m[s] || 'Unknown'; }
function getDayStatusIconClass(s, rd, re) { if (rd || s === 'rest_day') return 'bi-moon-stars-fill'; if (!re) return 'bi-hourglass-split'; let m = { present: 'bi-check-circle-fill', late: 'bi-exclamation-triangle-fill', absent: 'bi-x-circle-fill', leave_paid: 'bi-clipboard-check-fill', leave_unpaid: 'bi-clipboard-fill', holiday_no_work: 'bi-stars', holiday_work: 'bi-stars' }; return m[s] || 'bi-question-circle'; }

function getHalfStatus(weekStatuses) {
    if (weekStatuses.every(s => s === 'locked' || s === 'approved')) return 'locked';
    if (weekStatuses.some(s => s === 'sent' || s === 'approved')) return 'sent';
    return 'draft';
}

function loadMonthReview() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    currentMonthYear = `${year}-${month}`;

    const statusDisplay = document.getElementById('reviewStatusDisplay');
    if (statusDisplay) statusDisplay.textContent = 'Loading month data...';

    const placeholder = document.getElementById('loadingPlaceholder');
    if (placeholder) {
        placeholder.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading...</p>
            </div>
        `;
        placeholder.style.display = 'block';
    }

    const container = document.getElementById('reviewContent');
    if (container) container.innerHTML = '';

    fetch(`?page=api_get_month_attendance&month_year=${currentMonthYear}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderReview(data.data);
                if (statusDisplay) statusDisplay.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Loaded';
                if (placeholder) placeholder.style.display = 'none';
            } else {
                if (statusDisplay) statusDisplay.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i> ' + (data.message || 'Error');
                showNoData();
            }
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            if (statusDisplay) statusDisplay.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i> Error loading';
            showNoData();
        });
}

function renderReview(data) {
    const container = document.getElementById('reviewContent');
    if (!container) return;
    container.innerHTML = '';

    const monthName = MONTH_NAMES[parseInt(document.getElementById('monthSelect').value) - 1];
    const year = document.getElementById('yearSelect').value;

    const weeks = data.weeks || {};
    const weekNumbers = [1, 2, 3, 4];
    const half1Weeks = [1, 2];
    const half2Weeks = [3, 4];

    function renderHalf(halfNumber, weekNumbersArray, label) {
        const halfWeeks = weekNumbersArray.map(num => ({
            num: num,
            stats: weeks[num] || {
                week_days: 0,
                total_days: 0,
                present_days: 0,
                late_days: 0,
                absent_days: 0,
                leave_paid_days: 0,
                leave_unpaid_days: 0,
                rest_days: 0,
                holiday_days: 0,
                total_overtime: 0,
                status: 'draft'
            }
        }));

        const statuses = halfWeeks.map(w => w.stats.status);
        const halfStatus = getHalfStatus(statuses);
        const allLocked = statuses.every(s => s === 'locked' || s === 'approved');
        const payrollGenerated = allLocked;
        const payrollStatus = payrollGenerated ? '<i class="bi bi-check-circle-fill text-success"></i> Generated' : '<i class="bi bi-hourglass-split"></i> Not Generated';

        const hasSentWeeks = statuses.some(s => s === 'sent');
        const showApproveButton = hasSentWeeks && !payrollGenerated;

        // Get date range for this half
        const month = parseInt(document.getElementById('monthSelect').value);
        const year = parseInt(document.getElementById('yearSelect').value);
        const daysInMonth = new Date(year, month, 0).getDate();
        let startDay, endDay;
        if (halfNumber === 1) {
            startDay = 1;
            endDay = (daysInMonth === 31) ? 16 : 15;
        } else {
            startDay = (daysInMonth === 31) ? 17 : 16;
            endDay = daysInMonth;
        }
        const startDate = new Date(year, month - 1, startDay);
        const endDate = new Date(year, month - 1, endDay);
        const dateRange = `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;

        let sentInfo = '';
        if (data.sent_by && data.sent_at) {
            sentInfo = `<span class="sent-info">Sent by ${data.sent_by} on ${formatDateDisplay(data.sent_at)}</span>`;
        }

        // Build table
        let tableHtml = `
            <div class="half-section">
                <div class="half-header">
                    <div>
                        <span class="half-title"><i class="bi bi-bar-chart-fill"></i> ${label} (${dateRange})</span>
                        <span class="half-status">
                            Status: ${getWeekStatusBadge(halfStatus)}
                        </span>
                        <span class="half-payroll-status">Payroll: ${payrollStatus}</span>
                        ${sentInfo}
                    </div>
                    <div class="half-actions">
                        ${showApproveButton ? `
                            <button class="btn btn-sm btn-success approve-half-btn" data-half="${halfNumber}">
                                <i class="bi bi-check2-circle"></i> Approve All
                            </button>
                        ` : (payrollGenerated ? `<span class="text-success"><i class="bi bi-check-circle-fill"></i> Approved</span>` : '')}
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="half-table">
                        <thead>
                            <tr>
                                <th>Week</th>
                                <th>Days</th>
                                <th>Present</th>
                                <th>Late</th>
                                <th>Absent</th>
                                <th>Leave</th>
                                <th>Rest</th>
                                <th>Holiday</th>
                                <th>OT (hrs)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        // Rows per week
        let totals = {
            week_days: 0,
            present_days: 0,
            late_days: 0,
            absent_days: 0,
            leave_paid_days: 0,
            leave_unpaid_days: 0,
            rest_days: 0,
            holiday_days: 0,
            total_overtime: 0
        };

        halfWeeks.forEach(({ num, stats }) => {
            const w = stats;

            // ✅ Convert to numbers to avoid string concatenation
            const weekDays = Number(w.week_days) || 0;
            const present = Number(w.present_days) || 0;
            const late = Number(w.late_days) || 0;
            const absent = Number(w.absent_days) || 0;
            const leavePaid = Number(w.leave_paid_days) || 0;
            const leaveUnpaid = Number(w.leave_unpaid_days) || 0;
            const rest = Number(w.rest_days) || 0;
            const holiday = Number(w.holiday_days) || 0;
            const overtime = Number(w.total_overtime) || 0;

            totals.week_days += weekDays;
            totals.present_days += present;
            totals.late_days += late;
            totals.absent_days += absent;
            totals.leave_paid_days += leavePaid;
            totals.leave_unpaid_days += leaveUnpaid;
            totals.rest_days += rest;
            totals.holiday_days += holiday;
            totals.total_overtime += overtime;

            const status = w.status || 'draft';
            const canApprove = (status === 'sent');
            const canRetract = (status === 'sent');
            const isLocked = (status === 'locked' || status === 'approved');

            // Use week_days for Days column
            const daysDisplay = weekDays;

            tableHtml += `
                <tr>
                    <td><strong>Week ${num}</strong></td>
                    <td>${daysDisplay}</td>
                    <td>${present}</td>
                    <td>${late}</td>
                    <td>${absent}</td>
                    <td>${leavePaid + leaveUnpaid}</td>
                    <td>${rest}</td>
                    <td>${holiday}</td>
                    <td>${overtime}</td>
                    <td>${getWeekStatusBadge(status)}</td>
                    <td>
                        ${canApprove ? `<button class="btn btn-sm btn-success action-btn-sm approve-week-btn" data-week="${num}"><i class="bi bi-check2-circle"></i></button>` : ''}
                        ${canRetract ? `<button class="btn btn-sm btn-warning action-btn-sm retract-week-btn" data-week="${num}"><i class="bi bi-arrow-counterclockwise"></i></button>` : ''}
                        ${isLocked ? `<span class="text-success small"><i class="bi bi-lock-fill"></i></span>` : ''}
                        ${status === 'draft' && w.total_days === 0 ? `<span class="text-muted small">No data</span>` : ''}
                    </td>
                </tr>
            `;
        });

        // Total row – now numeric sums
        tableHtml += `
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td>${totals.week_days}</td>
                <td>${totals.present_days}</td>
                <td>${totals.late_days}</td>
                <td>${totals.absent_days}</td>
                <td>${totals.leave_paid_days + totals.leave_unpaid_days}</td>
                <td>${totals.rest_days}</td>
                <td>${totals.holiday_days}</td>
                <td>${totals.total_overtime}</td>
                <td></td>
                <td></td>
            </tr>
        `;

        tableHtml += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        return tableHtml;
    }

    let html = '';
    if (weekNumbers.some(n => weeks[n] && weeks[n].week_days > 0)) {
        html += renderHalf(1, half1Weeks, '1ST HALF PAYROLL (Weeks 1-2)');
        html += renderHalf(2, half2Weeks, '2ND HALF PAYROLL (Weeks 3-4)');
    } else {
        html = `<div class="text-center text-muted py-4">No attendance data found for this month.</div>`;
    }

    container.innerHTML = html;

    // Attach event listeners for approve/retract per week
    document.querySelectorAll('.approve-week-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const weekNum = parseInt(this.dataset.week);
            processWeekAction(weekNum, 'approve');
        });
    });

    document.querySelectorAll('.retract-week-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const weekNum = parseInt(this.dataset.week);
            processWeekAction(weekNum, 'retract');
        });
    });

    // Attach event listeners for "Approve All" per half
    document.querySelectorAll('.approve-half-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const half = parseInt(this.dataset.half);
            const weekNums = half === 1 ? [1, 2] : [3, 4];
            const weeksToApprove = weekNums.filter(num => {
                const w = weeks[num];
                return w && w.status === 'sent';
            });
            if (weeksToApprove.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Weeks to Approve',
                    text: 'All weeks in this half are already locked or not sent.'
                });
                return;
            }
            Swal.fire({
                title: 'Approve All?',
                text: `This will approve all sent weeks in the ${half === 1 ? '1st' : '2nd'} half.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Approve All',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    let i = 0;
                    function doNext() {
                        if (i >= weeksToApprove.length) {
                            loadMonthReview();
                            return;
                        }
                        const weekNum = weeksToApprove[i];
                        fetch('?page=api_approve_week', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                month_year: currentMonthYear,
                                week_number: weekNum,
                                action: 'approve'
                            })
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (!data.success) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Approval Failed',
                                        text: `Week ${weekNum}: ${data.message || 'Unknown error'}`
                                    });
                                    loadMonthReview();
                                    return;
                                }
                                i++;
                                setTimeout(doNext, 300);
                            })
                            .catch(() => {
                                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
                                loadMonthReview();
                            });
                    }
                    doNext();
                }
            });
        });
    });
}

function processWeekAction(weekNumber, action, silent = false) {
    if (!silent) {
        Swal.fire({
            title: `${action === 'approve' ? 'Approve' : 'Retract'} Week ${weekNumber}?`,
            html: `<p>${action === 'approve' ? 'This will lock the week and prevent further edits.' : 'This will return the week to draft.'}</p>`,
            icon: action === 'approve' ? 'success' : 'warning',
            showCancelButton: true,
            confirmButtonColor: action === 'approve' ? '#198754' : '#ffc107',
            confirmButtonText: `Yes, ${action === 'approve' ? 'Approve' : 'Retract'}`,
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                executeAction(weekNumber, action);
            }
        });
    } else {
        executeAction(weekNumber, action);
    }
}

function executeAction(weekNumber, action) {
    fetch('?page=api_approve_week', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            month_year: currentMonthYear,
            week_number: weekNumber,
            action: action
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadMonthReview();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Action Failed',
                    text: data.message || 'Please try again.'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please try again.'
            });
        });
}

function showNoData() {
    const container = document.getElementById('reviewContent');
    if (container) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                No data found for this month.
            </div>
        `;
    }
    const placeholder = document.getElementById('loadingPlaceholder');
    if (placeholder) placeholder.style.display = 'none';
}

document.getElementById('loadReviewBtn').addEventListener('click', loadMonthReview);

document.addEventListener('DOMContentLoaded', function() {
    const cm = new Date().getMonth() + 1;
    const cy = new Date().getFullYear();
    document.getElementById('monthSelect').value = String(cm).padStart(2, '0');
    document.getElementById('yearSelect').value = String(cy);
    loadMonthReview();
    loadMonthEmployees();

    document.getElementById('loadReviewBtn').addEventListener('click', loadMonthEmployees);
    document.getElementById('employeePicker').addEventListener('change', function() {
        const userId = this.value;
        if (userId) {
            openEmployeeDrillDown(userId);
        }
    });
});

// ============================================
// PER-EMPLOYEE DRILL-DOWN
// ============================================

function loadMonthEmployees() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    const monthYear = `${year}-${month}`;
    const picker = document.getElementById('employeePicker');
    if (!picker) return;

    fetch(`?page=api_get_month_employees&month_year=${monthYear}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const employees = data.data.employees || [];
            let options = '<option value="">Select an employee...</option>';
            employees.forEach(emp => {
                options += `<option value="${emp.user_id}">${emp.first_name} ${emp.last_name} (${emp.employee_number})</option>`;
            });
            picker.innerHTML = options;
            if (window.refreshSearchableSelect) window.refreshSearchableSelect(picker);
        })
        .catch(error => console.error('❌ loadMonthEmployees error:', error));
}

function openEmployeeDrillDown(userId) {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    const monthYear = `${year}-${month}`;

    const modalEl = document.getElementById('employeeDrillDownModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const body = document.getElementById('employeeDrillDownBody');
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`?page=api_get_employee_month_attendance&user_id=${userId}&month_year=${monthYear}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderEmployeeDrillDown(data.data, monthYear);
            } else {
                body.innerHTML = `<div class="text-center text-danger py-4">${data.message || 'Error loading timecard.'}</div>`;
            }
        })
        .catch(error => {
            console.error('❌ openEmployeeDrillDown error:', error);
            body.innerHTML = '<div class="text-center text-danger py-4">Something went wrong.</div>';
        });
}

function renderEmployeeDrillDown(data, monthYear) {
    const body = document.getElementById('employeeDrillDownBody');
    const emp = data.employee;
    const initials = ((emp.first_name || '')[0] || '') + ((emp.last_name || '')[0] || '');

    const totals = { present: 0, late: 0, absent: 0, leave: 0, rest: 0, holiday: 0, ot: 0 };
    (data.weeks || []).forEach(w => {
        totals.present += Number(w.present_days) || 0;
        totals.late += Number(w.late_days) || 0;
        totals.absent += Number(w.absent_days) || 0;
        totals.leave += (Number(w.leave_paid_days) || 0) + (Number(w.leave_unpaid_days) || 0);
        totals.rest += Number(w.rest_days) || 0;
        totals.holiday += Number(w.holiday_days) || 0;
        totals.ot += Number(w.total_overtime_hours) || 0;
    });

    // Real computed hours from the daily time_in/time_out span, not the
    // mockup's Approved/Rejected/Pending split -- there's no per-day
    // approval state in this app to report honestly (see attendance.js).
    let regularHoursTotal = 0;
    (data.days || []).forEach(d => {
        let ot = Number(d.overtime_hours) || 0;
        if (d.time_in && d.time_out) {
            regularHoursTotal += Math.max(0, hoursBetween(d.time_in, d.time_out) - ot);
        }
    });
    let totalHoursTracked = regularHoursTotal + totals.ot;
    let regularPct = totalHoursTracked > 0 ? (regularHoursTotal / totalHoursTracked * 100) : 0;
    let overtimePct = totalHoursTracked > 0 ? (totals.ot / totalHoursTracked * 100) : 0;

    let html = `
        <div class="hr-timecard-profile">
            <div class="hr-timecard-avatar">${initials.toUpperCase()}</div>
            <div>
                <div class="hr-timecard-name">${emp.first_name} ${emp.last_name}</div>
                <div class="hr-timecard-meta">${emp.employee_number} &middot; ${emp.role}</div>
            </div>
        </div>

        <div class="hr-timecard-hours-summary">
            <div class="hr-timecard-hours-header">
                <span class="hr-timecard-hours-label">Hour breakdown</span>
                <span class="hr-timecard-hours-total">${totalHoursTracked.toFixed(2)} hrs</span>
                <span class="hr-timecard-hours-legend">
                    <span><span class="legend-dot regular"></span>Regular: ${regularHoursTotal.toFixed(2)} hrs</span>
                    <span><span class="legend-dot overtime"></span>Overtime: ${totals.ot.toFixed(2)} hrs</span>
                </span>
            </div>
            <div class="hr-timecard-hours-bar">
                <div class="hr-timecard-hours-segment regular" style="width:${regularPct}%"></div>
                <div class="hr-timecard-hours-segment overtime" style="width:${overtimePct}%"></div>
            </div>
        </div>

        <div class="hr-timecard-stats">
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${totals.present}</div><div class="hr-timecard-stat-label">Present</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${totals.late}</div><div class="hr-timecard-stat-label">Late</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${totals.absent}</div><div class="hr-timecard-stat-label">Absent</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${totals.leave}</div><div class="hr-timecard-stat-label">Leave</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${totals.rest}</div><div class="hr-timecard-stat-label">Rest</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${totals.holiday}</div><div class="hr-timecard-stat-label">Holiday</div></div>
            <div class="hr-timecard-stat"><div class="hr-timecard-stat-value">${totals.ot}</div><div class="hr-timecard-stat-label">OT hrs</div></div>
        </div>

        <div class="edt-week-strip">
            ${(data.weeks || []).map(w => `
                <div class="edt-week-chip">
                    <strong>Week ${w.week_number}</strong>
                    ${getWeekStatusBadge(w.status)}
                    <span class="edt-week-actions">
                        ${w.status === 'sent' ? `<button class="btn btn-sm btn-success drill-approve-week-btn" data-week="${w.week_number}" title="Approves this week for the entire company"><i class="bi bi-check2-circle"></i></button>` : ''}
                        ${w.status === 'sent' ? `<button class="btn btn-sm btn-warning drill-retract-week-btn" data-week="${w.week_number}" title="Retracts this week for the entire company"><i class="bi bi-arrow-counterclockwise"></i></button>` : ''}
                    </span>
                </div>
            `).join('')}
        </div>
        <div class="text-muted small mb-3"><i class="bi bi-info-circle"></i> Approve/Retract applies to the whole company for that week, same as the main review table.</div>

        <div class="table-responsive">
            <table class="hr-timecard-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Scheduled</th>
                        <th>Time</th>
                        <th>Work Hours</th>
                        <th>OT (hrs)</th>
                        <th>Status</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    ${(data.days || []).map(d => {
                        let cellClass = getDayStatusClass(d.status, d.is_rest_day);
                        let label = getDayStatusLabel(d.status, d.is_rest_day, !!d.status);
                        let iconClass = getDayStatusIconClass(d.status, d.is_rest_day, !!d.status);
                        let timeRangeHtml = (d.time_in && d.time_out)
                            ? `<span class="hr-timecard-time-range"><span>${formatTimeShort(d.time_in)}</span><span class="hr-timecard-time-line"></span><span>${formatTimeShort(d.time_out)}</span></span>`
                            : `<span class="hr-timecard-time-range is-empty">-</span>`;
                        let hasNote = !!(d.notes && String(d.notes).trim());
                        let noteBtn = `<button class="hr-timecard-note-btn ${hasNote ? 'has-note' : ''}" ${hasNote ? '' : 'disabled'} data-note="${escapeHtmlReview(d.notes || '')}" data-date="${d.date}" title="${hasNote ? 'View note' : 'No note'}"><i class="bi bi-clipboard${hasNote ? '-fill' : ''}"></i></button>`;
                        let workHours = (d.time_in && d.time_out) ? Math.max(0, hoursBetween(d.time_in, d.time_out) - (Number(d.overtime_hours) || 0)) : 0;
                        return `
                        <tr class="${d.is_rest_day ? 'is-rest-day' : ''}">
                            <td class="hr-timecard-date-cell"><span class="hr-timecard-date-pill"><span class="hr-timecard-day-abbr">${getDayAbbr(d.date)}</span>${formatOrdinalDate(d.date)}</span></td>
                            <td>${d.scheduled_in && d.scheduled_out ? `${formatTimeShort(d.scheduled_in)} - ${formatTimeShort(d.scheduled_out)}` : '-'}</td>
                            <td>${timeRangeHtml}</td>
                            <td>${workHours.toFixed(2)}</td>
                            <td>${d.overtime_hours || 0}</td>
                            <td><span class="hr-timecard-status-pill ${cellClass}"><i class="bi ${iconClass} me-1"></i>${label}</span></td>
                            <td>${noteBtn}</td>
                        </tr>
                    `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;

    body.innerHTML = html;

    body.querySelectorAll('.drill-approve-week-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            drillWeekAction(parseInt(this.dataset.week), 'approve', monthYear);
        });
    });
    body.querySelectorAll('.drill-retract-week-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            drillWeekAction(parseInt(this.dataset.week), 'retract', monthYear);
        });
    });
    body.querySelectorAll('.hr-timecard-note-btn.has-note').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('hrTimecardNoteMeta').textContent = formatDateDisplay(this.dataset.date);
            document.getElementById('hrTimecardNoteText').textContent = this.dataset.note || '-';
            // The note button lives inside the already-open drill-down modal --
            // showing a second Bootstrap modal on top would stack two
            // .modal-backdrop elements (the exact bug fixed earlier on the Job
            // Postings drafts modal), so hide the drill-down first and restore
            // it once the note modal closes instead of layering them.
            const drillModalEl = document.getElementById('employeeDrillDownModal');
            const noteModalEl = document.getElementById('hrTimecardNoteModal');
            bootstrap.Modal.getInstance(drillModalEl)?.hide();
            bootstrap.Modal.getOrCreateInstance(noteModalEl).show();
            noteModalEl.addEventListener('hidden.bs.modal', function reopenDrillDown() {
                noteModalEl.removeEventListener('hidden.bs.modal', reopenDrillDown);
                bootstrap.Modal.getOrCreateInstance(drillModalEl).show();
            }, { once: true });
        });
    });
}

function drillWeekAction(weekNumber, action, monthYear) {
    Swal.fire({
        title: `${action === 'approve' ? 'Approve' : 'Retract'} Week ${weekNumber}?`,
        html: `<p>This affects <strong>the entire company's</strong> Week ${weekNumber}, not just this employee.</p>`,
        icon: action === 'approve' ? 'success' : 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'approve' ? '#198754' : '#ffc107',
        confirmButtonText: `Yes, ${action === 'approve' ? 'Approve' : 'Retract'}`,
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('?page=api_approve_week', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ month_year: monthYear, week_number: weekNumber, action: action })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const userId = document.getElementById('employeePicker').value;
                    if (userId) openEmployeeDrillDown(userId);
                    loadMonthReview();
                } else {
                    Swal.fire({ icon: 'error', title: 'Action Failed', text: data.message || 'Please try again.' });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
            });
    });
}