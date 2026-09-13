// public/assets/js/shared/schedule-overrides.js
// "Cutoff Schedule Changes" panel shared by HR's and Store Manager's
// Schedules pages. The standing/baseline schedule (separate grid, separate
// endpoints) is untouched by this -- this only ever reads/writes
// schedule_overrides for one specific H1/H2 cutoff period at a time.
//
// Shows a short list of actual changes for the selected employee+period
// (empty by default -- most employees/periods have none), not a second
// full 7-day grid mirroring the baseline. "Add Change" opens a small form
// to pick one day and set its override.
//
// Usage: ScheduleOverrides.init({
//   periodSelectId, listContainerId, addBtnId, formContainerId,
//   formDaySelectId, formTimeInId, formTimeOutId, formRestDayId,
//   formReasonId, formSaveBtnId, formCancelBtnId,
//   getCurrentUserId: () => currentEmployeeId
// });

const ScheduleOverrides = (function () {
    const DAY_NAMES = {
        monday: 'Monday', tuesday: 'Tuesday', wednesday: 'Wednesday', thursday: 'Thursday',
        friday: 'Friday', saturday: 'Saturday', sunday: 'Sunday'
    };
    const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    let opts = null;
    let currentChanges = []; // overrides only, for the selected employee+period
    let editingDay = null;

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
        document.getElementById(opts.addBtnId).addEventListener('click', () => openForm(null));
        document.getElementById(opts.formSaveBtnId).addEventListener('click', saveForm);
        document.getElementById(opts.formCancelBtnId).addEventListener('click', closeForm);
        document.getElementById(opts.formRestDayId).addEventListener('change', function () {
            document.getElementById(opts.formTimeInId).disabled = this.checked;
            document.getElementById(opts.formTimeOutId).disabled = this.checked;
        });

        closeForm();
    }

    function currentPeriodKey() {
        return document.getElementById(opts.periodSelectId).value;
    }

    function loadForCurrentEmployee() {
        const userId = opts.getCurrentUserId();
        const listEl = document.getElementById(opts.listContainerId);
        closeForm();
        document.getElementById(opts.addBtnId).disabled = !userId;

        if (!userId) {
            listEl.innerHTML = `<p class="text-muted small mb-0">${escapeHtml(opts.emptyMessage || 'Select an employee to view.')}</p>`;
            currentChanges = [];
            return;
        }
        const periodKey = currentPeriodKey();
        if (!periodKey) return;

        listEl.innerHTML = '<div class="text-center py-2"><span class="spinner-border spinner-border-sm"></span></div>';

        fetch(`?page=api_get_effective_schedule&user_id=${encodeURIComponent(userId)}&period_key=${encodeURIComponent(periodKey)}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    listEl.innerHTML = `<p class="text-danger small mb-0">${escapeHtml(res.message || 'Failed to load')}</p>`;
                    return;
                }
                currentChanges = (res.data.schedule || []).filter(row => row.is_override);
                render();
            });
    }

    function render() {
        const listEl = document.getElementById(opts.listContainerId);
        if (currentChanges.length === 0) {
            listEl.innerHTML = '<p class="text-muted small mb-0">No changes for this cutoff period -- following the standing schedule.</p>';
            return;
        }

        const rows = DAY_ORDER
            .map(day => currentChanges.find(c => c.day_of_week === day))
            .filter(Boolean);

        listEl.innerHTML = rows.map(row => {
            const isRest = row.is_rest_day == 1;
            const timeLabel = isRest ? 'Rest day' : `${(row.time_in || '').slice(0, 5)} - ${(row.time_out || '').slice(0, 5)}`;
            return `
                <div class="d-flex justify-content-between align-items-start border rounded p-2 mb-2" data-day="${row.day_of_week}">
                    <div>
                        <div class="fw-semibold">${DAY_NAMES[row.day_of_week]} <span class="badge bg-warning text-dark ms-1">Changed</span></div>
                        <div class="small">${escapeHtml(timeLabel)}</div>
                        ${row.reason ? `<div class="small text-muted fst-italic">${escapeHtml(row.reason)}</div>` : ''}
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary so-edit-btn" data-day="${row.day_of_week}" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger so-revert-btn" data-day="${row.day_of_week}" title="Revert to standard"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </div>
                </div>
            `;
        }).join('');

        listEl.querySelectorAll('.so-edit-btn').forEach(btn => {
            btn.addEventListener('click', function () { openForm(this.dataset.day); });
        });
        listEl.querySelectorAll('.so-revert-btn').forEach(btn => {
            btn.addEventListener('click', function () { revertDay(this.dataset.day, this); });
        });
    }

    function openForm(day) {
        editingDay = day;
        const daySelect = document.getElementById(opts.formDaySelectId);
        daySelect.innerHTML = DAY_ORDER.map(d => `<option value="${d}">${DAY_NAMES[d]}</option>`).join('');

        const existing = day ? currentChanges.find(c => c.day_of_week === day) : null;
        if (existing) {
            daySelect.value = day;
            daySelect.disabled = true;
            document.getElementById(opts.formRestDayId).checked = existing.is_rest_day == 1;
            document.getElementById(opts.formTimeInId).value = (existing.time_in || '').slice(0, 5);
            document.getElementById(opts.formTimeOutId).value = (existing.time_out || '').slice(0, 5);
            document.getElementById(opts.formReasonId).value = existing.reason || '';
        } else {
            daySelect.disabled = false;
            document.getElementById(opts.formRestDayId).checked = false;
            document.getElementById(opts.formTimeInId).value = '';
            document.getElementById(opts.formTimeOutId).value = '';
            document.getElementById(opts.formReasonId).value = '';
        }
        document.getElementById(opts.formTimeInId).disabled = document.getElementById(opts.formRestDayId).checked;
        document.getElementById(opts.formTimeOutId).disabled = document.getElementById(opts.formRestDayId).checked;

        document.getElementById(opts.formContainerId).style.display = 'block';
        document.getElementById(opts.addBtnId).style.display = 'none';
    }

    function closeForm() {
        editingDay = null;
        const formEl = document.getElementById(opts.formContainerId);
        if (formEl) formEl.style.display = 'none';
        const addBtn = document.getElementById(opts.addBtnId);
        if (addBtn) addBtn.style.display = 'inline-block';
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

    function saveForm() {
        const userId = opts.getCurrentUserId();
        if (!userId) return;
        const periodKey = currentPeriodKey();
        const day = document.getElementById(opts.formDaySelectId).value;
        const isRestDay = document.getElementById(opts.formRestDayId).checked ? 1 : 0;
        const timeIn = document.getElementById(opts.formTimeInId).value;
        const timeOut = document.getElementById(opts.formTimeOutId).value;
        const reason = (document.getElementById(opts.formReasonId).value || '').trim();

        if (!isRestDay && (!timeIn || !timeOut)) {
            Swal.fire('Missing time', 'Set Time In and Time Out, or mark it a rest day.', 'warning');
            return;
        }

        const saveBtn = document.getElementById(opts.formSaveBtnId);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch('?page=api_save_schedule_override', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                period_key: periodKey,
                day_of_week: day,
                time_in: isRestDay ? '' : timeIn,
                time_out: isRestDay ? '' : timeOut,
                is_rest_day: isRestDay,
                reason: reason || null
            })
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

    return { init, reload: loadForCurrentEmployee };
})();
