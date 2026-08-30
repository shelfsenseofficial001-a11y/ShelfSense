// ============================================
// HR TRAINEES - FULL AJAX
// ============================================

console.log('✅ trainees.js loaded');

let currentPage = 1;
let currentFilters = {
    status: 'all',
    role: 'all',
    search: ''
};

let autocompleteResults = [];
let selectedIndex = -1;
let searchTimeout = null;

// ============================================
// DATE PICKER RESTRICTION - BLOCK INVALID DATES
// ============================================

function closeDatePicker(input) {
    if (input) {
        input.blur();
    }
    if (document.activeElement) {
        document.activeElement.blur();
    }
}

function validateDateInput(input) {
    const value = input.value;
    if (!value) return;
    
    const selected = new Date(value);
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);
    
    const maxDate = new Date(now);
    maxDate.setMonth(maxDate.getMonth() + 3);
    maxDate.setHours(23, 59, 59, 999);
    
    if (isNaN(selected.getTime())) {
        input.value = '';
        input.classList.add('error');
        closeDatePicker(input);
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Format',
            text: 'Please use the date picker to select a valid date.',
            timer: 2000,
            timerProgressBar: true
        });
        setTimeout(() => input.classList.remove('error'), 2000);
        return;
    }
    
    if (selected < tomorrow) {
        input.value = toDatetimeLocalString(tomorrow);
        input.classList.add('error');
        closeDatePicker(input);
        Swal.fire({
            icon: 'warning',
            title: 'Date Too Early',
            text: '❌ Cannot select yesterday or earlier. Snapped to the earliest allowed date.',
            timer: 3000,
            timerProgressBar: true
        });
        setTimeout(() => input.classList.remove('error'), 3000);
        return;
    }

    if (selected > maxDate) {
        input.value = toDatetimeLocalString(maxDate);
        input.classList.add('error');
        closeDatePicker(input);
        Swal.fire({
            icon: 'warning',
            title: 'Date Too Far',
            text: '❌ Cannot select beyond 3 months from now. Snapped to the latest allowed date.',
            timer: 3000,
            timerProgressBar: true
        });
        setTimeout(() => input.classList.remove('error'), 3000);
        return;
    }

    input.classList.remove('error');
}

function toDatetimeLocalString(d) {
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function validateScheduleDate(dateStr) {
    const selected = new Date(dateStr);
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);
    const maxDate = new Date(now);
    maxDate.setMonth(maxDate.getMonth() + 3);

    if (selected < tomorrow) {
        document.activeElement?.blur();
        Swal.fire({ icon: 'warning', title: 'Invalid Date', text: 'Please select a date from tomorrow onwards.' });
        return false;
    }
    if (selected > maxDate) {
        document.activeElement?.blur();
        Swal.fire({ icon: 'warning', title: 'Invalid Date', text: 'Date cannot exceed 3 months from now.' });
        return false;
    }
    return true;
}
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM ready - trainees page');
    loadTrainees();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'status', type: 'select', elementId: 'filterStatus', defaultValue: 'all' },
            { key: 'role', type: 'select', elementId: 'filterRole', defaultValue: 'all' },
            { key: 'search', type: 'search', elementId: 'searchInput' },
        ]);
    }

    document.getElementById('filterStatus').addEventListener('change', function() {
        currentFilters.status = this.value;
        currentPage = 1;
        loadTrainees();
    });

    document.getElementById('filterRole').addEventListener('change', function() {
        currentFilters.role = this.value;
        currentPage = 1;
        loadTrainees();
    });

    const searchInput = document.getElementById('searchInput');
    const autocompleteDropdown = document.getElementById('autocompleteDropdown');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);
            if (query.length < 2) {
                autocompleteDropdown.classList.remove('show');
                autocompleteResults = [];
                selectedIndex = -1;
                if (query.length === 0) {
                    currentFilters.search = '';
                    currentPage = 1;
                    loadTrainees();
                }
                return;
            }
            searchTimeout = setTimeout(() => {
                fetchAutocompleteSuggestions(query);
            }, 300);
        });
        searchInput.addEventListener('keydown', function(e) {
            const items = autocompleteDropdown.querySelectorAll('.item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length > 0) {
                    selectedIndex = (selectedIndex + 1) % items.length;
                    highlightItem(items);
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length > 0) {
                    selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                    highlightItem(items);
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && selectedIndex < items.length) {
                    const selectedItem = items[selectedIndex];
                    const index = parseInt(selectedItem.dataset.index);
                    const trainee = autocompleteResults[index];
                    if (trainee) {
                        selectAutocompleteItem(trainee);
                    }
                }
            } else if (e.key === 'Escape') {
                autocompleteDropdown.classList.remove('show');
            }
        });
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
                autocompleteDropdown.classList.remove('show');
            }
        });
    }

    document.getElementById('refreshBtn').addEventListener('click', function() {
        currentFilters.search = '';
        document.getElementById('searchInput').value = '';
        loadTrainees();
    });

    document.getElementById('assignTrainerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        assignTrainer();
    });

    // Schedule contract interview form
    const contractForm = document.getElementById('scheduleContractInterviewForm');
    if (contractForm) {
        contractForm.addEventListener('submit', function(e) {
            e.preventDefault();
            scheduleContractInterview();
        });
    }
});

// ============================================
// FETCH & RENDER AUTOCOMPLETE
// ============================================

function fetchAutocompleteSuggestions(query) {
    const params = new URLSearchParams({
        p: 1,
        status: currentFilters.status,
        role: currentFilters.role,
        search: query
    });
    fetch(`?page=api_get_trainees&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                autocompleteResults = data.data.trainees || [];
                renderAutocomplete(autocompleteResults);
            }
        })
        .catch(error => console.error('Autocomplete error:', error));
}

function renderAutocomplete(results) {
    const dropdown = document.getElementById('autocompleteDropdown');
    if (!results || results.length === 0) {
        dropdown.innerHTML = '<div class="no-results">No trainees found</div>';
        dropdown.classList.add('show');
        return;
    }
    let html = '';
    results.forEach((trainee, index) => {
        const isSelected = index === selectedIndex;
        html += `
            <div class="item ${isSelected ? 'selected' : ''}" data-index="${index}" data-id="${trainee.id}">
                <div class="item-name">${escapeHtml(trainee.trainee_name)}</div>
                <div class="item-email">${escapeHtml(trainee.applicant_email)}</div>
                <div class="item-role">${escapeHtml(trainee.target_role)}</div>
            </div>
        `;
    });
    dropdown.innerHTML = html;
    dropdown.classList.add('show');
    dropdown.querySelectorAll('.item').forEach(item => {
        item.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            const trainee = autocompleteResults[index];
            if (trainee) {
                selectAutocompleteItem(trainee);
            }
        });
    });
}

function selectAutocompleteItem(trainee) {
    const searchInput = document.getElementById('searchInput');
    searchInput.value = trainee.trainee_name;
    document.getElementById('autocompleteDropdown').classList.remove('show');
    selectedIndex = -1;
    autocompleteResults = [];
    currentFilters.search = trainee.applicant_email;
    currentPage = 1;
    loadTrainees();
}

function highlightItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('selected', index === selectedIndex);
        if (index === selectedIndex) {
            item.scrollIntoView({ block: 'nearest' });
        }
    });
}

// ============================================
// LOAD TRAINEES
// ============================================

function loadTrainees(page = currentPage) {
    currentPage = page;
    const params = new URLSearchParams({
        p: page,
        status: currentFilters.status,
        role: currentFilters.role,
        search: currentFilters.search
    });
    const url = `?page=api_get_trainees&${params}`;
    console.log('🔍 Request URL:', url);
    const tbody = document.getElementById('traineesTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading trainees...</p>
            </td>
        </tr>
    `;
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('🔍 API Response:', data);
            if (data.success) {
                const result = data.data;
                if (result.trainees && result.trainees.length > 0) {
                    renderTrainees(result.trainees);
                    renderPagination(result.pagination);
                    renderStats(result.stats);
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No trainees found
                            </td>
                        </tr>
                    `;
                }
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                            ${data.message || 'Failed to load trainees'}
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
// RENDER TRAINEES TABLE
// ============================================

function renderTrainees(trainees) {
    const tbody = document.getElementById('traineesTableBody');
    tbody.innerHTML = trainees.map(trainee => `
        <tr>
            <td>
                <strong>${escapeHtml(trainee.trainee_name)}</strong>
                <br>
                <small class="text-muted">${escapeHtml(trainee.employee_number || 'N/A')}</small>
            </td>
            <td><span class="badge bg-info">${escapeHtml(trainee.target_role)}</span></td>
            <td>${escapeHtml(trainee.employee_number || 'N/A')}</td>
            <td>${escapeHtml(trainee.trainer_name || 'Not assigned')}</td>
            <td>
                ${trainee.schedule}
                <br>
                <small class="text-muted">${trainee.formatted_start} - ${trainee.formatted_end}</small>
            </td>
            <td>
                <span class="badge bg-${trainee.status_color}">
                    ${trainee.status_label}
                </span>
                ${trainee.reports_status === 'pending' ? '<span class="badge bg-secondary ms-1">Reports Pending</span>' : ''}
                ${trainee.reports_status === 'completed' ? '<span class="badge bg-success ms-1">Reports Complete</span>' : ''}
                ${trainee.eligible_for_contract ? '<span class="badge bg-primary ms-1">✅ Eligible</span>' : ''}
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary view-trainee-btn" data-id="${trainee.id}"><i class="bi bi-eye"></i></button>
                ${trainee.status === 'active' && !trainee.trainer_name ? `
                    <button class="btn btn-sm btn-outline-warning assign-trainer-btn" data-id="${trainee.id}" data-name="${escapeHtml(trainee.trainee_name)}"><i class="bi bi-person-plus"></i></button>
                ` : ''}
                ${trainee.status === 'active' ? `
                    <button class="btn btn-sm btn-outline-info reports-btn" data-id="${trainee.id}" data-name="${escapeHtml(trainee.trainee_name)}"><i class="bi bi-file-text"></i></button>
                ` : ''}
                ${trainee.status === 'active' && trainee.reports_status === 'completed' && !trainee.eligible_for_contract && window.HR_IS_HEAD ? `
                    <button class="btn btn-sm btn-outline-success review-btn review-eligible" data-id="${trainee.id}" data-name="${escapeHtml(trainee.trainee_name)}"><i class="bi bi-check2-circle"></i></button>
                    <button class="btn btn-sm btn-outline-danger review-btn review-terminate" data-id="${trainee.id}" data-name="${escapeHtml(trainee.trainee_name)}"><i class="bi bi-x-circle"></i></button>
                ` : ''}
                ${trainee.eligible_for_contract ? `
                    <span class="badge bg-success ms-1" title="Schedule their Final Interview from the Interviews page">Ready for Final Interview</span>
                ` : ''}
            </td>
        </tr>
    `).join('');

    // Attach event listeners
    document.querySelectorAll('.view-trainee-btn').forEach(btn => btn.addEventListener('click', function() { viewTrainee(this.dataset.id); }));
    document.querySelectorAll('.assign-trainer-btn').forEach(btn => btn.addEventListener('click', function() {
        openAssignTrainerModal(this.dataset.id, this.dataset.name);
    }));
    document.querySelectorAll('.reports-btn').forEach(btn => btn.addEventListener('click', function() {
        if (this.dataset.processing) return;
        openReportsModal(this.dataset.id, this.dataset.name);
    }));
    document.querySelectorAll('.review-eligible').forEach(btn => btn.addEventListener('click', function() {
        console.log('🔍 Eligible button clicked for trainee ID:', this.dataset.id);
        reviewReports(this.dataset.id, 'eligible', this.dataset.name);
    }));
    document.querySelectorAll('.review-terminate').forEach(btn => btn.addEventListener('click', function() {
        console.log('🔍 Terminate button clicked for trainee ID:', this.dataset.id);
        reviewReports(this.dataset.id, 'terminate', this.dataset.name);
    }));
    document.querySelectorAll('.schedule-contract-interview-btn').forEach(btn => btn.addEventListener('click', function() {
        const traineeId = this.dataset.id;
        const applicantId = this.dataset.applicantId;
        const name = this.dataset.name;
        openScheduleContractInterview(traineeId, applicantId, name);
    }));
}

// ============================================
// PAGINATION & STATS
// ============================================

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const info = document.getElementById('tableInfo');
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        info.textContent = `Showing ${pagination?.totalRecords || 0} trainees`;
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
            loadTrainees(page);
        });
    });
}

function renderStats(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statActive').textContent = stats.active || 0;
    document.getElementById('statCompleted').textContent = stats.completed || 0;
    document.getElementById('statTerminated').textContent = stats.terminated || 0;
}

// ============================================
// VIEW TRAINEE DETAIL
// ============================================

function viewTrainee(id) {
    const modal = document.getElementById('traineeDetailModal');
    const body = document.getElementById('traineeDetailBody');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    bootstrap.Offcanvas.getOrCreateInstance(modal).show();
    const params = new URLSearchParams({ p: 1, status: 'all', role: 'all', search: '' });
    fetch(`?page=api_get_trainees&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const trainee = data.data.trainees.find(t => t.id == id);
                if (trainee) {
                    body.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Trainee:</strong> ${escapeHtml(trainee.trainee_name)}</p>
                                <p><strong>Employee #:</strong> ${escapeHtml(trainee.employee_number || 'N/A')}</p>
                                <p><strong>Email:</strong> ${escapeHtml(trainee.applicant_email)}</p>
                                <p><strong>Role:</strong> ${escapeHtml(trainee.target_role)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Trainer:</strong> ${escapeHtml(trainee.trainer_name || 'Not assigned')}</p>
                                <p><strong>Schedule:</strong> ${trainee.schedule}</p>
                                <p><strong>Start Date:</strong> ${trainee.formatted_start}</p>
                                <p><strong>End Date:</strong> ${trainee.formatted_end}</p>
                                <p><strong>Status:</strong> <span class="badge bg-${trainee.status_color}">${trainee.status_label}</span></p>
                                ${trainee.is_completed ? '<p><strong>✅ Training completed</strong></p>' : `<p><strong>Days remaining:</strong> ${trainee.days_remaining}</p>`}
                            </div>
                        </div>
                    `;
                } else {
                    body.innerHTML = `<div class="text-center text-danger py-4">Trainee not found</div>`;
                }
            }
        })
        .catch(error => {
            body.innerHTML = `<div class="text-center text-danger py-4">Error loading trainee details</div>`;
        });
}

// ============================================
// ASSIGN TRAINER
// ============================================

function openAssignTrainerModal(id, name) {
    document.getElementById('assignTraineeId').value = id;
    document.getElementById('assignTraineeName').textContent = name;
    document.getElementById('assignTrainerSelect').value = '';
    new bootstrap.Modal(document.getElementById('assignTrainerModal')).show();
}

function assignTrainer() {
    const form = document.getElementById('assignTrainerForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.action = 'assign_trainer';
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Assigning...';
    fetch('?page=api_update_trainee', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Assign Trainer';
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Trainer Assigned!',
                text: result.message,
                timer: 1500,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('assignTrainerModal')).hide();
            loadTrainees(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to Assign', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Assign Trainer';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// REPORTS MODAL
// ============================================

let currentTraineeId = null;
let currentTraineeReports = [];

function openReportsModal(traineeId, traineeName) {
    currentTraineeId = traineeId;
    document.getElementById('reportsTraineeName').textContent = traineeName;
    document.getElementById('reportsBody').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading reports...</p>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('reportsModal')).show();
    loadWeeklyReports(traineeId);
}

function loadWeeklyReports(traineeId) {
    fetch(`?page=api_trainee_get_reports&trainee_id=${traineeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentTraineeReports = data.data;
                renderWeeklyReports(traineeId, data.data);
            } else {
                document.getElementById('reportsBody').innerHTML = `<div class="text-center text-danger py-4">${escapeHtml(data.message || 'Failed to load reports.')}</div>`;
            }
        })
        .catch(() => {
            document.getElementById('reportsBody').innerHTML = `
                <div class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                    Error loading reports.
                </div>
            `;
        });
}

function renderWeeklyReports(traineeId, data) {
    const body = document.getElementById('reportsBody');
    const role = window.CURRENT_USER_ROLE;
    const userId = window.CURRENT_USER_ID;

    let html = `
        <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="badge bg-${data.all_reports_complete ? 'success' : 'secondary'}">${data.submitted_weeks}/${data.expected_weeks} weeks submitted</span>
                ${data.all_forwarded && data.reports.length ? '<span class="badge bg-info ms-1">All forwarded to HR Head</span>' : ''}
                ${data.missing_weeks.length ? `<span class="badge bg-warning text-dark ms-1">Missing: ${data.missing_weeks.join(', ')}</span>` : ''}
            </div>
            <button class="btn btn-sm btn-yellow-primary" id="openSubmitReportBtn"><i class="bi bi-plus-circle"></i> Submit Weekly Report</button>
        </div>
        <div id="submitReportFormWrap" class="modern-card p-3 mb-3" style="display:none;">
            <div class="mb-2">
                <label class="form-label fw-semibold">Week Number</label>
                <input type="number" id="reportWeekNumber" class="form-control form-control-sm" min="1" max="${data.expected_weeks}" style="max-width:120px;" value="${data.missing_weeks[0] || 1}">
            </div>
            <div class="mb-2"><label class="form-label fw-semibold">Report Content</label><textarea id="reportContentInput" class="form-control" rows="3" maxlength="5000"></textarea></div>
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label small">Strengths</label><textarea id="reportStrengths" class="form-control form-control-sm" rows="2" maxlength="2000"></textarea></div>
                <div class="col-md-6"><label class="form-label small">Areas for Improvement</label><textarea id="reportImprovements" class="form-control form-control-sm" rows="2" maxlength="2000"></textarea></div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-6"><label class="form-label small">Attendance/Punctuality Notes</label><input type="text" id="reportAttendance" class="form-control form-control-sm" maxlength="1000"></div>
                <div class="col-md-6">
                    <label class="form-label small">Performance Rating</label>
                    <select id="reportRating" class="form-select form-select-sm">
                        <option value="">-- none --</option>
                        <option value="excellent">Excellent</option>
                        <option value="good">Good</option>
                        <option value="satisfactory">Satisfactory</option>
                        <option value="needs_improvement">Needs Improvement</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
            </div>
            <div class="text-muted small mt-2">Once submitted, this report cannot be edited.</div>
            <button class="btn btn-yellow-primary btn-sm mt-2" id="submitWeeklyReportBtn"><i class="bi bi-save"></i> Submit</button>
        </div>
    `;

    if (!data.reports.length) {
        html += `<div class="text-center text-muted py-3">No weekly reports submitted yet.</div>`;
    } else {
        html += data.reports.map(r => {
            const canObserve = r.status === 'submitted' && role === mapDeptToReviewerRoleJs(r.department) && Number(r.trainer_id) !== Number(userId);
            const canHrReview = (r.status === 'forwarded' || r.status === 'hr_reviewed') && (role === 'hr_head');
            return `
            <div class="modern-card p-3 mb-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Week ${r.week_number} <small class="text-muted">(${r.period_start} to ${r.period_end})</small></h6>
                    <span class="badge bg-${r.status === 'submitted' ? 'secondary' : r.status === 'forwarded' ? 'info' : 'success'}">${escapeHtml(r.status)}</span>
                </div>
                <div class="p-2 rounded mb-2" style="white-space:pre-wrap; background: var(--bg-card-subtle); color: var(--text-main);">${escapeHtml(r.report_content)}</div>
                <div class="small text-muted mb-2">By ${escapeHtml(r.trainer_first)} ${escapeHtml(r.trainer_last)} on ${new Date(r.submitted_at).toLocaleDateString()}
                    ${r.performance_rating ? ' &middot; Rating: ' + escapeHtml(r.performance_rating) : ''}</div>
                ${r.reviewer_observation ? `<div class="alert alert-secondary py-1 px-2 small mb-2"><strong>${escapeHtml(r.reviewer_role)} observation (${escapeHtml(r.reviewer_first)} ${escapeHtml(r.reviewer_last)}):</strong> ${escapeHtml(r.reviewer_observation)}</div>` : ''}
                ${r.hr_head_notes ? `<div class="alert alert-info py-1 px-2 small mb-2"><strong>HR Head notes:</strong> ${escapeHtml(r.hr_head_notes)}</div>` : ''}
                ${canObserve ? `
                    <textarea class="form-control form-control-sm mb-1 observation-input" data-report-id="${r.id}" rows="2" maxlength="3000" placeholder="Add your observation (optional)..."></textarea>
                    <button class="btn btn-sm btn-outline-primary forward-report-btn" data-report-id="${r.id}"><i class="bi bi-send"></i> Add Observation &amp; Forward to HR Head</button>
                ` : ''}
                ${canHrReview ? `
                    <textarea class="form-control form-control-sm mb-1 hrhead-input" data-report-id="${r.id}" rows="2" maxlength="2000" placeholder="HR Head notes (optional)..."></textarea>
                    <button class="btn btn-sm btn-outline-success hrhead-review-btn" data-report-id="${r.id}"><i class="bi bi-check2"></i> Save Review Note</button>
                ` : ''}
            </div>`;
        }).join('');
    }

    body.innerHTML = html;

    document.getElementById('openSubmitReportBtn')?.addEventListener('click', function () {
        const wrap = document.getElementById('submitReportFormWrap');
        wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';
    });
    document.getElementById('submitWeeklyReportBtn')?.addEventListener('click', () => submitWeeklyReport(traineeId));
    body.querySelectorAll('.forward-report-btn').forEach(btn => btn.addEventListener('click', function () {
        const reportId = this.dataset.reportId;
        const obs = body.querySelector(`.observation-input[data-report-id="${reportId}"]`).value.trim();
        forwardReport(reportId, obs, traineeId);
    }));
    body.querySelectorAll('.hrhead-review-btn').forEach(btn => btn.addEventListener('click', function () {
        const reportId = this.dataset.reportId;
        const notes = body.querySelector(`.hrhead-input[data-report-id="${reportId}"]`).value.trim();
        hrHeadReviewReport(reportId, notes, traineeId);
    }));
}

function mapDeptToReviewerRoleJs(department) {
    return { store: 'store_manager', finance: 'finance_head', hr: 'hr_staff' }[department] || null;
}

function submitWeeklyReport(traineeId) {
    const payload = {
        trainee_id: traineeId,
        week_number: parseInt(document.getElementById('reportWeekNumber').value),
        report_content: document.getElementById('reportContentInput').value.trim(),
        strengths: document.getElementById('reportStrengths').value.trim(),
        improvements: document.getElementById('reportImprovements').value.trim(),
        attendance_notes: document.getElementById('reportAttendance').value.trim(),
        performance_rating: document.getElementById('reportRating').value
    };
    if (!payload.report_content) {
        Swal.fire({ icon: 'warning', title: 'Report Cannot Be Empty', text: 'Please enter the report details.' });
        return;
    }
    fetch('?page=api_trainee_submit_report', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Report Submitted', text: data.message, timer: 2000, showConfirmButton: false });
                loadWeeklyReports(traineeId);
            } else {
                Swal.fire({ icon: 'error', title: 'Submission Failed', text: data.message || 'Please try again.' });
            }
        });
}

function forwardReport(reportId, observation, traineeId) {
    fetch('?page=api_trainee_add_observation', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ report_id: reportId, observation })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Forwarded', text: data.message, timer: 2000, showConfirmButton: false });
                loadWeeklyReports(traineeId);
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Please try again.' });
            }
        });
}

function hrHeadReviewReport(reportId, notes, traineeId) {
    fetch('?page=api_trainee_hr_head_review_report', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ report_id: reportId, notes })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Saved', text: data.message, timer: 2000, showConfirmButton: false });
                loadWeeklyReports(traineeId);
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Please try again.' });
            }
        });
}

// ============================================
// REVIEW REPORTS (Eligible / Terminate)
// ============================================

function reviewReports(traineeId, action, traineeName) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('reportsModal'));
    const doSubmit = (override, reason) => {
        Swal.fire({ title: 'Processing...', text: 'Please wait.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('?page=api_update_trainee', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trainee_id: traineeId, action: action === 'eligible' ? 'complete' : 'terminate', override, reason })
        })
        .then(response => response.json())
        .then(result => {
            Swal.close();
            if (result.success) {
                const msg = action === 'eligible'
                    ? `<p><strong>${escapeHtml(traineeName)}</strong> is now eligible for the Final Interview.</p><p class="text-muted">Next step: go to the <strong>Interviews</strong> page and schedule their Final Interview.</p>`
                    : `<p><strong>${escapeHtml(traineeName)}</strong> has been terminated.</p><p class="text-muted">The trainee account has been deactivated and the trainer released.</p>`;
                Swal.fire({ icon: 'success', title: action === 'eligible' ? 'Trainee Marked Eligible!' : 'Trainee Terminated', html: msg, confirmButtonText: 'OK' })
                    .then(() => {
                        loadTrainees(currentPage);
                        const modalInstance = bootstrap.Modal.getInstance(document.getElementById('reportsModal'));
                        if (modalInstance) modalInstance.hide();
                    });
            } else if (result.data && result.data.missing_weeks && action === 'eligible') {
                // Missing/unforwarded reports -- offer an explicit override.
                Swal.fire({
                    icon: 'warning',
                    title: 'Reports Incomplete',
                    html: `<p>${escapeHtml(result.message)}</p><textarea id="overrideReason" class="swal2-textarea" placeholder="Reason for overriding..."></textarea>`,
                    showCancelButton: true,
                    confirmButtonText: 'Override & Mark Eligible',
                    preConfirm: () => {
                        const reason = document.getElementById('overrideReason').value.trim();
                        if (!reason) { Swal.showValidationMessage('An override reason is required.'); return false; }
                        return reason;
                    }
                }).then(r2 => {
                    if (r2.isConfirmed) doSubmit(true, r2.value);
                    else if (modal) modal.show();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Action Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => {
            Swal.close();
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
        });
    };

    if (action === 'terminate') {
        Swal.fire({
            title: 'Terminate Trainee?',
            html: `<p>Terminate <strong>${escapeHtml(traineeName)}</strong>?</p><p class="text-danger">This action cannot be undone.</p><textarea id="terminateReason" class="swal2-textarea" placeholder="Reason for termination (required)..."></textarea>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Terminate',
            preConfirm: () => {
                const reason = document.getElementById('terminateReason').value.trim();
                if (!reason) { Swal.showValidationMessage('A termination reason is required.'); return false; }
                return reason;
            }
        }).then(result => {
            if (result.isConfirmed) doSubmit(false, result.value);
            else if (modal) modal.show();
        });
        return;
    }

    Swal.fire({
        title: 'Mark as Eligible for Final Interview?',
        html: `<p>Mark <strong>${escapeHtml(traineeName)}</strong> as eligible?</p><p class="text-muted small">This releases the trainer and prepares the trainee for the Final Interview.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Mark Eligible',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) doSubmit(false, '');
        else if (modal) modal.show();
    });
}

// ============================================
// SCHEDULE CONTRACT INTERVIEW (legacy stage -- no longer triggered from the UI;
// kept only so any lingering reference doesn't throw a ReferenceError)
// ============================================

function openScheduleContractInterview(traineeId, applicantId, traineeName) {
    document.getElementById('scheduleContractApplicantId').value = applicantId;
    document.getElementById('scheduleContractTraineeName').textContent = traineeName;
    document.getElementById('scheduleContractDate').value = '';
    document.getElementById('scheduleContractGmeet').value = '';
    document.getElementById('scheduleContractMessage').value = '';
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(10, 0, 0, 0);
    document.getElementById('scheduleContractDate').value = tomorrow.toISOString().slice(0, 16);
    new bootstrap.Modal(document.getElementById('scheduleContractInterviewModal')).show();
}

function scheduleContractInterview() {
    const form = document.getElementById('scheduleContractInterviewForm');
    if (!form) {
        console.error('❌ scheduleContractInterviewForm not found');
        return;
    }
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.interview_type = 'contract';

    if (data.scheduled_date && !validateScheduleDate(data.scheduled_date)) {
        return;
    }

    const traineeName = document.getElementById('scheduleContractTraineeName')?.textContent || 'Trainee';
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scheduling...';
    fetch('?page=api_schedule_interview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Schedule Interview';
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Contract Interview Scheduled!',
                html: `
                    <p>A contract interview has been scheduled for <strong>${escapeHtml(traineeName)}</strong>.</p>
                    <p class="text-muted">Next step: Go to <strong>Interviews tab → Contract</strong> to manage the interview. After the call, click <strong>"Create Contract"</strong>.</p>
                `,
                confirmButtonText: 'OK'
            });
            bootstrap.Modal.getInstance(document.getElementById('scheduleContractInterviewModal')).hide();
            loadTrainees(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to Schedule', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Schedule Interview';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// HELPER
// ============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Make validateDateInput globally accessible
window.validateDateInput = validateDateInput;
window.validateScheduleDate = validateScheduleDate;
window.closeDatePicker = closeDatePicker;