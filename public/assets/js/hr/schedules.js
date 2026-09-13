// ============================================
// HR SCHEDULES - FULL AJAX
// ============================================

let currentEmployeeId = null;
let allEmployees = [];

// ============================================
// SELECT EMPLOYEE
// ============================================

function selectEmployee(userId) {
    if (!userId) {
        document.getElementById('contractInfoContent').innerHTML = `<p class="text-muted small mb-0">Select an employee to view contract details.</p>`;
        updateSyncButtonVisibility(null);
        highlightActiveEmployeeRow(null);
        return;
    }

    currentEmployeeId = userId;
    updateSyncButtonVisibility(userId);
    updateEmployeeInfo(userId);
    loadContractInfo(userId);
    if (window.ScheduleOverrides) ScheduleOverrides.reload();
}

// ============================================
// LOAD CONTRACT INFO
// ============================================

function loadContractInfo(userId) {
    const contractDiv = document.getElementById('contractInfoContent');
    contractDiv.innerHTML = `<p class="text-muted small mb-0"><i class="bi bi-arrow-repeat spinner-border-sm"></i> Loading contract...</p>`;

    fetch(`?page=api_get_employee_contract&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.has_contract) {
                const contract = data.data.contract;
                const shiftLabel = contract.shift_label || 'N/A';
                const shiftTime = contract.shift_time || 'N/A';
                const salary = formatCurrency(contract.salary);
                const startDate = contract.start_date ? new Date(contract.start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
                const role = contract.target_role || 'N/A';

                contractDiv.innerHTML = `
                    <div class="contract-info-card" onclick="openContractDetail(${userId})">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="contract-shift">${shiftLabel}</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="small mt-2">
                            <div><strong>Role:</strong> ${role}</div>
                            <div><strong>Shift Hours:</strong> ${shiftTime}</div>
                            <div><strong>Salary:</strong> ₱${salary}</div>
                            <div><strong>Start Date:</strong> ${startDate}</div>
                        </div>
                        <div class="mt-2">
                            <span class="text-primary small"><i class="bi bi-eye"></i> Click to view full contract</span>
                        </div>
                    </div>
                `;
            } else {
                contractDiv.innerHTML = `
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i> No active contract found for this employee.
                    </p>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Contract fetch error:', error);
            contractDiv.innerHTML = `<p class="text-muted small mb-0 text-danger">Error loading contract.</p>`;
        });
}

// ============================================
// OPEN CONTRACT DETAIL MODAL
// ============================================

function openContractDetail(userId) {
    const modal = document.getElementById('contractDetailModal');
    const body = document.getElementById('contractDetailBody');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    bootstrap.Offcanvas.getOrCreateInstance(modal).show();

    fetch(`?page=api_get_employee_contract&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.has_contract) {
                const c = data.data.contract;
                const shiftLabel = c.shift_label || 'N/A';
                const shiftTime = c.shift_time || 'N/A';
                const salary = formatCurrency(c.salary);
                const startDate = c.start_date ? new Date(c.start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
                const role = c.target_role || 'N/A';
                const jobDetails = c.job_details || 'No additional details.';

                body.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Employee:</strong> ${c.first_name} ${c.last_name}</p>
                            <p><strong>Role:</strong> ${role}</p>
                            <p><strong>Shift:</strong> ${shiftLabel} (${shiftTime})</p>
                            <p><strong>Salary:</strong> ₱${salary}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Start Date:</strong> ${startDate}</p>
                            <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                            <p><strong>Contract ID:</strong> #${c.id}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p><strong>Job Details:</strong></p>
                        <div class="p-3 rounded" style="background: var(--bg-card-subtle); color: var(--text-main);">${escapeHtml(jobDetails)}</div>
                    </div>
                `;
            } else {
                body.innerHTML = `<div class="text-center text-muted py-4">No contract details available.</div>`;
            }
        })
        .catch(error => {
            body.innerHTML = `<div class="text-center text-danger py-4">Error loading contract details.</div>`;
        });
}

// ============================================
// UPDATE EMPLOYEE INFO / ACTIVE ROW
// ============================================

function updateEmployeeInfo(userId) {
    highlightActiveEmployeeRow(userId);
}

function highlightActiveEmployeeRow(userId) {
    document.querySelectorAll('.employee-row').forEach(function(row) {
        row.classList.toggle('active', String(row.dataset.userId) === String(userId));
    });
}

// ============================================
// SYNC SCHEDULE FROM CONTRACT
// ============================================

function syncScheduleFromContract() {
    if (!currentEmployeeId) {
        Swal.fire({
            icon: 'warning',
            title: 'No Employee Selected',
            text: 'Please select an employee first.'
        });
        return;
    }

    Swal.fire({
        title: 'Sync from Contract?',
        html: `
            <p>This will overwrite the standing schedule with the shift and rest days from this employee's active contract.</p>
            <p class="text-muted small">This does not affect cutoff-specific changes below.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Sync',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            const btn = document.getElementById('syncScheduleBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Syncing...';

            fetch('?page=api_sync_schedule_from_contract', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentEmployeeId })
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync from Contract';
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Synced Successfully!',
                        text: data.data.message || 'Schedule updated from contract.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    if (window.ScheduleOverrides) ScheduleOverrides.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Sync Failed',
                        text: data.message || 'Please try again.'
                    });
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync from Contract';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong. Please try again.'
                });
            });
        }
    });
}

// ============================================
// UPDATE SYNC BUTTON VISIBILITY
// ============================================

function updateSyncButtonVisibility(employeeId) {
    const btn = document.getElementById('syncScheduleBtn');
    if (employeeId) {
        btn.style.display = 'inline-block';
    } else {
        btn.style.display = 'none';
    }
}

// ============================================
// EMPLOYEE LIST
// ============================================

function loadEmployeeList() {
    const tbody = document.getElementById('employeeListBody');
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-2"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>`;

    fetch('?page=api_get_all_employees')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allEmployees = data.data.employees || [];
                renderEmployeeList(allEmployees);
                if (!currentEmployeeId && allEmployees.length > 0) {
                    selectEmployee(allEmployees[0].user_id);
                }
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Failed to load employees</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading employees:', error);
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Error loading employees</td></tr>`;
        });
}

function renderEmployeeList(employees) {
    const tbody = document.getElementById('employeeListBody');
    if (!employees || employees.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">No employees found</td></tr>`;
        return;
    }

    let html = '';
    employees.forEach(emp => {
        const displayRole = getRoleDisplayName(emp.role);
        html += `
            <tr class="employee-row" data-user-id="${emp.user_id}">
                <td>
                    ${escapeHtml(emp.first_name)} ${escapeHtml(emp.last_name)}
                    <small class="text-muted d-block">${escapeHtml(emp.employee_number || '')}</small>
                </td>
                <td><span class="badge bg-info">${displayRole}</span></td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    highlightActiveEmployeeRow(currentEmployeeId);

    document.querySelectorAll('.employee-row').forEach(row => {
        row.addEventListener('click', function() {
            selectEmployee(this.dataset.userId);
        });
    });
}

function formatCurrency(amount) {
    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function getRoleDisplayName(role) {
    const map = {
        'owner': 'Owner',
        'hr_head': 'HR Head',
        'hr_staff': 'HR Staff',
        'employee': 'Cashier',
        'finance_head': 'Finance Head',
        'finance_staff': 'Finance Staff',
        'trainee': 'Trainee',
        'store_manager': 'Store Manager',
        'supplier': 'Supplier'
    };
    return map[role] || role;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// EVENT LISTENERS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
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
        changesListId: 'scheduleChangesList',
        emptyMessage: 'Select an employee to view.',
        getCurrentUserId: () => currentEmployeeId
    });

    document.getElementById('syncScheduleBtn').addEventListener('click', syncScheduleFromContract);

    document.getElementById('refreshEmployeeListBtn').addEventListener('click', function() {
        loadEmployeeList();
    });

    document.getElementById('employeeSearch').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        if (query.length < 2) {
            renderEmployeeList(allEmployees);
            return;
        }
        const filtered = allEmployees.filter(emp =>
            (emp.first_name || '').toLowerCase().includes(query) ||
            (emp.last_name || '').toLowerCase().includes(query) ||
            (emp.employee_number || '').toLowerCase().includes(query) ||
            getRoleDisplayName(emp.role).toLowerCase().includes(query)
        );
        renderEmployeeList(filtered);
    });

    if (window.__INITIAL_DATA__) {
        allEmployees = window.__INITIAL_DATA__.employees || [];
        renderEmployeeList(allEmployees);
        if (!currentEmployeeId && allEmployees.length > 0) {
            selectEmployee(allEmployees[0].user_id);
        }
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        loadEmployeeList();
    }
});
