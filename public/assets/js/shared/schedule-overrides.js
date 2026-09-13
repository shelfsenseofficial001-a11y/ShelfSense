// public/assets/js/shared/schedule-overrides.js
// Calendar view of one employee's effective schedule for a cutoff period,
// shared by HR's and Store Manager's Schedules pages. Each date cell shows
// that day's Time In/Out (or Rest Day) and, if it deviates from the
// standing schedule, the reason. The only change operation is a rest-day
// swap: pick a day that becomes rest and an existing rest day (same
// period) that becomes the work day instead -- one paired action with one
// reason, never a free-form per-day time edit.
//
// Usage: ScheduleOverrides.init({
//   periodSelectId, calendarGridId,
//   formContainerId, formRestDaySelectId, formWorkDaySelectId,
//   formReasonId, formSaveBtnId, formCancelBtnId,
//   emptyMessage, getCurrentUserId: () => currentEmployeeId
// });

// Assigned directly to window (not "const ScheduleOverrides = ...") --
// top-level const/let in a classic script creates a global *binding* but
// does NOT attach as a window property, so `window.ScheduleOverrides`
// checks elsewhere would silently always be false otherwise.
window.ScheduleOverrides = (function () {
    const DAY_NAMES = {
        monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu',
        friday: 'Fri', saturday: 'Sat', sunday: 'Sun'
    };
    const DAY_NAMES_FULL = {
        monday: 'Monday', tuesday: 'Tuesday', wednesday: 'Wednesday', thursday: 'Thursday',
        friday: 'Friday', saturday: 'Saturday', sunday: 'Sunday'
    };
    // Monday-first, matching this app's day_of_week convention elsewhere.
    const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    let opts = null;
    let currentPeriod = null; // { key, start_date, end_date, label }
    let effectiveByDay = {};  // day_of_week -> effective schedule row for the loaded period

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    // Pure UTC date math throughout -- parsing "YYYY-MM-DDT00:00:00" without
    // a "Z" is LOCAL time, and toISOString() converts back to UTC, which
    // rolls the date backward across midnight in any positive-UTC-offset
    // timezone (e.g. Philippines, UTC+8). That made addDays() return the
    // same date it was given instead of advancing, hanging the browser in
    // an infinite loop building the calendar. Explicit "Z" + getUTC*/setUTC*
    // sidesteps local-timezone conversion entirely.
    function dayOfWeekFor(dateStr) {
        const utcDay = new Date(dateStr + 'T00:00:00Z').getUTCDay();
        return ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][utcDay];
    }

    function addDays(dateStr, n) {
        const d = new Date(dateStr + 'T00:00:00Z');
        d.setUTCDate(d.getUTCDate() + n);
        return d.toISOString().slice(0, 10);
    }

    function init(options) {
        opts = options;
        const periodSelect = document.getElementById(opts.periodSelectId);

        fetch('?page=api_get_schedule_periods')
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                periodSelect.innerHTML = res.data.periods.map(p =>
                    `<option value="${p.key}" ${p.key === res.data.current_key ? 'selected' : ''}>${escapeHtml(p.label)}</option>`
                ).join('');
                currentPeriod = res.data.periods.find(p => p.key === res.data.current_key) || res.data.periods[0];
                loadForCurrentEmployee();
            });

        periodSelect.addEventListener('change', function () {
            fetch('?page=api_get_schedule_periods')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    currentPeriod = res.data.periods.find(p => p.key === periodSelect.value);
                    loadForCurrentEmployee();
                });
        });

        document.getElementById(opts.formSaveBtnId).addEventListener('click', saveSwap);
        document.getElementById(opts.formCancelBtnId).addEventListener('click', closeForm);
        closeForm();
    }

    function currentPeriodKey() {
        return document.getElementById(opts.periodSelectId).value;
    }

    function loadForCurrentEmployee() {
        const userId = opts.getCurrentUserId();
        const gridEl = document.getElementById(opts.calendarGridId);
        closeForm();

        if (!userId) {
            gridEl.innerHTML = `<p class="text-muted small mb-0">${escapeHtml(opts.emptyMessage || 'Select an employee to view.')}</p>`;
            effectiveByDay = {};
            return;
        }
        const periodKey = currentPeriodKey();
        if (!periodKey) return;

        gridEl.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>';

        fetch(`?page=api_get_effective_schedule&user_id=${encodeURIComponent(userId)}&period_key=${encodeURIComponent(periodKey)}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    gridEl.innerHTML = `<p class="text-danger small mb-0">${escapeHtml(res.message || 'Failed to load')}</p>`;
                    return;
                }
                currentPeriod = res.data.period;
                effectiveByDay = {};
                (res.data.schedule || []).forEach(row => { effectiveByDay[row.day_of_week] = row; });
                renderCalendar();
            });
    }

    function renderCalendar() {
        const gridEl = document.getElementById(opts.calendarGridId);
        if (!currentPeriod) { gridEl.innerHTML = ''; return; }

        // Leading blanks so the grid aligns Monday-first.
        const startDow = dayOfWeekFor(currentPeriod.start_date);
        const leadingBlanks = DAY_ORDER.indexOf(startDow);

        const cells = [];
        for (let i = 0; i < leadingBlanks; i++) cells.push(null);

        let cursor = currentPeriod.start_date;
        while (cursor <= currentPeriod.end_date) {
            cells.push(cursor);
            cursor = addDays(cursor, 1);
        }
        while (cells.length % 7 !== 0) cells.push(null);

        let html = '<div class="sched-cal-headrow">' + DAY_ORDER.map(d => `<div class="sched-cal-headcell">${DAY_NAMES[d]}</div>`).join('') + '</div>';
        html += '<div class="sched-cal-body">';
        for (let i = 0; i < cells.length; i += 7) {
            html += '<div class="sched-cal-row">';
            for (let j = i; j < i + 7; j++) {
                html += renderCell(cells[j]);
            }
            html += '</div>';
        }
        html += '</div>';
        gridEl.innerHTML = html;

        gridEl.querySelectorAll('.sched-cal-cell[data-day]').forEach(cell => {
            cell.addEventListener('click', function () { onCellClick(this.dataset.day); });
        });
        gridEl.querySelectorAll('.sched-cal-revert-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                revertDay(this.dataset.day, this);
            });
        });
    }

    function renderCell(dateStr) {
        if (!dateStr) return '<div class="sched-cal-cell sched-cal-cell-blank"></div>';

        const day = dayOfWeekFor(dateStr);
        const row = effectiveByDay[day];
        const dateNum = parseInt(dateStr.slice(8, 10), 10);

        if (!row) {
            return `<div class="sched-cal-cell" data-day="${day}"><div class="sched-cal-date">${dateNum}</div><div class="small text-muted">No baseline set</div></div>`;
        }

        const isRest = row.is_rest_day == 1;
        const timeLabel = isRest ? 'Rest Day' : `${(row.time_in || '').slice(0, 5)}–${(row.time_out || '').slice(0, 5)}`;
        const changedBadge = row.is_override ? '<span class="badge bg-warning text-dark sched-cal-badge">Changed</span>' : '';
        const reasonHtml = row.is_override && row.reason ? `<div class="sched-cal-reason">${escapeHtml(row.reason)}</div>` : '';
        const revertBtn = row.is_override ? `<button type="button" class="btn btn-sm btn-link p-0 sched-cal-revert-btn text-danger" data-day="${day}" title="Revert swap"><i class="bi bi-arrow-counterclockwise"></i></button>` : '';

        return `
            <div class="sched-cal-cell ${row.is_override ? 'sched-cal-cell-changed' : ''} ${isRest ? 'sched-cal-cell-rest' : ''}" data-day="${day}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="sched-cal-date">${dateNum}</div>
                    ${revertBtn}
                </div>
                <div class="sched-cal-time">${escapeHtml(timeLabel)}</div>
                ${changedBadge}
                ${reasonHtml}
            </div>
        `;
    }

    function onCellClick(day) {
        openForm(day);
    }

    function openForm(clickedDay) {
        const clickedRow = effectiveByDay[clickedDay];
        if (!clickedRow) return;

        const restSelect = document.getElementById(opts.formRestDaySelectId);
        const workSelect = document.getElementById(opts.formWorkDaySelectId);

        const workDays = DAY_ORDER.filter(d => effectiveByDay[d] && !effectiveByDay[d].is_rest_day);
        const restDays = DAY_ORDER.filter(d => effectiveByDay[d] && effectiveByDay[d].is_rest_day);

        if (workDays.length === 0 || restDays.length === 0) {
            Swal.fire('Not available', 'Need at least one work day and one rest day in this period to swap.', 'info');
            return;
        }

        restSelect.innerHTML = workDays.map(d => `<option value="${d}">${DAY_NAMES_FULL[d]}</option>`).join('');
        workSelect.innerHTML = restDays.map(d => `<option value="${d}">${DAY_NAMES_FULL[d]}</option>`).join('');

        // Pre-select based on which day was clicked, so the common case
        // (click the day you want to rest) needs one fewer choice.
        if (!clickedRow.is_rest_day) {
            restSelect.value = clickedDay;
        } else {
            workSelect.value = clickedDay;
        }

        document.getElementById(opts.formReasonId).value = '';
        document.getElementById(opts.formContainerId).style.display = 'block';
    }

    function closeForm() {
        const el = document.getElementById(opts.formContainerId);
        if (el) el.style.display = 'none';
    }

    function revertDay(day, btn) {
        const userId = opts.getCurrentUserId();
        const periodKey = currentPeriodKey();
        if (btn) btn.disabled = true;

        fetch('?page=api_revert_schedule_swap', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, period_key: periodKey, day_of_week: day })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    loadForCurrentEmployee();
                } else {
                    Swal.fire('Error', res.message || 'Failed to revert', 'error');
                    if (btn) btn.disabled = false;
                }
            });
    }

    function saveSwap() {
        const userId = opts.getCurrentUserId();
        if (!userId) return;
        const periodKey = currentPeriodKey();
        const restDay = document.getElementById(opts.formRestDaySelectId).value;
        const workDay = document.getElementById(opts.formWorkDaySelectId).value;
        const reason = (document.getElementById(opts.formReasonId).value || '').trim();

        if (!reason) {
            Swal.fire('Reason required', 'Please explain why this schedule is changing.', 'warning');
            return;
        }
        if (restDay === workDay) {
            Swal.fire('Pick two different days', '', 'warning');
            return;
        }

        const saveBtn = document.getElementById(opts.formSaveBtnId);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch('?page=api_swap_schedule_rest_day', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, period_key: periodKey, rest_day: restDay, work_day: workDay, reason })
        })
            .then(r => r.json())
            .then(res => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Swap';
                if (res.success) {
                    closeForm();
                    loadForCurrentEmployee();
                    if (typeof opts.onSaved === 'function') opts.onSaved();
                } else {
                    Swal.fire('Error', res.message || 'Failed to save', 'error');
                }
            })
            .catch(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Swap';
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
    }

    return { init, reload: loadForCurrentEmployee };
})();
