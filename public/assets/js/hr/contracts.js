// ============================================
// HR CONTRACTS - FULL AJAX
// ============================================

console.log('✅ contracts.js loaded');

let currentPage = 1;
let currentFilters = {
    status: 'all',
    search: ''
};

// Role salary ranges (fixed salary with min/max limits)
const ROLE_SALARY_RANGES = {
    'Cashier': { min: 9900, max: 10500 },
    'HR Staff': { min: 13800, max: 14700 },
    'Finance Staff': { min: 13800, max: 14700 },
    'Head HR': { min: 15000, max: 18600 },
    'Head Finance': { min: 15000, max: 18600 }
};

// ============================================
// REST DAY TOGGLES (CREATE)
// ============================================

let selectedRestDays = [];

document.querySelectorAll('.rest-day-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const day = this.dataset.day;
        const index = selectedRestDays.indexOf(day);
        if (index > -1) {
            selectedRestDays.splice(index, 1);
            this.classList.remove('btn-primary');
            this.classList.add('btn-outline-secondary');
        } else {
            if (selectedRestDays.length >= 2) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Reached',
                    text: 'You can only select 2 rest days.'
                });
                return;
            }
            selectedRestDays.push(day);
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary');
        }
        document.getElementById('contractRestDays').value = selectedRestDays.join(',');
    });
});

// Reset create modal rest days on open
document.getElementById('createContractModal').addEventListener('show.bs.modal', function() {
    selectedRestDays = [];
    document.querySelectorAll('.rest-day-toggle').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-secondary');
    });
    document.getElementById('contractRestDays').value = '';
});

// ============================================
// REST DAY TOGGLES (EDIT)
// ============================================

let editSelectedRestDays = [];

function initEditRestDays() {
    editSelectedRestDays = [];
    document.querySelectorAll('#editRestDaysContainer .edit-rest-day-toggle').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-secondary');
    });
    document.getElementById('editContractRestDays').value = '';
}

function loadEditRestDays(restDaysStr) {
    if (restDaysStr) {
        const days = restDaysStr.split(',').map(d => d.trim());
        days.forEach(day => {
            const btn = document.querySelector(`#editRestDaysContainer .edit-rest-day-toggle[data-day="${day}"]`);
            if (btn && editSelectedRestDays.length < 2) {
                editSelectedRestDays.push(day);
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-primary');
            }
        });
        document.getElementById('editContractRestDays').value = editSelectedRestDays.join(',');
    }
}

// Attach event listeners for edit modal rest day toggles
document.querySelectorAll('#editRestDaysContainer .edit-rest-day-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const day = this.dataset.day;
        const index = editSelectedRestDays.indexOf(day);
        if (index > -1) {
            editSelectedRestDays.splice(index, 1);
            this.classList.remove('btn-primary');
            this.classList.add('btn-outline-secondary');
        } else {
            if (editSelectedRestDays.length >= 2) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Reached',
                    text: 'You can only select 2 rest days.'
                });
                return;
            }
            editSelectedRestDays.push(day);
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary');
        }
        document.getElementById('editContractRestDays').value = editSelectedRestDays.join(',');
    });
});

// Reset edit modal rest days when modal opens (after data is loaded)
document.getElementById('editContractModal').addEventListener('show.bs.modal', function() {
    initEditRestDays();
});

// ============================================
// DOM READY
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM ready - contracts page');
    loadContracts();

    document.getElementById('filterStatus').addEventListener('change', function() {
        currentFilters.status = this.value;
        currentPage = 1;
        loadContracts();
    });

    const searchInput = document.getElementById('searchInput');
    let searchTimeout = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            searchTimeout = setTimeout(() => {
                currentFilters.search = query;
                currentPage = 1;
                loadContracts();
            }, 300);
        });
    }

    document.getElementById('refreshBtn').addEventListener('click', function() {
        currentFilters.search = '';
        document.getElementById('searchInput').value = '';
        loadContracts();
    });

    document.getElementById('createContractForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createContract();
    });

    document.getElementById('createContractBtn').addEventListener('click', function() {
        openCreateContractModal();
    });

    document.getElementById('editContractForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveEditedContract();
    });

    // Autocomplete for contracts search
    const searchInputAutocomplete = document.getElementById('searchInput');
    if (searchInputAutocomplete) {
        searchInputAutocomplete.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            if (query.length < 2) {
                document.getElementById('autocompleteDropdown').classList.remove('show');
                return;
            }
            searchTimeout = setTimeout(() => {
                fetchContractAutocomplete(query);
            }, 300);
        });
    }
});

// ============================================
// AUTOCOMPLETE FOR CONTRACTS SEARCH
// ============================================

function fetchContractAutocomplete(query) {
    const dropdown = document.getElementById('autocompleteDropdown');
    if (!query || query.length < 2) {
        dropdown.classList.remove('show');
        return;
    }

    const params = new URLSearchParams({
        p: 1,
        status: 'all',
        search: query
    });

    fetch(`?page=api_get_contracts&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const results = data.data.contracts || [];
                renderContractAutocomplete(results);
            }
        })
        .catch(error => console.error('Autocomplete error:', error));
}

function renderContractAutocomplete(results) {
    const dropdown = document.getElementById('autocompleteDropdown');
    if (!results || results.length === 0) {
        dropdown.innerHTML = `<div class="no-results">No contracts found</div>`;
        dropdown.classList.add('show');
        return;
    }

    let html = '';
    results.forEach((contract, index) => {
        const name = contract.trainee_name || 'Unknown';
        html += `
            <div class="item" data-index="${index}" data-id="${contract.id}">
                <div class="item-name">${escapeHtml(name)}</div>
                <div class="item-email">${escapeHtml(contract.applicant_email)}</div>
                <div class="item-role">${escapeHtml(contract.target_role)}</div>
            </div>
        `;
    });

    dropdown.innerHTML = html;
    dropdown.classList.add('show');

    dropdown.querySelectorAll('.item').forEach(item => {
        item.addEventListener('click', function() {
            const name = this.querySelector('.item-name').textContent;
            const input = document.getElementById('searchInput');
            input.value = name;
            dropdown.classList.remove('show');
            const event = new Event('input', { bubbles: true });
            input.dispatchEvent(event);
        });
    });
}

// ============================================
// LOAD CONTRACTS
// ============================================

function loadContracts(page = currentPage) {
    currentPage = page;
    const params = new URLSearchParams({
        p: page,
        status: currentFilters.status,
        search: currentFilters.search
    });
    const url = `?page=api_get_contracts&${params}`;
    console.log('🔍 Request URL:', url);
    const tbody = document.getElementById('contractsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading contracts...</p>
            </td>
        </tr>
    `;
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('🔍 API Response:', data);
            if (data.success) {
                const result = data.data;
                if (result.contracts && result.contracts.length > 0) {
                    renderContracts(result.contracts);
                    renderPagination(result.pagination);
                    renderStats(result.stats);
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No contracts found
                            </td>
                        </tr>
                    `;
                }
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                            ${data.message || 'Failed to load contracts'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

// ============================================
// RENDER CONTRACTS TABLE
// ============================================

function renderContracts(contracts) {
    const tbody = document.getElementById('contractsTableBody');
    if (!contracts || contracts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No contracts found
                </td>
            </tr>
        `;
        return;
    }
    const shiftLabels = {
        'opening': 'Opening (6am-2pm)',
        'closing': 'Closing (2pm-10pm)',
        'midshift': 'MidShift (10am-6pm)'
    };
    const statusColors = { 'pending': 'warning', 'accepted': 'success', 'declined': 'danger' };
    tbody.innerHTML = contracts.map(contract => `
        <tr>
            <td>
                <strong>${escapeHtml(contract.trainee_name)}</strong>
                <br>
                <small class="text-muted">${escapeHtml(contract.employee_number || 'N/A')}</small>
            </td>
            <td><span class="badge bg-info">${escapeHtml(contract.target_role)}</span></td>
            <td>${escapeHtml(shiftLabels[contract.shift] || contract.shift || '—')}</td>
            <td>₱${parseFloat(contract.salary || 0).toFixed(2)}</td>
            <td>${contract.formatted_start || '—'}</td>
            <td>
                <span class="badge bg-${statusColors[contract.status] || 'secondary'}">
                    ${contract.status.charAt(0).toUpperCase() + contract.status.slice(1)}
                </span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary view-contract" data-id="${contract.id}"><i class="bi bi-eye"></i></button>
                ${contract.status === 'pending' ? `
                    <button class="btn btn-sm btn-outline-warning edit-contract-btn"
                            data-id="${contract.id}"
                            data-applicant="${escapeHtml(contract.trainee_name)}"
                            data-role="${escapeHtml(contract.target_role)}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary send-email-btn"
                            data-id="${contract.id}"
                            data-applicant="${escapeHtml(contract.trainee_name)}">
                        <i class="bi bi-envelope"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success accept-contract"
                            data-id="${contract.id}"
                            data-name="${escapeHtml(contract.trainee_name)}">
                        <i class="bi bi-check2"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger decline-contract"
                            data-id="${contract.id}"
                            data-name="${escapeHtml(contract.trainee_name)}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                ` : ''}
            </td>
        </tr>
    `).join('');

    document.querySelectorAll('.view-contract').forEach(btn => btn.addEventListener('click', function() { viewContract(this.dataset.id); }));
    document.querySelectorAll('.edit-contract-btn').forEach(btn => btn.addEventListener('click', function() {
        const contractId = this.dataset.id;
        const traineeName = this.dataset.applicant;
        const role = this.dataset.role;
        openEditContractModal(contractId, traineeName, role);
    }));
    document.querySelectorAll('.send-email-btn').forEach(btn => btn.addEventListener('click', function() {
        sendContractEmail(this.dataset.id, this.dataset.applicant);
    }));
    document.querySelectorAll('.accept-contract').forEach(btn => btn.addEventListener('click', function() {
        updateContract(this.dataset.id, 'accept', this.dataset.name);
    }));
    document.querySelectorAll('.decline-contract').forEach(btn => btn.addEventListener('click', function() {
        updateContract(this.dataset.id, 'decline', this.dataset.name);
    }));
}

// ============================================
// PAGINATION
// ============================================

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const info = document.getElementById('tableInfo');
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        info.textContent = `Showing ${pagination?.totalRecords || 0} contracts`;
        return;
    }
    info.textContent = `Showing page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} total)`;
    let html = '';
    if (pagination.currentPage > 1) html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>`;
    else html += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);
    if (start > 1) { html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`; if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`; }
    for (let i = start; i <= end; i++) {
        if (i === pagination.currentPage) html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        else html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    if (end < pagination.totalPages) { if (end < pagination.totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`; html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`; }
    if (pagination.currentPage < pagination.totalPages) html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage + 1}">»</a></li>`;
    else html += `<li class="page-item disabled"><span class="page-link">»</span></li>`;
    container.innerHTML = html;
    container.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            loadContracts(page);
        });
    });
}

// ============================================
// STATS
// ============================================

function renderStats(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statPending').textContent = stats.pending || 0;
    document.getElementById('statAccepted').textContent = stats.accepted || 0;
    document.getElementById('statDeclined').textContent = stats.declined || 0;
}

// ============================================
// VIEW CONTRACT DETAIL
// ============================================

function viewContract(id) {
    const modal = document.getElementById('contractDetailModal');
    const body = document.getElementById('contractDetailBody');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    new bootstrap.Modal(modal).show();
    const params = new URLSearchParams({ p: 1, status: 'all', search: '' });
    fetch(`?page=api_get_contracts&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const contract = data.data.contracts.find(c => c.id == id);
                if (contract) {
                    const shiftLabels = {
                        'opening': 'Opening (6am-2pm)',
                        'closing': 'Closing (2pm-10pm)',
                        'midshift': 'MidShift (10am-6pm)'
                    };
                    body.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Trainee:</strong> ${escapeHtml(contract.trainee_name)}</p>
                                <p><strong>Email:</strong> ${escapeHtml(contract.applicant_email)}</p>
                                <p><strong>Role:</strong> ${escapeHtml(contract.target_role)}</p>
                                <p><strong>Employee #:</strong> ${escapeHtml(contract.employee_number || 'N/A')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Shift:</strong> ${shiftLabels[contract.shift] || contract.shift || '—'}</p>
                                <p><strong>Salary:</strong> ₱${parseFloat(contract.salary || 0).toFixed(2)}</p>
                                <p><strong>Start Date:</strong> ${contract.formatted_start || '—'}</p>
                                <p><strong>Decision Deadline:</strong> ${contract.decision_deadline ? new Date(contract.decision_deadline).toLocaleDateString() : '—'}</p>
                                <p><strong>Rest Days:</strong> ${contract.rest_days ? contract.rest_days.split(',').map(d => d.charAt(0).toUpperCase() + d.slice(1)).join(', ') : 'None'}</p>
                                <p><strong>Status:</strong> <span class="badge bg-${contract.status === 'pending' ? 'warning' : contract.status === 'accepted' ? 'success' : 'danger'}">${contract.status}</span></p>
                            </div>
                        </div>
                        ${contract.job_details ? `<div class="mt-3"><p><strong>Job Details:</strong></p><div class="p-3 bg-light rounded">${escapeHtml(contract.job_details)}</div></div>` : ''}
                    `;
                } else {
                    body.innerHTML = `<div class="text-center text-danger py-4">Contract not found</div>`;
                }
            }
        })
        .catch(error => {
            body.innerHTML = `<div class="text-center text-danger py-4">Error loading contract details</div>`;
        });
}

// ============================================
// CREATE CONTRACT
// ============================================

function openCreateContractModal() {
    document.getElementById('createContractForm').reset();
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('contractStartDate').value = tomorrow.toISOString().split('T')[0];
    // Reset rest days
    selectedRestDays = [];
    document.querySelectorAll('.rest-day-toggle').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-secondary');
    });
    document.getElementById('contractRestDays').value = '';
    new bootstrap.Modal(document.getElementById('createContractModal')).show();
}

function createContract() {
    const form = document.getElementById('createContractForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const salary = parseFloat(data.salary);
    if (isNaN(salary) || salary <= 0) {
        Swal.fire({ icon: 'warning', title: 'Invalid Salary', text: 'Please enter a valid salary amount.' });
        return;
    }
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
    fetch('?page=api_create_contract', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Offer Contract';
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Contract Offered!',
                text: result.message,
                timer: 1500,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('createContractModal')).hide();
            loadContracts(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to Offer', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Offer Contract';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// EDIT CONTRACT (FULLY UPDATED)
// ============================================

function openEditContractModal(contractId, traineeName, targetRole) {
    // Reset edit rest days UI
    initEditRestDays();

    document.getElementById('editContractId').value = contractId;
    document.getElementById('editContractName').textContent = traineeName;
    document.getElementById('editContractRole').value = targetRole;
    const range = ROLE_SALARY_RANGES[targetRole] || { min: 0, max: 0 };
    document.getElementById('salaryRangeHint').textContent = `Range: ₱${range.min.toFixed(2)} – ₱${range.max.toFixed(2)}`;
    document.getElementById('editContractSalary').min = range.min;
    document.getElementById('editContractSalary').max = range.max;

    fetch(`?page=api_get_contracts&p=1&status=all&search=`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const contract = data.data.contracts.find(c => c.id == contractId);
                if (contract) {
                    document.getElementById('editContractShift').value = contract.shift || '';
                    document.getElementById('editContractSalary').value = contract.salary || '';
                    document.getElementById('editContractStartDate').value = contract.start_date || '';
                    document.getElementById('editContractJobDetails').value = contract.job_details || '';
                    if (contract.decision_deadline) {
                        const days = Math.ceil((new Date(contract.decision_deadline) - new Date()) / (1000 * 60 * 60 * 24));
                        document.getElementById('editContractDeadline').value = Math.max(3, Math.min(7, days));
                    } else {
                        document.getElementById('editContractDeadline').value = '5';
                    }
                    // Load rest days
                    const restDays = contract.rest_days || '';
                    document.getElementById('editContractRestDays').value = restDays;
                    // Apply toggles after a tiny delay
                    setTimeout(() => {
                        loadEditRestDays(restDays);
                    }, 50);

                    // ✅ Set min attributes for date inputs dynamically
                    const today = new Date().toISOString().split('T')[0];
                    const startDateInput = document.getElementById('editContractStartDate');
                    const deadlineInput = document.getElementById('editContractDeadline');
                    
                    // Set min attribute for start date and deadline (which is a select, so min doesn't apply directly)
                    // For start date, we set min attribute
                    if (startDateInput) {
                        startDateInput.min = today;
                        // Also attach change listener for immediate warning
                        startDateInput.addEventListener('change', function() {
                            const todayStr = new Date().toISOString().split('T')[0];
                            if (this.value < todayStr) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Invalid Date',
                                    text: 'Start date cannot be in the past.'
                                });
                                this.value = '';
                            }
                        });
                    }

                    // For deadline select, we can't use min, but we can validate on change later.

                }
            }
        })
        .catch(error => {
            console.error('Error fetching contract:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load contract data.' });
        });

    new bootstrap.Modal(document.getElementById('editContractModal')).show();
}

function saveEditedContract() {
    const form = document.getElementById('editContractForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const role = data.role;
    const range = ROLE_SALARY_RANGES[role] || { min: 0, max: 0 };
    const salary = parseFloat(data.salary);
    
    // Validate salary
    if (isNaN(salary) || salary < range.min || salary > range.max) {
        Swal.fire({
            icon: 'warning',
            title: 'Salary Out of Range',
            text: `Salary must be between ₱${range.min.toFixed(2)} and ₱${range.max.toFixed(2)} for this role.`
        });
        return;
    }

    // ✅ Validate dates (no past dates)
    const today = new Date().toISOString().split('T')[0];
    if (data.start_date < today) {
        Swal.fire({ icon: 'warning', title: 'Invalid Start Date', text: 'Start date cannot be in the past.' });
        return;
    }
    // Decision deadline is a number of days from today, not a date string.
    // The decision_deadline in the form is the number of days (3-7). 
    // We compute the actual deadline date from the days selected.
    // But the backend expects a date string. In the form data, we have 'decision_deadline' as the number of days.
    // We'll compute the deadline date here and then convert it to a date string before sending.
    const days = parseInt(data.decision_deadline) || 5;
    const deadline = new Date();
    deadline.setDate(deadline.getDate() + days);
    const deadlineStr = deadline.toISOString().split('T')[0];
    if (deadlineStr < today) {
        Swal.fire({ icon: 'warning', title: 'Invalid Deadline', text: 'Decision deadline cannot be in the past.' });
        return;
    }
    if (deadlineStr < data.start_date) {
        Swal.fire({ icon: 'warning', title: 'Invalid Deadline', text: 'Decision deadline must be after the start date.' });
        return;
    }

    // Now we send the computed deadline date as decision_deadline
    const submitData = {
        contract_id: data.contract_id,
        action: 'update_details',
        shift: data.shift,
        salary: data.salary,
        start_date: data.start_date,
        job_details: data.job_details,
        decision_deadline: deadlineStr,
        rest_days: data.rest_days || ''
    };

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('?page=api_update_contract', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(submitData)
    })
    .then(response => response.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Changes';
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Contract Updated!',
                text: 'The contract details have been saved.',
                timer: 1500,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('editContractModal')).hide();
            loadContracts(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Changes';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// UPDATE CONTRACT (ACCEPT/DECLINE)
// ============================================

function updateContract(id, action, name) {
    const actionText = action === 'accept' ? 'Accept' : 'Decline';
    const icon = action === 'accept' ? 'question' : 'warning';
    const confirmColor = action === 'accept' ? '#198754' : '#dc3545';
    Swal.fire({
        title: `${actionText} Contract?`,
        html: `${actionText} the contract for <strong>${name}</strong>?`,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        confirmButtonText: `Yes, ${actionText}`,
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            const data = { contract_id: id, action: action };
            fetch('?page=api_update_contract', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                Swal.close();
                if (result.success) {
                    const msg = action === 'accept'
                        ? `<p><strong>${name}</strong> has been hired!</p><p class="text-muted">The employee\'s account has been upgraded. They can now log in with the default password (if not changed).</p>`
                        : `<p>The contract for <strong>${name}</strong> has been declined.</p><p class="text-muted">The applicant\'s status has been updated accordingly.</p>`;
                    Swal.fire({
                        icon: 'success',
                        title: `${actionText}ed!`,
                        html: msg,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        loadContracts(currentPage);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Update Failed', text: result.message || 'Please try again.' });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
            });
        }
    });
}

// ============================================
// SEND EMAIL (PLACEHOLDER)
// ============================================

function sendContractEmail(contractId, name) {
    Swal.fire({
        icon: 'info',
        title: 'Send Contract Email?',
        html: `Send the contract details to <strong>${name}</strong> via email?`,
        showCancelButton: true,
        confirmButtonText: 'Yes, Send',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            // TODO: Implement actual email sending
            Swal.fire({
                icon: 'success',
                title: 'Email Sent!',
                html: `
                    <p>The contract details have been sent to <strong>${name}</strong>.</p>
                    <p class="text-muted">The trainee can now review the contract and accept/decline it.</p>
                `,
                confirmButtonText: 'OK'
            });
        }
    });
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}