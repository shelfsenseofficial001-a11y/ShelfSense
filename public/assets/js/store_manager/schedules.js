// ============================================
// STORE MANAGER SCHEDULES - Front Department only
// ============================================

let currentEmployeeId = null;
let smAllEmployees = [];

function smLoadEmployeeList() {
    const tbody = document.getElementById('smEmployeeListBody');
    tbody.innerHTML = '<tr><td colspan="2" class="text-center py-2"><span class="spinner-border spinner-border-sm"></span></td></tr>';

    fetch('?page=api_sm_get_front_department_employees')
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Failed to load employees</td></tr>';
                return;
            }
            smAllEmployees = data.data.employees || [];
            smRenderEmployeeList(smAllEmployees);
            if (!currentEmployeeId && smAllEmployees.length > 0) {
                smSelectEmployee(smAllEmployees[0].user_id);
            }
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Error loading employees</td></tr>';
        });
}

function smRenderEmployeeList(employees) {
    const tbody = document.getElementById('smEmployeeListBody');
    if (!employees.length) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No Front Department staff found</td></tr>';
        return;
    }
    tbody.innerHTML = employees.map(emp => `
        <tr class="sm-employee-row" data-user-id="${emp.user_id}">
            <td>${smEscapeHtml(emp.first_name)} ${smEscapeHtml(emp.last_name)}
                <small class="text-muted d-block">${smEscapeHtml(emp.employee_number || '')}</small>
            </td>
            <td><span class="badge bg-info">${emp.role === 'trainee' ? 'Trainee' : 'Cashier'}</span></td>
        </tr>
    `).join('');

    tbody.querySelectorAll('.sm-employee-row').forEach(row => {
        row.addEventListener('click', function () { smSelectEmployee(this.dataset.userId); });
    });
    smHighlightActiveRow();
}

function smHighlightActiveRow() {
    document.querySelectorAll('.sm-employee-row').forEach(row => {
        row.classList.toggle('active', String(row.dataset.userId) === String(currentEmployeeId));
    });
}

function smSelectEmployee(userId) {
    currentEmployeeId = userId;
    smHighlightActiveRow();
    document.getElementById('smSyncScheduleBtn').style.display = 'inline-block';
    smLoadContractInfo(userId);
    if (window.ScheduleOverrides) ScheduleOverrides.reload();
}

function smLoadContractInfo(userId) {
    const el = document.getElementById('smContractInfoContent');
    el.innerHTML = '<p class="text-muted small mb-0"><span class="spinner-border spinner-border-sm"></span> Loading contract...</p>';

    fetch(`?page=api_get_employee_contract&user_id=${userId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data.has_contract) {
                const c = data.data.contract;
                el.innerHTML = `
                    <div class="small">
                        <div><strong>Shift:</strong> ${smEscapeHtml(c.shift_label || 'N/A')} (${smEscapeHtml(c.shift_time || 'N/A')})</div>
                        <div><strong>Rest Days:</strong> ${smEscapeHtml(c.rest_days || 'N/A')}</div>
                    </div>
                `;
            } else {
                el.innerHTML = '<p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No active contract found for this employee.</p>';
            }
        })
        .catch(() => {
            el.innerHTML = '<p class="text-muted small mb-0 text-danger">Error loading contract.</p>';
        });
}

function smSyncScheduleFromContract() {
    if (!currentEmployeeId) return;

    Swal.fire({
        title: 'Sync from Contract?',
        html: '<p>This will overwrite the standing schedule with the shift and rest days from this employee\'s active contract.</p><p class="text-muted small">This does not affect cutoff-specific changes below.</p>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Sync'
    }).then(result => {
        if (!result.isConfirmed) return;
        const btn = document.getElementById('smSyncScheduleBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Syncing...';

        fetch('?page=api_sync_schedule_from_contract', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: currentEmployeeId })
        })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync from Contract';
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Synced', timer: 1500, showConfirmButton: false });
                    if (window.ScheduleOverrides) ScheduleOverrides.reload();
                } else {
                    Swal.fire('Sync Failed', data.message || 'Please try again.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync from Contract';
                Swal.fire('Error', 'Something went wrong.', 'error');
            });
    });
}

function smEscapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function () {
    ScheduleOverrides.init({
        periodSelectId: 'periodSelect',
        calendarGridId: 'scheduleCalendarGrid',
        formContainerId: 'overrideForm',
        formTitleId: 'overrideFormTitle',
        formTimeInId: 'overrideFormTimeIn',
        formTimeOutId: 'overrideFormTimeOut',
        formReasonId: 'overrideFormReason',
        formSaveBtnId: 'overrideFormSaveBtn',
        formCancelBtnId: 'overrideFormCancelBtn',
        restEditBtnId: 'restEditBtn',
        restStatusId: 'restEditStatus',
        restStatusTextId: 'restEditStatusText',
        restStatusActionsId: 'restEditStatusActions',
        restReasonId: 'restEditReason',
        restSaveBtnId: 'restEditSaveBtn',
        restCancelBtnId: 'restEditCancelBtn',
        changesListId: 'scheduleChangesList',
        emptyMessage: 'Select an employee to view.',
        getCurrentUserId: () => currentEmployeeId
    });

    document.getElementById('smSyncScheduleBtn').addEventListener('click', smSyncScheduleFromContract);
    document.getElementById('smRefreshEmployeesBtn').addEventListener('click', smLoadEmployeeList);
    document.getElementById('smEmployeeSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        if (q.length < 2) {
            smRenderEmployeeList(smAllEmployees);
            return;
        }
        smRenderEmployeeList(smAllEmployees.filter(e =>
            (e.first_name || '').toLowerCase().includes(q) ||
            (e.last_name || '').toLowerCase().includes(q) ||
            (e.employee_number || '').toLowerCase().includes(q)
        ));
    });

    smLoadEmployeeList();
});
