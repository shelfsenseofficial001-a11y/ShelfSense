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

function getWeekStatusBadge(status) {
    const labels = {
        'draft': 'Draft',
        'sent': 'Sent 📨',
        'locked': '🔒 Locked',
        'approved': '✅ Approved',
        'rejected': '❌ Rejected',
        'in_progress': '⏳ In Progress'
    };
    return `<span class="week-status-badge ${status}">${labels[status] || status}</span>`;
}

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
                if (statusDisplay) statusDisplay.textContent = '✅ Loaded';
                if (placeholder) placeholder.style.display = 'none';
            } else {
                if (statusDisplay) statusDisplay.textContent = '❌ ' + (data.message || 'Error');
                showNoData();
            }
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            if (statusDisplay) statusDisplay.textContent = '❌ Error loading';
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
        const payrollStatus = payrollGenerated ? '✅ Generated' : '⏳ Not Generated';

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
                        <span class="half-title">📊 ${label} (${dateRange})</span>
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
                        ` : (payrollGenerated ? `<span class="text-success">✅ Approved</span>` : '')}
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
                        ${isLocked ? `<span class="text-success small">🔒</span>` : ''}
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
});