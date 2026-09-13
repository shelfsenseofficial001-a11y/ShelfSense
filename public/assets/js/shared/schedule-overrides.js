// public/assets/js/shared/schedule-overrides.js
// "Cutoff Schedule Changes" panel shared by HR's and Store Manager's
// Schedules pages. The standing/baseline schedule (separate grid, separate
// endpoints) is untouched by this -- this only ever reads/writes
// schedule_overrides for one specific H1/H2 cutoff period at a time.
//
// Usage: ScheduleOverrides.init({
//   periodSelectId, tableBodyId, reasonInputId, saveBtnId, resetBtnId,
//   emptyMessage, getCurrentUserId: () => currentEmployeeId
// });

const ScheduleOverrides = (function () {
    const DAY_NAMES = {
        monday: 'Monday', tuesday: 'Tuesday', wednesday: 'Wednesday', thursday: 'Thursday',
        friday: 'Friday', saturday: 'Saturday', sunday: 'Sunday'
    };
    const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    let opts = null;
    let loadedSchedule = {}; // day -> original effective row, to diff against on save

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
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
                loadForCurrentEmployee();
            });

        periodSelect.addEventListener('change', loadForCurrentEmployee);
        document.getElementById(opts.saveBtnId).addEventListener('click', saveChanges);
        if (opts.resetBtnId) {
            document.getElementById(opts.resetBtnId).addEventListener('click', loadForCurrentEmployee);
        }
    }

    function currentPeriodKey() {
        return document.getElementById(opts.periodSelectId).value;
    }

    function loadForCurrentEmployee() {
        const userId = opts.getCurrentUserId();
        const tbody = document.getElementById(opts.tableBodyId);
        if (!userId) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">${escapeHtml(opts.emptyMessage || 'Select an employee to view.')}</td></tr>`;
            return;
        }
        const periodKey = currentPeriodKey();
        if (!periodKey) return;

        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>';

        fetch(`?page=api_get_effective_schedule&user_id=${encodeURIComponent(userId)}&period_key=${encodeURIComponent(periodKey)}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">${escapeHtml(res.message || 'Failed to load')}</td></tr>`;
                    return;
                }
                loadedSchedule = {};
                (res.data.schedule || []).forEach(row => { loadedSchedule[row.day_of_week] = row; });
                render();
            });
    }

    function render() {
        const tbody = document.getElementById(opts.tableBodyId);
        tbody.innerHTML = DAY_ORDER.map(day => {
            const row = loadedSchedule[day] || { time_in: '', time_out: '', is_rest_day: 0, is_override: false };
            const isRest = row.is_rest_day == 1;
            return `
                <tr data-day="${day}" class="${row.is_override ? 'table-warning' : ''}">
                    <td>${DAY_NAMES[day]}</td>
                    <td><input type="time" class="form-control form-control-sm so-time-in" value="${isRest ? '' : (row.time_in || '').slice(0, 5)}" ${isRest ? 'disabled' : ''}></td>
                    <td><input type="time" class="form-control form-control-sm so-time-out" value="${isRest ? '' : (row.time_out || '').slice(0, 5)}" ${isRest ? 'disabled' : ''}></td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input so-rest-day" ${isRest ? 'checked' : ''}>
                    </td>
                    <td class="text-center">${row.is_override ? '<span class="badge bg-warning text-dark">Changed</span>' : '<span class="text-muted small">Standard</span>'}</td>
                    <td class="text-center">
                        ${row.is_override ? `<button type="button" class="btn btn-sm btn-outline-secondary so-revert-btn" data-day="${day}"><i class="bi bi-arrow-counterclockwise"></i> Revert</button>` : ''}
                    </td>
                </tr>
            `;
        }).join('');

        tbody.querySelectorAll('.so-rest-day').forEach(cb => {
            cb.addEventListener('change', function () {
                const row = this.closest('tr');
                row.querySelector('.so-time-in').disabled = this.checked;
                row.querySelector('.so-time-out').disabled = this.checked;
            });
        });

        tbody.querySelectorAll('.so-revert-btn').forEach(btn => {
            btn.addEventListener('click', function () { revertDay(this.dataset.day, this); });
        });
    }

    function revertDay(day, btn) {
        const userId = opts.getCurrentUserId();
        const periodKey = currentPeriodKey();
        if (btn) btn.disabled = true;

        fetch('?page=api_delete_schedule_override', {
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

    function saveChanges() {
        const userId = opts.getCurrentUserId();
        if (!userId) {
            Swal.fire('No employee selected', 'Please select an employee first.', 'warning');
            return;
        }
        const periodKey = currentPeriodKey();
        const reason = opts.reasonInputId ? (document.getElementById(opts.reasonInputId).value || '').trim() : '';

        const tbody = document.getElementById(opts.tableBodyId);
        const changedRows = [];

        tbody.querySelectorAll('tr[data-day]').forEach(tr => {
            const day = tr.dataset.day;
            const isRestDay = tr.querySelector('.so-rest-day').checked ? 1 : 0;
            const timeIn = tr.querySelector('.so-time-in').value;
            const timeOut = tr.querySelector('.so-time-out').value;
            const original = loadedSchedule[day] || {};
            const originalTimeIn = (original.time_in || '').slice(0, 5);
            const originalTimeOut = (original.time_out || '').slice(0, 5);
            const originalRest = original.is_rest_day == 1 ? 1 : 0;

            const differs = isRestDay !== originalRest || (!isRestDay && (timeIn !== originalTimeIn || timeOut !== originalTimeOut));
            if (differs) {
                if (!isRestDay && (!timeIn || !timeOut)) {
                    return;
                }
                changedRows.push({ day, isRestDay, timeIn, timeOut });
            }
        });

        if (changedRows.length === 0) {
            Swal.fire('No changes', 'Nothing was changed from the current schedule for this period.', 'info');
            return;
        }

        const saveBtn = document.getElementById(opts.saveBtnId);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        Promise.all(changedRows.map(row => fetch('?page=api_save_schedule_override', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                period_key: periodKey,
                day_of_week: row.day,
                time_in: row.isRestDay ? '' : row.timeIn,
                time_out: row.isRestDay ? '' : row.timeOut,
                is_rest_day: row.isRestDay,
                reason: reason || null
            })
        }).then(r => r.json())))
            .then(results => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
                const failed = results.filter(r => !r.success);
                if (failed.length === 0) {
                    Swal.fire({ icon: 'success', title: 'Saved', text: 'Schedule changes recorded for this cutoff.', timer: 1500, showConfirmButton: false });
                    if (opts.reasonInputId) document.getElementById(opts.reasonInputId).value = '';
                    loadForCurrentEmployee();
                    if (typeof opts.onSaved === 'function') opts.onSaved();
                } else {
                    Swal.fire('Some changes failed', failed.map(f => f.message).join(', '), 'error');
                }
            })
            .catch(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
    }

    return { init, reload: loadForCurrentEmployee };
})();
