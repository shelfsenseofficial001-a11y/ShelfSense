// ============================================
// HR APPLICANTS - FULL AJAX CRUD + Autofill
// ============================================

console.log('✅ applicants.js loaded');

let currentPage = 1;
let currentFilters = {
    status: 'all',
    role: 'all',
    search: ''
};

// Autocomplete variables
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
        // Close any open picker before showing Swal
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
    loadApplicants();
    
    // ============================================
    // FILTER EVENT LISTENERS
    // ============================================
    
    document.getElementById('filterStatus').addEventListener('change', function() {
        currentFilters.status = this.value;
        currentPage = 1;
        loadApplicants();
    });
    
    document.getElementById('filterRole').addEventListener('change', function() {
        currentFilters.role = this.value;
        currentPage = 1;
        loadApplicants();
    });
    
    // ============================================
    // AUTOCOMPLETE SEARCH
    // ============================================
    
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
                    loadApplicants();
                }
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetchAutocompleteSuggestions(query);
            }, 300);
        });
        
        // Keyboard navigation for autocomplete
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
                    const applicant = autocompleteResults[index];
                    if (applicant) {
                        selectAutocompleteItem(applicant);
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
    
    // ============================================
    // REFRESH BUTTON
    // ============================================
    
    document.getElementById('refreshBtn').addEventListener('click', function() {
        currentFilters.search = '';
        document.getElementById('searchInput').value = '';
        loadApplicants();
    });

    // ============================================
    // Schedule Interview Form Listener
    // ============================================
    const scheduleForm = document.getElementById('scheduleInterviewForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            scheduleInterview();
        });
    }
    
    // ============================================
    // Trainer Selection Modal (if exists)
    // ============================================
    const confirmBtn = document.getElementById('confirmCreateTraineeBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const trainerId = document.getElementById('trainerSelect').value;
            if (!trainerId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Please Select a Trainer',
                    text: 'You must assign a trainer before creating the trainee account.',
                    confirmButtonText: 'OK'
                });
                return;
            }
            Swal.fire({
                title: 'Create Trainee Account?',
                text: 'This will create a trainee account with the selected trainer assigned.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Yes, Create',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    createTraineeWithTrainer(currentApplicantId, trainerId);
                }
            });
        });
    }
});

// ============================================
// FETCH AUTOCOMPLETE SUGGESTIONS
// ============================================

function fetchAutocompleteSuggestions(query) {
    const params = new URLSearchParams({
        p: 1,
        status: currentFilters.status,
        role: currentFilters.role,
        search: query
    });
    
    fetch(`?page=api_get_applicants&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                autocompleteResults = data.data.applicants || [];
                renderAutocomplete(autocompleteResults);
            }
        })
        .catch(error => {
            console.error('Autocomplete error:', error);
        });
}

// ============================================
// RENDER AUTOCOMPLETE DROPDOWN
// ============================================

function renderAutocomplete(results) {
    const dropdown = document.getElementById('autocompleteDropdown');
    
    if (!results || results.length === 0) {
        dropdown.innerHTML = `<div class="no-results">No applicants found</div>`;
        dropdown.classList.add('show');
        return;
    }
    
    let html = '';
    results.forEach((applicant, index) => {
        const isSelected = index === selectedIndex;
        html += `
            <div class="item ${isSelected ? 'selected' : ''}" data-index="${index}" data-id="${applicant.id}">
                <div class="item-name">${escapeHtml(applicant.first_name)} ${escapeHtml(applicant.last_name)}</div>
                <div class="item-email">${escapeHtml(applicant.email)}</div>
                <div class="item-role">${escapeHtml(applicant.target_role)}</div>
            </div>
        `;
    });
    
    dropdown.innerHTML = html;
    dropdown.classList.add('show');
    
    dropdown.querySelectorAll('.item').forEach(item => {
        item.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            const applicant = autocompleteResults[index];
            if (applicant) {
                selectAutocompleteItem(applicant);
            }
        });
    });
}

// ============================================
// SELECT AUTOCOMPLETE ITEM
// ============================================

function selectAutocompleteItem(applicant) {
    const searchInput = document.getElementById('searchInput');
    searchInput.value = `${applicant.first_name} ${applicant.last_name}`;
    document.getElementById('autocompleteDropdown').classList.remove('show');
    selectedIndex = -1;
    autocompleteResults = [];
    
    currentFilters.search = applicant.email;
    currentPage = 1;
    loadApplicants();
}

// ============================================
// HIGHLIGHT AUTOCOMPLETE ITEM
// ============================================

function highlightItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('selected', index === selectedIndex);
        if (index === selectedIndex) {
            item.scrollIntoView({ block: 'nearest' });
        }
    });
}

// ============================================
// LOAD APPLICANTS (AJAX)
// ============================================

// ============================================
// ACTIVE FILTER CHIPS (Modrinth-style removable filter pills)
// ============================================

function getActiveFilterChipData() {
    const chips = [];
    const statusSelect = document.getElementById('filterStatus');
    const roleSelect = document.getElementById('filterRole');

    if (currentFilters.status && currentFilters.status !== 'all') {
        const opt = statusSelect.querySelector(`option[value="${CSS.escape(currentFilters.status)}"]`);
        chips.push({ key: 'status', label: opt ? opt.textContent : currentFilters.status });
    }
    if (currentFilters.role && currentFilters.role !== 'all') {
        const opt = roleSelect.querySelector(`option[value="${CSS.escape(currentFilters.role)}"]`);
        chips.push({ key: 'role', label: opt ? opt.textContent : currentFilters.role });
    }
    if (currentFilters.search && currentFilters.search.trim() !== '') {
        chips.push({ key: 'search', label: `"${currentFilters.search}"` });
    }
    return chips;
}

function renderActiveFilterChips() {
    const container = document.getElementById('activeFilterChips');
    if (!container) return;

    const chips = getActiveFilterChipData();
    if (!chips.length) {
        container.innerHTML = '';
        return;
    }

    let html = `<button type="button" class="filter-chip clear-all-chip" id="clearAllFiltersChip"><i class="bi bi-x-circle"></i>Clear all filters</button>`;
    chips.forEach(chip => {
        html += `<button type="button" class="filter-chip" data-filter-key="${chip.key}"><i class="bi bi-x"></i>${escapeHtml(chip.label)}</button>`;
    });
    container.innerHTML = html;

    container.querySelectorAll('.filter-chip[data-filter-key]').forEach(chipEl => {
        chipEl.addEventListener('click', function() {
            removeActiveFilter(this.dataset.filterKey);
        });
    });
    document.getElementById('clearAllFiltersChip').addEventListener('click', clearAllActiveFilters);
}

function removeActiveFilter(key) {
    if (key === 'status') {
        currentFilters.status = 'all';
        const el = document.getElementById('filterStatus');
        el.value = 'all';
        if (window.refreshSearchableSelect) window.refreshSearchableSelect(el);
    } else if (key === 'role') {
        currentFilters.role = 'all';
        const el = document.getElementById('filterRole');
        el.value = 'all';
        if (window.refreshSearchableSelect) window.refreshSearchableSelect(el);
    } else if (key === 'search') {
        currentFilters.search = '';
        document.getElementById('searchInput').value = '';
    }
    currentPage = 1;
    loadApplicants();
}

function clearAllActiveFilters() {
    currentFilters = { status: 'all', role: 'all', search: '' };
    const statusEl = document.getElementById('filterStatus');
    const roleEl = document.getElementById('filterRole');
    statusEl.value = 'all';
    roleEl.value = 'all';
    document.getElementById('searchInput').value = '';
    if (window.refreshSearchableSelect) {
        window.refreshSearchableSelect(statusEl);
        window.refreshSearchableSelect(roleEl);
    }
    currentPage = 1;
    loadApplicants();
}

function loadApplicants(page = currentPage) {
    currentPage = page;
    renderActiveFilterChips();

    const params = new URLSearchParams({
        p: page,
        status: currentFilters.status,
        role: currentFilters.role,
        search: currentFilters.search
    });
    
    const url = `?page=api_get_applicants&${params}`;
    console.log('🔍 Request URL:', url);
    
    const tbody = document.getElementById('applicantsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading applicants...</p>
            </td>
        </tr>
    `;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('🔍 API Response:', data);
            
            if (data.success) {
                const result = data.data;
                if (result.applicants && result.applicants.length > 0) {
                    renderApplicants(result.applicants);
                    renderPagination(result.pagination);
                    renderStats(result.stats);
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No applicants found
                            </td>
                        </tr>
                    `;
                }
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger py-4">
                            <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                            ${data.message || 'Failed to load applicants'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

// ============================================
// RENDER APPLICANTS TABLE
// ============================================

function renderApplicants(applicants) {
    const tbody = document.getElementById('applicantsTableBody');
    
    if (!Array.isArray(applicants) || applicants.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No applicants found
                </td>
            </tr>
        `;
        return;
    }
    
    const statusColors = {
        'pending': 'warning',
        'initial_scheduled': 'info',
        'initial_passed': 'primary',
        'initial_failed': 'danger',
        'final_scheduled': 'info',
        'final_passed': 'primary',
        'final_failed': 'danger',
        'screening': 'warning',
        'screening_success': 'success',
        'screening_failed': 'danger',
        'contract_offered': 'primary',
        'contract_declined': 'secondary',
        'hired': 'success'
    };
    
    tbody.innerHTML = applicants.map(applicant => `
        <tr data-status="${applicant.status}">
            <td>
                <strong>${escapeHtml(applicant.first_name)} ${escapeHtml(applicant.last_name)}</strong>
            </td>
            <td>${escapeHtml(applicant.target_role)}</td>
            <td>${escapeHtml(applicant.email)}</td>
            <td>${formatDate(applicant.applied_date)}</td>
            <td>
                <span class="badge bg-${statusColors[applicant.status] || 'secondary'}">
                    ${applicant.status_label}
                </span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary view-applicant" data-id="${applicant.id}">
                    <i class="bi bi-eye"></i>
                </button>
                ${canScheduleInterview(applicant.status) ? `
                    <button class="btn btn-sm btn-outline-success schedule-interview" 
                            data-id="${applicant.id}" 
                            data-name="${escapeHtml(applicant.first_name)} ${escapeHtml(applicant.last_name)}">
                        <i class="bi bi-calendar-plus"></i>
                    </button>
                ` : ''}
                ${canReject(applicant.status) ? `
                    <button class="btn btn-sm btn-outline-danger reject-applicant" 
                            data-id="${applicant.id}" 
                            data-name="${escapeHtml(applicant.first_name)} ${escapeHtml(applicant.last_name)}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                ` : ''}
            </td>
        </tr>
    `).join('');
    
    // Attach event listeners
    tbody.querySelectorAll('.view-applicant').forEach(btn => {
        btn.addEventListener('click', function() {
            viewApplicant(this.dataset.id);
        });
    });
    
    tbody.querySelectorAll('.schedule-interview').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const row = this.closest('tr');
            const status = row.dataset.status;
            openScheduleModal(id, name, status);
        });
    });
    
    tbody.querySelectorAll('.reject-applicant').forEach(btn => {
        btn.addEventListener('click', function() {
            rejectApplicant(this.dataset.id, this.dataset.name);
        });
    });
}

// ============================================
// RENDER PAGINATION
// ============================================

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const info = document.getElementById('tableInfo');
    
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        info.textContent = `Showing ${pagination?.totalRecords || 0} applicants`;
        return;
    }
    
    info.textContent = `Showing page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} total)`;
    
    let html = '';
    
    if (pagination.currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
    }
    
    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);
    
    if (start > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let i = start; i <= end; i++) {
        if (i === pagination.currentPage) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }
    
    if (end < pagination.totalPages) {
        if (end < pagination.totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
    }
    
    if (pagination.currentPage < pagination.totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage + 1}">»</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">»</span></li>`;
    }
    
    container.innerHTML = html;
    
    container.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            loadApplicants(page);
        });
    });
}

// ============================================
// RENDER STATS
// ============================================

function renderStats(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statPending').textContent = stats.pending || 0;
    document.getElementById('statScheduled').textContent = stats.scheduled || 0;
    document.getElementById('statPassed').textContent = stats.passed || 0;
    document.getElementById('statRejected').textContent = stats.rejected || 0;
    document.getElementById('statHired').textContent = stats.hired || 0;
}

// ============================================
// VIEW APPLICANT DETAIL
// ============================================

function viewApplicant(id) {
    const modal = document.getElementById('applicantDetailModal');
    const body = document.getElementById('applicantDetailBody');
    
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;
    
    new bootstrap.Modal(modal).show();
    
    fetch(`?page=api_get_applicant&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderApplicantDetail(data.data.applicant);
            } else {
                body.innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                        ${data.message || 'Failed to load applicant details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            body.innerHTML = `
                <div class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-3 d-block"></i>
                    An error occurred.
                </div>
            `;
        });
}

// Statuses where the applicant is between two interview stages: the
// last interview is resolved but the pipeline isn't finished, so the
// timeline should show a hollow "up next" node instead of just
// stopping and leaving the viewer to guess what happens next.
const NEXT_STAGE_BY_STATUS = {
    'initial_passed': { label: 'Final Interview', sub: 'Not yet scheduled' },
    'screening': { label: 'Final Interview', sub: 'Awaiting training completion' },
    'screening_success': { label: 'Final Interview', sub: 'Not yet scheduled' },
    'final_passed': { label: 'Job Offer', sub: 'Awaiting contract' },
    'contract_offered': { label: 'Onboarding', sub: 'Awaiting acceptance' },
};

function renderApplicantDetail(applicant) {
    const body = document.getElementById('applicantDetailBody');

    let interviewsHtml = '';
    if (applicant.interviews && applicant.interviews.length > 0) {
        const sorted = [...applicant.interviews].sort((a, b) => new Date(a.scheduled_date) - new Date(b.scheduled_date));
        const nextStage = NEXT_STAGE_BY_STATUS[applicant.status] || null;

        const stepsHtml = sorted.map(i => {
            const typeLabel = (i.interview_type === 'final' ? 'Final' : 'Initial') + ' Interview';
            const hrName = (i.hr_name || '').trim();
            const isCurrent = i.result !== 'passed' && i.result !== 'failed';
            const dotClass = i.result === 'passed' ? 'passed' : i.result === 'failed' ? 'failed' : 'pending';
            const dotIcon = i.result === 'passed' ? 'bi-check-lg' : i.result === 'failed' ? 'bi-x-lg' : 'bi-hourglass-split';
            const subLine = i.result === 'passed'
                ? `Passed${hrName ? ' &middot; ' + escapeHtml(hrName) : ''}`
                : i.result === 'failed'
                ? `Failed${hrName ? ' &middot; ' + escapeHtml(hrName) : ''}`
                : `Waiting for result${hrName ? ' &middot; ' + escapeHtml(hrName) : ''}`;
            return `
                <div class="timeline-item ${isCurrent ? 'is-current' : ''}">
                    <div class="timeline-dot-col">
                        <div class="timeline-dot ${dotClass}"><i class="bi ${dotIcon}"></i></div>
                        <div class="timeline-connector ${dotClass}"></div>
                    </div>
                    <div class="timeline-content">
                        <div>
                            <div class="timeline-title">${typeLabel}</div>
                            <div class="timeline-sub">${subLine}</div>
                        </div>
                        <div class="timeline-date">${new Date(i.scheduled_date).toLocaleString()}</div>
                    </div>
                </div>
            `;
        }).join('');

        const nextStageHtml = nextStage ? `
            <div class="timeline-item is-upcoming">
                <div class="timeline-dot-col">
                    <div class="timeline-dot upcoming"></div>
                </div>
                <div class="timeline-content">
                    <div>
                        <div class="timeline-title">${escapeHtml(nextStage.label)}</div>
                        <div class="timeline-sub">${escapeHtml(nextStage.sub)}</div>
                    </div>
                </div>
            </div>
        ` : '';

        interviewsHtml = `
            <h6 class="mt-3">Interview History</h6>
            <div class="interview-timeline">
                ${stepsHtml}
                ${nextStageHtml}
            </div>
        `;
    }
    
    body.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> ${escapeHtml(applicant.first_name)} ${escapeHtml(applicant.last_name)}</p>
                <p><strong>Email:</strong> ${escapeHtml(applicant.email)}</p>
                <p><strong>Phone:</strong> ${escapeHtml(applicant.phone || 'N/A')}</p>
                <p><strong>Target Role:</strong> ${escapeHtml(applicant.target_role)}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge bg-${applicant.status === 'hired' ? 'success' : applicant.status === 'pending' ? 'warning' : 'secondary'}">${applicant.status_label}</span></p>
                <p><strong>Applied:</strong> ${new Date(applicant.applied_date).toLocaleDateString()}</p>
                <p><strong>Resume:</strong> <a href="${applicant.resume_url}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> View Resume</a></p>
                ${applicant.rejection_reason ? `<p><strong>Rejection Reason:</strong> ${escapeHtml(applicant.rejection_reason.reason || 'No reason provided')}</p>` : ''}
            </div>
        </div>
        ${interviewsHtml}
    `;
}

// ============================================
// SCHEDULE INTERVIEW
// ============================================

function openScheduleModal(id, name, status) {
    document.getElementById('scheduleApplicantId').value = id;
    
    // Only pending can be scheduled (should always be initial)
    document.getElementById('scheduleTypeHidden').value = 'initial';
    
    document.getElementById('scheduleDate').value = '';
    document.getElementById('scheduleGmeet').value = '';
    document.getElementById('scheduleMessage').value = '';
    
    // Set default date to tomorrow at 10am
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(10, 0, 0, 0);
    document.getElementById('scheduleDate').value = tomorrow.toISOString().slice(0, 16);
    
    new bootstrap.Modal(document.getElementById('scheduleInterviewModal')).show();
}

function scheduleInterview() {
    const form = document.getElementById('scheduleInterviewForm');
    if (!form) {
        console.error('❌ scheduleInterviewForm not found');
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const applicantName = document.querySelector('#scheduleInterviewModal .modal-title')?.textContent || 'Applicant';
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scheduling...';

    if (data.scheduled_date && !validateScheduleDate(data.scheduled_date)) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Schedule Interview';
        return;
    }
    
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
                title: 'Initial Interview Scheduled!',
                html: `
                    <p><strong>${escapeHtml(applicantName)}</strong> has been scheduled for an initial interview.</p>
                    <p class="text-muted">Next step: Go to the <strong>Interviews tab → Initial (Scheduled)</strong> to manage the interview.</p>
                `,
                confirmButtonText: 'OK'
            });
            bootstrap.Modal.getInstance(document.getElementById('scheduleInterviewModal')).hide();
            updatePendingBadge();
            loadApplicants(currentPage);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to Schedule',
                text: result.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Schedule Interview';
        console.error('❌ Fetch error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
}

// ============================================
// REJECT APPLICANT
// ============================================

function rejectApplicant(id, name) {
    const row = document.querySelector(`button[data-id="${id}"]`).closest('tr');
    if (!row) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Could not find applicant row.' });
        return;
    }
    
    const status = row.dataset.status;
    
    if (status !== 'pending') {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Reject',
            text: 'Only pending applicants can be rejected from this tab.'
        });
        return;
    }
    
    Swal.fire({
        title: 'Reject Applicant?',
        html: `Reject <strong>${name}</strong>?<br><small class="text-muted">You can provide a reason below.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel',
        input: 'textarea',
        inputPlaceholder: 'Reason for rejection (optional)',
        inputAttributes: { rows: 3 }
    }).then(result => {
        if (result.isConfirmed) {
            updateApplicantStatus(id, 'reject_initial', result.value);
        }
    });
}

function updateApplicantStatus(id, action, reason) {
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    const data = { applicant_id: id, action: action };
    if (reason) data.reason = reason;
    
    fetch('?page=api_update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        Swal.close();
        if (result.success) {
            Swal.fire({
                icon: 'info',
                title: 'Applicant Rejected',
                html: `
                    <p><strong>${escapeHtml(name)}</strong> has been rejected.</p>
                    <p class="text-muted">The applicant will no longer appear in the pending list.</p>
                `,
                confirmButtonText: 'OK'
            });
            updatePendingBadge();
            loadApplicants(currentPage);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: result.message || 'Please try again.'
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
}

// ============================================
// TRAINER SELECTION FOR TRAINEES (Handled in interviews.js now)
// ============================================

let currentApplicantId = null;

// ============================================
// HELPER FUNCTIONS
// ============================================

function canScheduleInterview(status) {
    return status === 'pending';
}

function canReject(status) {
    return status === 'pending';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Make validateDateInput globally accessible
window.validateDateInput = validateDateInput;
window.validateScheduleDate = validateScheduleDate;
window.closeDatePicker = closeDatePicker;