// public/assets/js/shared/schedule-overrides.js
// Calendar view of one employee's effective schedule for a cutoff period,
// shared by HR's and Store Manager's Schedules pages. Each date cell shows
// that day's Time In/Out (or Rest Day) and, if it deviates from the
// standing schedule, the reason.
//
// Two edit modes, mutually exclusive:
// - Normal mode: clicking a cell opens a direct Time In/Out editor for
//   that single date (a reason is required).
// - Rest Day edit mode (toggled via the header button): clicking cells
//   picks a rest-day/work-day pair to swap. Removing a rest day always
//   means allocating it to another day in the same period -- there's no
//   way to just delete one, by design.
//
// Below the calendar, a change history lists every override made this
// period and who made it.
//
// Usage: ScheduleOverrides.init({
//   periodSelectId, calendarGridId,
//   formContainerId, formTitleId, formTimeInId, formTimeOutId,
//   formReasonId, formSaveBtnId, formCancelBtnId,
//   restEditBtnId, restStatusId, restStatusTextId, restStatusActionsId,
//   restReasonId, restSaveBtnId, restCancelBtnId,
//   changesListId,
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
    let editingDay = null;

    let restEditMode = false;
    let restPick = null; // { day, wasRest } -- the first day picked in the current swap

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

    function formatChangeTimestamp(ts) {
        if (!ts) return '';
        const d = new Date(ts.replace(' ', 'T'));
        if (isNaN(d.getTime())) return ts;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' +
            d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
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

        document.getElementById(opts.formSaveBtnId).addEventListener('click', saveDayEdit);
        document.getElementById(opts.formCancelBtnId).addEventListener('click', closeForm);
        closeForm();

        document.getElementById(opts.restEditBtnId).addEventListener('click', toggleRestEditMode);
        document.getElementById(opts.restSaveBtnId).addEventListener('click', saveRestSwap);
        document.getElementById(opts.restCancelBtnId).addEventListener('click', cancelRestPick);
        setRestEditMode(false);
    }

    function currentPeriodKey() {
        return document.getElementById(opts.periodSelectId).value;
    }

    function loadForCurrentEmployee() {
        const userId = opts.getCurrentUserId();
        const gridEl = document.getElementById(opts.calendarGridId);
        closeForm();
        cancelRestPick();

        if (!userId) {
            gridEl.innerHTML = `<p class="text-muted small mb-0">${escapeHtml(opts.emptyMessage || 'Select an employee to view.')}</p>`;
            effectiveByDay = {};
            renderChangesList([]);
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

        loadChangesList(userId, periodKey);
    }

    function loadChangesList(userId, periodKey) {
        const listEl = document.getElementById(opts.changesListId);
        if (!listEl) return;
        listEl.innerHTML = '<div class="text-center py-2"><span class="spinner-border spinner-border-sm"></span></div>';

        fetch(`?page=api_get_schedule_user_period_changes&user_id=${encodeURIComponent(userId)}&period_key=${encodeURIComponent(periodKey)}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    listEl.innerHTML = `<p class="text-danger small mb-0">${escapeHtml(res.message || 'Failed to load')}</p>`;
                    return;
                }
                renderChangesList(res.data.changes || []);
            });
    }

    function renderChangesList(changes) {
        const listEl = document.getElementById(opts.changesListId);
        if (!listEl) return;

        if (!changes.length) {
            listEl.innerHTML = '<p class="text-muted small mb-0">No changes made this cutoff period.</p>';
            return;
        }

        listEl.innerHTML = changes.map(c => {
            const isRest = c.is_rest_day == 1;
            const timeLabel = isRest ? 'Rest Day' : `${(c.time_in || '').slice(0, 5)}–${(c.time_out || '').slice(0, 5)}`;
            const who = c.changed_by_name || 'Unknown';
            return `
                <div class="sched-change-row d-flex justify-content-between align-items-start">
                    <div>
                        <div><strong>${DAY_NAMES_FULL[c.day_of_week] || c.day_of_week}</strong> &mdash; ${escapeHtml(timeLabel)}</div>
                        ${c.reason ? `<div class="text-muted">${escapeHtml(c.reason)}</div>` : ''}
                        <div class="text-muted">By ${escapeHtml(who)} &middot; ${escapeHtml(formatChangeTimestamp(c.updated_at))}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link p-0 text-danger sched-change-revert-btn" data-day="${c.day_of_week}" title="Revert to standing schedule">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            `;
        }).join('');

        listEl.querySelectorAll('.sched-change-revert-btn').forEach(btn => {
            btn.addEventListener('click', function () { revertDay(this.dataset.day, this); });
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
    }

    function renderCell(dateStr) {
        if (!dateStr) return '<div class="sched-cal-cell sched-cal-cell-blank"></div>';

        const day = dayOfWeekFor(dateStr);
        const row = effectiveByDay[day];
        const dateNum = parseInt(dateStr.slice(8, 10), 10);
        const disabled = isRestEditDisabled(day, row) ? 'sched-cal-cell-disabled' : '';

        if (!row) {
            return `<div class="sched-cal-cell ${disabled}" data-day="${day}"><div class="sched-cal-date">${dateNum}</div><div class="small text-muted">No baseline set</div></div>`;
        }

        const isRest = row.is_rest_day == 1;
        const timeLabel = isRest ? 'Rest Day' : `${(row.time_in || '').slice(0, 5)}–${(row.time_out || '').slice(0, 5)}`;
        const changedBadge = row.is_override ? '<span class="badge bg-warning text-dark sched-cal-badge">Changed</span>' : '';
        const isPicked = restEditMode && restPick && (day === restPick.day || day === restPick.pairedWith);

        return `
            <div class="sched-cal-cell ${row.is_override ? 'sched-cal-cell-changed' : ''} ${isRest ? 'sched-cal-cell-rest' : ''} ${isPicked ? 'sched-cal-cell-selected' : ''} ${disabled}" data-day="${day}">
                <div class="sched-cal-date">${dateNum}</div>
                <div class="sched-cal-time">${escapeHtml(timeLabel)}</div>
                ${changedBadge}
            </div>
        `;
    }

    function onCellClick(day) {
        if (restEditMode) {
            onRestEditCellClick(day);
        } else {
            openForm(day);
        }
    }

    // ============================================
    // NORMAL MODE: direct Time In/Out edit for one date
    // ============================================

    function openForm(day) {
        const row = effectiveByDay[day];
        if (!row) return;

        editingDay = day;
        document.getElementById(opts.formTitleId).textContent = 'Edit ' + DAY_NAMES_FULL[day];
        document.getElementById(opts.formTimeInId).value = row.is_rest_day ? '' : (row.time_in || '').slice(0, 5);
        document.getElementById(opts.formTimeOutId).value = row.is_rest_day ? '' : (row.time_out || '').slice(0, 5);
        document.getElementById(opts.formReasonId).value = '';
        document.getElementById(opts.formContainerId).style.display = 'block';
    }

    function closeForm() {
        editingDay = null;
        const el = document.getElementById(opts.formContainerId);
        if (el) el.style.display = 'none';
    }

    function saveDayEdit() {
        const userId = opts.getCurrentUserId();
        if (!userId || !editingDay) return;
        const periodKey = currentPeriodKey();
        const timeIn = document.getElementById(opts.formTimeInId).value;
        const timeOut = document.getElementById(opts.formTimeOutId).value;
        const reason = (document.getElementById(opts.formReasonId).value || '').trim();

        if (!timeIn || !timeOut) {
            Swal.fire('Missing time', 'Please set both Time In and Time Out.', 'warning');
            return;
        }
        if (!reason) {
            Swal.fire('Reason required', 'Please explain why this schedule is changing.', 'warning');
            return;
        }

        const saveBtn = document.getElementById(opts.formSaveBtnId);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch('?page=api_save_schedule_day_override', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, period_key: periodKey, day_of_week: editingDay, time_in: timeIn, time_out: timeOut, reason })
        })
            .then(r => r.json())
            .then(res => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
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
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
    }

    // ============================================
    // REST DAY EDIT MODE: pick a rest day + a work day to swap. Removing a
    // rest day always means allocating it to another day in this same
    // period -- there's no way to just delete one.
    // ============================================

    function toggleRestEditMode() {
        setRestEditMode(!restEditMode);
    }

    function setRestEditMode(on) {
        restEditMode = on;
        closeForm();
        cancelRestPick();

        const btn = document.getElementById(opts.restEditBtnId);
        if (on) {
            btn.classList.add('active');
            btn.innerHTML = '<i class="bi bi-check2"></i> Done';
        } else {
            btn.classList.remove('active');
            btn.innerHTML = '<i class="bi bi-arrow-left-right"></i> Edit Rest Days';
        }

        const statusEl = document.getElementById(opts.restStatusId);
        if (statusEl) statusEl.style.display = on ? 'flex' : 'none';
        if (on) setRestStatusText('Pick a day to change its rest/work status.');

        renderCalendar();
    }

    function setRestStatusText(text) {
        const el = document.getElementById(opts.restStatusTextId);
        if (el) el.textContent = text;
    }

    // Whether a cell can be clicked right now in rest-edit mode. Used both
    // to guard clicks and to dim/disable invalid targets so the valid next
    // move is visually obvious instead of something you have to read the
    // banner text to figure out.
    function isRestEditDisabled(day, row) {
        if (!restEditMode || !restPick) return false;
        if (restPick.restDay) {
            // Pair already complete -- freeze everything except the two
            // picked cells until Save or Cancel resolves it.
            return day !== restPick.restDay && day !== restPick.workDay;
        }
        if (day === restPick.day) return false;
        if (!row) return true;
        const isRest = row.is_rest_day == 1;
        return isRest === restPick.wasRest;
    }

    function updateCancelBtnVisibility() {
        const btn = document.getElementById(opts.restCancelBtnId);
        if (btn) btn.style.display = restPick ? 'inline-block' : 'none';
    }

    function onRestEditCellClick(day) {
        const row = effectiveByDay[day];
        if (!row) return;
        if (isRestEditDisabled(day, row)) return;
        const isRest = row.is_rest_day == 1;

        if (!restPick) {
            restPick = { day, wasRest: isRest };
            renderCalendar();
            updateCancelBtnVisibility();
            if (isRest) {
                setRestStatusText(`${DAY_NAMES_FULL[day]} will become a work day. Rest day not yet allocated -- select which day becomes the new Rest Day.`);
            } else {
                setRestStatusText(`${DAY_NAMES_FULL[day]} will become the Rest Day. Select which currently-resting day becomes a work day instead.`);
            }
            return;
        }

        if (restPick.day === day) {
            // Clicked the same cell again -- cancel that pick.
            cancelRestPick();
            return;
        }

        // Reaching here means `day` is guaranteed to be a valid opposite-
        // type target (invalid ones were already filtered out above), and
        // this is the second pick since a completed pair disables every
        // other cell. Resolve which is becoming rest vs. work, regardless
        // of click order.
        const restDay = restPick.wasRest ? day : restPick.day;
        const workDay = restPick.wasRest ? restPick.day : day;

        restPick = { day, wasRest: isRest, pairedWith: restPick.day, restDay, workDay };
        renderCalendar();
        setRestStatusText(`${DAY_NAMES_FULL[restDay]} becomes Rest Day, ${DAY_NAMES_FULL[workDay]} becomes a work day. Add a reason to confirm.`);

        // Bootstrap's .d-flex utility class is !important, so a plain
        // inline style.display assignment can't hide/show this element --
        // it needs its own !important to win.
        const actionsEl = document.getElementById(opts.restStatusActionsId);
        if (actionsEl) actionsEl.style.setProperty('display', 'flex', 'important');
        document.getElementById(opts.restReasonId).value = '';
    }

    function cancelRestPick() {
        restPick = null;
        const actionsEl = document.getElementById(opts.restStatusActionsId);
        if (actionsEl) actionsEl.style.setProperty('display', 'none', 'important');
        updateCancelBtnVisibility();
        if (restEditMode) setRestStatusText('Pick a day to change its rest/work status.');
        if (currentPeriod) renderCalendar();
    }

    function saveRestSwap() {
        const userId = opts.getCurrentUserId();
        if (!userId || !restPick || !restPick.restDay || !restPick.workDay) return;
        const periodKey = currentPeriodKey();
        const reason = (document.getElementById(opts.restReasonId).value || '').trim();

        if (!reason) {
            Swal.fire('Reason required', 'Please explain why this schedule is changing.', 'warning');
            return;
        }

        const saveBtn = document.getElementById(opts.restSaveBtnId);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch('?page=api_swap_schedule_rest_day', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, period_key: periodKey, rest_day: restPick.restDay, work_day: restPick.workDay, reason })
        })
            .then(r => r.json())
            .then(res => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
                if (res.success) {
                    cancelRestPick();
                    loadForCurrentEmployee();
                    if (typeof opts.onSaved === 'function') opts.onSaved();
                } else {
                    Swal.fire('Error', res.message || 'Failed to save', 'error');
                }
            })
            .catch(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save';
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
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

    return { init, reload: loadForCurrentEmployee };
})();
