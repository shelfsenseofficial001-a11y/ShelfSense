// ============================================
// HR INTERVIEWS - FULL AJAX (Initial + Final + Contract)
// ============================================

console.log('✅ interviews.js loaded');

let initialPage = 1;
let finalPage = 1;
let contractPage = 1;

let initialFilters = { status: 'scheduled', search: '' };
let finalFilters = { status: 'scheduled', search: '' };
let contractFilters = { status: 'scheduled', search: '' };

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
        input.value = '';
        input.classList.add('error');
        closeDatePicker(input);
        Swal.fire({
            icon: 'warning',
            title: 'Date Too Early',
            text: '❌ Cannot select yesterday or earlier. Please select a date from tomorrow onwards.',
            timer: 3000,
            timerProgressBar: true
        });
        setTimeout(() => input.classList.remove('error'), 3000);
        return;
    }
    
    if (selected > maxDate) {
        input.value = '';
        input.classList.add('error');
        closeDatePicker(input);
        Swal.fire({
            icon: 'warning',
            title: 'Date Too Far',
            text: '❌ Cannot select beyond 3 months from now. Please select a date within 3 months.',
            timer: 3000,
            timerProgressBar: true
        });
        setTimeout(() => input.classList.remove('error'), 3000);
        return;
    }
    
    input.classList.remove('error');
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
    console.log('✅ DOM ready - interviews page');

    loadInitialInterviews();
    loadFinalInterviews();
    loadContractInterviews();
    loadAllStats();

    // Initial filter buttons
    document.querySelectorAll('.filter-btn-initial').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn-initial').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            initialFilters.status = this.dataset.status;
            initialPage = 1;
            loadInitialInterviews();
        });
    });

    document.getElementById('refreshInitialBtn').addEventListener('click', function() {
        initialFilters.search = '';
        document.getElementById('initialSearchInput').value = '';
        initialPage = 1;
        loadInitialInterviews();
    });

    let initialSearchTimeout = null;
    document.getElementById('initialSearchInput').addEventListener('input', function() {
        clearTimeout(initialSearchTimeout);
        const query = this.value.trim();
        initialSearchTimeout = setTimeout(() => {
            initialFilters.search = query;
            initialPage = 1;
            loadInitialInterviews();
        }, 300);
    });

    // Final filter buttons
    document.querySelectorAll('.filter-btn-final').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn-final').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            finalFilters.status = this.dataset.status;
            finalPage = 1;
            loadFinalInterviews();
        });
    });

    document.getElementById('refreshFinalBtn').addEventListener('click', function() {
        finalFilters.search = '';
        document.getElementById('finalSearchInput').value = '';
        finalPage = 1;
        loadFinalInterviews();
    });

    let finalSearchTimeout = null;
    document.getElementById('finalSearchInput').addEventListener('input', function() {
        clearTimeout(finalSearchTimeout);
        const query = this.value.trim();
        finalSearchTimeout = setTimeout(() => {
            finalFilters.search = query;
            finalPage = 1;
            loadFinalInterviews();
        }, 300);
    });

    // Contract filter buttons
    const contractFilterBtns = document.querySelectorAll('.filter-btn-contract');
    if (contractFilterBtns.length > 0) {
        contractFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn-contract').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                contractFilters.status = this.dataset.status;
                contractPage = 1;
                loadContractInterviews();
            });
        });
    }

    const refreshContractBtn = document.getElementById('refreshContractBtn');
    if (refreshContractBtn) {
        refreshContractBtn.addEventListener('click', function() {
            contractFilters.search = '';
            document.getElementById('contractSearchInput').value = '';
            contractPage = 1;
            loadContractInterviews();
        });
    }

    const contractSearchInput = document.getElementById('contractSearchInput');
    if (contractSearchInput) {
        let contractSearchTimeout = null;
        contractSearchInput.addEventListener('input', function() {
            clearTimeout(contractSearchTimeout);
            const query = this.value.trim();
            contractSearchTimeout = setTimeout(() => {
                contractFilters.search = query;
                contractPage = 1;
                loadContractInterviews();
            }, 300);
        });
    }

    // Tab switch events
    document.getElementById('initial-tab').addEventListener('shown.bs.tab', function() {
        loadInitialInterviews(initialPage);
    });

    document.getElementById('final-tab').addEventListener('shown.bs.tab', function() {
        loadFinalInterviews(finalPage);
    });

    const contractTab = document.getElementById('contract-tab');
    if (contractTab) {
        contractTab.addEventListener('shown.bs.tab', function() {
            loadContractInterviews(contractPage);
        });
    }

    // Modal events
    document.getElementById('setResultForm').addEventListener('submit', function(e) {
        e.preventDefault();
        setInterviewResult();
    });

    document.getElementById('scheduleInterviewModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('scheduleTypeHidden').value = 'final';
        const modalTitle = document.querySelector('#scheduleInterviewModal .modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Schedule Interview';
        }
    });

    const scheduleForm = document.getElementById('scheduleInterviewForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const type = document.getElementById('scheduleTypeHidden').value || 'final';
            scheduleInterview(type);
        });
    }

    document.getElementById('confirmCreateTraineeBtn')?.addEventListener('click', function() {
        const trainerId = document.getElementById('trainerSelect').value;
        const salaryMin = document.getElementById('traineeSalaryMin').value || 3900;
        const salaryMax = document.getElementById('traineeSalaryMax').value || 4500;

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
            html: 'This will create a trainee account with the selected trainer assigned.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Create',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                createTraineeWithTrainer(currentApplicantIdForTraining, trainerId, salaryMin, salaryMax);
            }
        });
    });

    // Initialize tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    updatePendingBadge();
});

// ============================================
// LOAD & RENDER FUNCTIONS
// ============================================

function loadInitialInterviews(page = 1) {
    initialPage = page;
    const params = new URLSearchParams({
        p: page,
        type: 'initial',
        status: initialFilters.status,
        search: initialFilters.search
    });
    const tbody = document.getElementById('initialInterviewsTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

    fetch(`?page=api_get_interviews&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const result = data.data;
                renderInitialInterviews(result.interviews);
                renderPagination(result.pagination, 'initialPaginationContainer', loadInitialInterviews);
                document.getElementById('initialTableInfo').textContent = `Showing ${result.pagination.totalRecords || 0} interviews`;
                document.getElementById('initialCount').textContent = `${result.pagination.totalRecords || 0} records`;
                updateInitialBadges(result.interviews || []);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${data.message || 'Failed to load'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading initial interviews:', error);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">An error occurred. Please try again.</td></tr>`;
        });
}

function renderInitialInterviews(interviews) {
    const tbody = document.getElementById('initialInterviewsTableBody');
    if (!tbody) return;
    if (!interviews || interviews.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No initial interviews found</td></tr>`;
        return;
    }

    let html = '';
    interviews.forEach(interview => {
        const applicantName = interview.applicant_name || 'Unknown';
        const hrName = interview.hr_name || 'Not assigned';
        const formattedDate = interview.formatted_date || '';
        const formattedTime = interview.formatted_time || '';
        const statusBadge = interview.status_color ?
            `<span class="badge bg-${interview.status_color}">${interview.status.charAt(0).toUpperCase() + interview.status.slice(1)}</span>`
            : `<span class="badge bg-secondary">Unknown</span>`;
        const resultBadge = interview.result_color ?
            `<span class="badge bg-${interview.result_color}">${interview.result ? interview.result.charAt(0).toUpperCase() + interview.result.slice(1) : '—'}</span>`
            : `<span class="badge bg-secondary">—</span>`;

        const showFinalBtn = (interview.status === 'completed' && interview.result === 'passed' && !interview.has_final_interview);

        // ✅ Locked Final button if current HR conducted the interview
        let finalButtonHtml = '';
        if (showFinalBtn) {
            if (!interview.is_current_hr) {
                finalButtonHtml = `
                    <button class="btn btn-sm btn-outline-primary schedule-final-btn"
                            data-applicant-id="${interview.applicant_id}"
                            data-name="${escapeHtml(applicantName)}">
                        <i class="bi bi-calendar-plus"></i> Final
                    </button>
                `;
            } else {
                finalButtonHtml = `
                    <button class="btn btn-sm btn-outline-secondary" disabled
                            title="You conducted the Initial interview. Another HR must conduct the Final."
                            data-bs-toggle="tooltip" data-bs-placement="top">
                        <i class="bi bi-calendar-plus"></i> Final (Locked)
                    </button>
                `;
            }
        }

        html += `
            <tr>
                <td><strong>${escapeHtml(applicantName)}</strong><br><small class="text-muted">${escapeHtml(interview.target_role || '')}</small></td>
                <td>${escapeHtml(hrName)}</td>
                <td>${formattedDate}<br><small class="text-muted">${formattedTime}</small></td>
                <td>${statusBadge}</td>
                <td>${resultBadge}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-interview" data-id="${interview.id}"><i class="bi bi-eye"></i></button>
                    ${finalButtonHtml}
                    ${interview.status === 'scheduled' ? `
                        <button class="btn btn-sm btn-outline-success set-result-btn" data-id="${interview.id}" data-applicant="${escapeHtml(applicantName)}"><i class="bi bi-check2-circle"></i></button>
                        <button class="btn btn-sm btn-outline-danger cancel-interview" data-id="${interview.id}" data-applicant="${escapeHtml(applicantName)}"><i class="bi bi-x-circle"></i></button>
                    ` : ''}
                    ${interview.status === 'completed' && interview.result === 'pending' ? `
                        <button class="btn btn-sm btn-outline-success set-result-btn" data-id="${interview.id}" data-applicant="${escapeHtml(applicantName)}"><i class="bi bi-check2-circle"></i></button>
                    ` : ''}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Attach event listeners
    document.querySelectorAll('#initialInterviewsTableBody .view-interview').forEach(btn => btn.addEventListener('click', function() { viewInterview(this.dataset.id); }));
    document.querySelectorAll('#initialInterviewsTableBody .schedule-final-btn').forEach(btn => btn.addEventListener('click', function() {
        const applicantId = this.dataset.applicantId;
        const name = this.dataset.name;
        checkAndOpenFinalModal(applicantId, name);
    }));
    document.querySelectorAll('#initialInterviewsTableBody .set-result-btn').forEach(btn => btn.addEventListener('click', function() {
        openSetResultModal(this.dataset.id, this.dataset.applicant);
    }));
    document.querySelectorAll('#initialInterviewsTableBody .cancel-interview').forEach(btn => btn.addEventListener('click', function() {
        cancelInterview(this.dataset.id, this.dataset.applicant);
    }));

    // Reinitialize tooltips for dynamically created elements
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
}

function loadFinalInterviews(page = 1) {
    finalPage = page;
    const params = new URLSearchParams({
        p: page,
        type: 'final',
        status: finalFilters.status,
        search: finalFilters.search
    });
    const tbody = document.getElementById('finalInterviewsTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

    fetch(`?page=api_get_interviews&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const result = data.data;
                renderFinalInterviews(result.interviews);
                renderPagination(result.pagination, 'finalPaginationContainer', loadFinalInterviews);
                document.getElementById('finalTableInfo').textContent = `Showing ${result.pagination.totalRecords || 0} interviews`;
                document.getElementById('finalCount').textContent = `${result.pagination.totalRecords || 0} records`;
                updateFinalBadges(result.interviews || []);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${data.message || 'Failed to load'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading final interviews:', error);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">An error occurred. Please try again.</td></tr>`;
        });
}

function renderFinalInterviews(interviews) {
    const tbody = document.getElementById('finalInterviewsTableBody');
    if (!tbody) return;
    if (!interviews || interviews.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No final interviews found</td></tr>`;
        return;
    }

    let html = '';
    interviews.forEach(interview => {
        const applicantName = interview.applicant_name || 'Unknown';
        const hrName = interview.hr_name || 'Not assigned';
        const formattedDate = interview.formatted_date || '';
        const formattedTime = interview.formatted_time || '';
        const statusBadge = interview.status_color ?
            `<span class="badge bg-${interview.status_color}">${interview.status.charAt(0).toUpperCase() + interview.status.slice(1)}</span>`
            : `<span class="badge bg-secondary">Unknown</span>`;
        const resultBadge = interview.result_color ?
            `<span class="badge bg-${interview.result_color}">${interview.result ? interview.result.charAt(0).toUpperCase() + interview.result.slice(1) : '—'}</span>`
            : `<span class="badge bg-secondary">—</span>`;

        html += `
            <tr>
                <td><strong>${escapeHtml(applicantName)}</strong><br><small class="text-muted">${escapeHtml(interview.target_role || '')}</small></td>
                <td>${escapeHtml(hrName)}</td>
                <td>${formattedDate}<br><small class="text-muted">${formattedTime}</small></td>
                <td>${statusBadge}</td>
                <td>${resultBadge}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-interview" data-id="${interview.id}"><i class="bi bi-eye"></i></button>
                    ${interview.status === 'completed' && interview.result === 'passed' && !interview.has_trainee_account ? `
                        <button class="btn btn-sm btn-outline-success move-to-training-btn"
                                data-applicant-id="${interview.applicant_id}"
                                data-name="${escapeHtml(applicantName)}"
                                data-role="${escapeHtml(interview.target_role || '')}">
                            <i class="bi bi-box-arrow-in-right"></i> Training
                        </button>
                    ` : ''}
                    ${interview.status === 'scheduled' ? `
                        <button class="btn btn-sm btn-outline-success set-result-btn" data-id="${interview.id}" data-applicant="${escapeHtml(applicantName)}"><i class="bi bi-check2-circle"></i></button>
                        <button class="btn btn-sm btn-outline-danger cancel-interview" data-id="${interview.id}" data-applicant="${escapeHtml(applicantName)}"><i class="bi bi-x-circle"></i></button>
                    ` : ''}
                    ${interview.status === 'completed' && interview.result === 'pending' ? `
                        <button class="btn btn-sm btn-outline-success set-result-btn" data-id="${interview.id}" data-applicant="${escapeHtml(applicantName)}"><i class="bi bi-check2-circle"></i></button>
                    ` : ''}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    document.querySelectorAll('#finalInterviewsTableBody .view-interview').forEach(btn => btn.addEventListener('click', function() { viewInterview(this.dataset.id); }));
    document.querySelectorAll('#finalInterviewsTableBody .move-to-training-btn').forEach(btn => btn.addEventListener('click', function() {
        const applicantId = this.dataset.applicantId;
        const name = this.dataset.name;
        const role = this.dataset.role;
        openTrainerSelectionModalFromFinal(applicantId, name, role);
    }));
    document.querySelectorAll('#finalInterviewsTableBody .set-result-btn').forEach(btn => btn.addEventListener('click', function() {
        openSetResultModal(this.dataset.id, this.dataset.applicant);
    }));
    document.querySelectorAll('#finalInterviewsTableBody .cancel-interview').forEach(btn => btn.addEventListener('click', function() {
        cancelInterview(this.dataset.id, this.dataset.applicant);
    }));
}

function loadContractInterviews(page = 1) {
    contractPage = page;
    const params = new URLSearchParams({
        p: page,
        type: 'contract',
        status: contractFilters.status,
        search: contractFilters.search
    });
    const tbody = document.getElementById('contractInterviewsTableBody');
    if (!tbody) {
        console.error('❌ contractInterviewsTableBody not found');
        return;
    }
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

    fetch(`?page=api_get_interviews&${params}`)
        .then(response => response.json())
        .then(data => {
            console.log('🔍 Contract interviews API response:', data); // Debug
            if (data.success) {
                const result = data.data;
                renderContractInterviews(result.interviews);
                renderPagination(result.pagination, 'contractPaginationContainer', loadContractInterviews);
                document.getElementById('contractTableInfo').textContent = `Showing ${result.pagination.totalRecords || 0} interviews`;
                document.getElementById('contractCount').textContent = `${result.pagination.totalRecords || 0} records`;
                updateContractBadges(result.interviews || []);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${data.message || 'Failed to load'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading contract interviews:', error);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">An error occurred. Please try again.</td></tr>`;
        });
}

function renderContractInterviews(interviews) {
    const tbody = document.getElementById('contractInterviewsTableBody');
    if (!tbody) return;
    if (!interviews || interviews.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No contract interviews found</td></tr>`;
        return;
    }

    let html = '';
    interviews.forEach(interview => {
        const applicantName = interview.applicant_name || 'Unknown';
        const hrName = interview.hr_name || 'Not assigned';
        const formattedDate = interview.formatted_date || '';
        const formattedTime = interview.formatted_time || '';
        const statusBadge = interview.status_color ?
            `<span class="badge bg-${interview.status_color}">${interview.status.charAt(0).toUpperCase() + interview.status.slice(1)}</span>`
            : `<span class="badge bg-secondary">Unknown</span>`;
        const resultBadge = interview.result_color ?
            `<span class="badge bg-${interview.result_color}">${interview.result ? interview.result.charAt(0).toUpperCase() + interview.result.slice(1) : '—'}</span>`
            : `<span class="badge bg-secondary">—</span>`;

        let actionsHtml = `
            <button class="btn btn-sm btn-outline-primary view-interview" data-id="${interview.id}"><i class="bi bi-eye"></i></button>
        `;
        if (interview.status === 'scheduled' && !interview.has_contract) {
            actionsHtml += `
                <button class="btn btn-sm btn-outline-success create-contract-btn"
                        data-applicant-id="${interview.applicant_id}"
                        data-interview-id="${interview.id}"
                        data-name="${escapeHtml(applicantName)}">
                    <i class="bi bi-file-text"></i> Create Contract
                </button>
            `;
        }
        if (interview.has_contract) {
            actionsHtml += ` <span class="badge bg-success">✅ Contract</span>`;
        }

        html += `
            <tr>
                <td><strong>${escapeHtml(applicantName)}</strong><br><small class="text-muted">${escapeHtml(interview.target_role || '')}</small></td>
                <td>${escapeHtml(hrName)}</td>
                <td>${formattedDate}<br><small class="text-muted">${formattedTime}</small></td>
                <td>${statusBadge}</td>
                <td>${resultBadge}</td>
                <td class="text-center">${actionsHtml}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Attach events
    document.querySelectorAll('#contractInterviewsTableBody .view-interview').forEach(btn => btn.addEventListener('click', function() { viewInterview(this.dataset.id); }));
    document.querySelectorAll('#contractInterviewsTableBody .create-contract-btn').forEach(btn => btn.addEventListener('click', function() {
        const applicantId = this.dataset.applicantId;
        const interviewId = this.dataset.interviewId;
        const name = this.dataset.name;
        createContractFromInterview(applicantId, interviewId, name);
    }));
}

// ============================================
// BADGE UPDATES
// ============================================

function updateInitialBadges() {
    fetch(`?page=api_get_interviews&p=1&type=initial&status=all&search=`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const interviews = data.data.interviews || [];
                const scheduled = interviews.filter(i => i.status === 'scheduled').length;
                const needsFinal = interviews.filter(i => i.status === 'completed' && i.result === 'passed' && !i.has_final_interview).length;
                // Update badge elements
                const scheduledBadge = document.getElementById('initialScheduledBadge');
                if (scheduledBadge) {
                    scheduledBadge.textContent = scheduled;
                    scheduledBadge.style.display = scheduled > 0 ? 'inline-block' : 'none';
                }
                const completedBadge = document.getElementById('initialCompletedBadge');
                if (completedBadge) {
                    completedBadge.textContent = needsFinal;
                    completedBadge.style.display = needsFinal > 0 ? 'inline-block' : 'none';
                }
                const tabBadge = document.getElementById('initialTabBadge');
                if (tabBadge) {
                    if (scheduled > 0) {
                        tabBadge.textContent = scheduled;
                        tabBadge.style.display = 'inline-block';
                    } else {
                        tabBadge.style.display = 'none';
                    }
                }
            }
        })
        .catch(() => {});
}

function updateFinalBadges() {
    fetch(`?page=api_get_interviews&p=1&type=final&status=all&search=`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const interviews = data.data.interviews || [];
                const scheduled = interviews.filter(i => i.status === 'scheduled').length;
                const completed = interviews.filter(i => i.status === 'completed').length;
                const scheduledBadge = document.getElementById('finalScheduledBadge');
                if (scheduledBadge) {
                    scheduledBadge.textContent = scheduled;
                    scheduledBadge.style.display = scheduled > 0 ? 'inline-block' : 'none';
                }
                const completedBadge = document.getElementById('finalCompletedBadge');
                if (completedBadge) {
                    completedBadge.textContent = completed;
                    completedBadge.style.display = completed > 0 ? 'inline-block' : 'none';
                }
                const tabBadge = document.getElementById('finalTabBadge');
                if (tabBadge) {
                    if (scheduled > 0) {
                        tabBadge.textContent = scheduled;
                        tabBadge.style.display = 'inline-block';
                    } else {
                        tabBadge.style.display = 'none';
                    }
                }
            }
        })
        .catch(() => {});
}

function updateContractBadges() {
    fetch(`?page=api_get_interviews&p=1&type=contract&status=all&search=`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const interviews = data.data.interviews || [];
                const scheduled = interviews.filter(i => i.status === 'scheduled').length;
                const completed = interviews.filter(i => i.status === 'completed').length;
                const scheduledBadge = document.getElementById('contractScheduledBadge');
                if (scheduledBadge) {
                    scheduledBadge.textContent = scheduled;
                    scheduledBadge.style.display = scheduled > 0 ? 'inline-block' : 'none';
                }
                const completedBadge = document.getElementById('contractCompletedBadge');
                if (completedBadge) {
                    completedBadge.textContent = completed;
                    completedBadge.style.display = completed > 0 ? 'inline-block' : 'none';
                }
                const tabBadge = document.getElementById('contractTabBadge');
                if (tabBadge) {
                    if (scheduled > 0) {
                        tabBadge.textContent = scheduled;
                        tabBadge.style.display = 'inline-block';
                    } else {
                        tabBadge.style.display = 'none';
                    }
                }
            }
        })
        .catch(() => {});
}

// ============================================
// LOAD ALL STATS
// ============================================

function loadAllStats() {
    fetch(`?page=api_get_interviews&p=1&type=all&status=all&search=`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const interviews = data.data.interviews || [];
                const initialScheduled = interviews.filter(i => i.interview_type === 'initial' && i.status === 'scheduled').length;
                const initialCompleted = interviews.filter(i => i.interview_type === 'initial' && i.status === 'completed').length;
                const finalScheduled = interviews.filter(i => i.interview_type === 'final' && i.status === 'scheduled').length;
                const finalCompleted = interviews.filter(i => i.interview_type === 'final' && i.status === 'completed').length;
                const contractScheduled = interviews.filter(i => i.interview_type === 'contract' && i.status === 'scheduled').length;
                const contractCompleted = interviews.filter(i => i.interview_type === 'contract' && i.status === 'completed').length;
                document.getElementById('statInitialScheduled').textContent = initialScheduled;
                document.getElementById('statInitialCompleted').textContent = initialCompleted;
                document.getElementById('statFinalScheduled').textContent = finalScheduled;
                document.getElementById('statFinalCompleted').textContent = finalCompleted;
                document.getElementById('statContractScheduled').textContent = contractScheduled;
                document.getElementById('statContractCompleted').textContent = contractCompleted;
                updateInitialBadges(interviews.filter(i => i.interview_type === 'initial'));
                updateFinalBadges(interviews.filter(i => i.interview_type === 'final'));
                updateContractBadges(interviews.filter(i => i.interview_type === 'contract'));
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// ============================================
// SCHEDULE FUNCTIONS
// ============================================

function checkAndOpenFinalModal(applicantId, applicantName) {
    fetch(`?page=api_get_interviews&p=1&type=final&status=all&search=`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const existingFinal = data.data.interviews.filter(i => i.applicant_id == applicantId);
                if (existingFinal.length > 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Final Interview Exists',
                        text: 'This applicant already has a final interview scheduled. You can update it.',
                        confirmButtonText: 'OK, Update',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel'
                    }).then(result => {
                        if (result.isConfirmed) {
                            openScheduleFinalModal(applicantId, applicantName);
                        }
                    });
                } else {
                    openScheduleFinalModal(applicantId, applicantName);
                }
            } else {
                openScheduleFinalModal(applicantId, applicantName);
            }
        })
        .catch(() => {
            openScheduleFinalModal(applicantId, applicantName);
        });
}

function openScheduleFinalModal(applicantId, applicantName) {
    document.getElementById('scheduleApplicantId').value = applicantId;
    document.getElementById('scheduleTypeHidden').value = 'final';
    document.getElementById('scheduleType').value = 'final';
    document.getElementById('scheduleDate').value = '';
    document.getElementById('scheduleGmeet').value = '';
    document.getElementById('scheduleMessage').value = '';
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(10, 0, 0, 0);
    document.getElementById('scheduleDate').value = tomorrow.toISOString().slice(0, 16);
    const modalTitle = document.querySelector('#scheduleInterviewModal .modal-title');
    if (modalTitle) modalTitle.textContent = `Schedule Final Interview for ${applicantName}`;
    new bootstrap.Modal(document.getElementById('scheduleInterviewModal')).show();
}

function scheduleInterview(type) {
    const form = document.getElementById('scheduleInterviewForm');
    if (!form) {
        console.error('❌ scheduleInterviewForm not found');
        return;
    }
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.interview_type = type || 'final';

    if (data.scheduled_date && !validateScheduleDate(data.scheduled_date)) {
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scheduling...';
    const applicantName = document.querySelector('#scheduleInterviewModal .modal-title')?.textContent || 'Applicant';

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
                title: 'Final Interview Scheduled!',
                html: `
                    <p><strong>${escapeHtml(applicantName)}</strong> has been scheduled for a final interview.</p>
                    <p class="text-muted">Next step: Go to <strong>Interviews tab → Final (Scheduled)</strong> to manage the interview.</p>
                `,
                confirmButtonText: 'OK'
            });
            bootstrap.Modal.getInstance(document.getElementById('scheduleInterviewModal')).hide();
            loadInitialInterviews(initialPage);
            loadFinalInterviews(finalPage);
            loadContractInterviews(contractPage);
            loadAllStats();
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to Schedule', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Schedule Interview';
        console.error('❌ Fetch error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// SET RESULT
// ============================================

function openSetResultModal(id, applicant) {
    document.getElementById('resultInterviewId').value = id;
    document.getElementById('resultValue').value = '';
    document.getElementById('resultNotes').value = '';
    document.querySelector('#setResultModal .modal-title').textContent = `Set Result - ${applicant}`;
    new bootstrap.Modal(document.getElementById('setResultModal')).show();
}

function setInterviewResult() {
    const form = document.getElementById('setResultForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.action = 'set_result';
    const applicantName = document.querySelector('#setResultModal .modal-title')?.textContent || 'Applicant';

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('?page=api_update_interview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Result';
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Result Saved!',
                html: `
                    <p><strong>${escapeHtml(applicantName)}</strong> has been updated.</p>
                    <p class="text-muted">The applicant has been moved to the appropriate stage.</p>
                `,
                confirmButtonText: 'OK'
            });
            bootstrap.Modal.getInstance(document.getElementById('setResultModal')).hide();
            loadInitialInterviews(initialPage);
            loadFinalInterviews(finalPage);
            loadContractInterviews(contractPage);
            loadAllStats();
            updatePendingBadge();
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to Save', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Result';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// CREATE CONTRACT FROM INTERVIEW
// ============================================

function createContractFromInterview(applicantId, interviewId, name) {
    Swal.fire({
        title: 'Create Contract?',
        html: `
            <p>Create a contract for <strong>${escapeHtml(name)}</strong>?</p>
            <p class="text-muted small">This will create a pending contract in the Contracts tab.</p>
            <p class="text-muted small">You can edit the contract details after creation.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Create Contract',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            const data = { applicant_id: applicantId, interview_id: interviewId };
            Swal.fire({
                title: 'Creating...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            fetch('?page=api_create_contract_from_interview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                Swal.close();
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Contract Created!',
                        html: `
                            <p>A pending contract has been created for <strong>${escapeHtml(name)}</strong>.</p>
                            <p class="text-muted">Next step: Go to <strong>Contracts tab</strong> to edit the contract details and send it to the trainee.</p>
                        `,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        loadContractInterviews(contractPage);
                        loadAllStats();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Creation Failed', text: result.message || 'Please try again.' });
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
// PAGINATION
// ============================================

function renderPagination(pagination, containerId, loadFunction) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        return;
    }
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
            loadFunction(page);
        });
    });
}

// ============================================
// MOVE TO TRAINING
// ============================================

let currentApplicantIdForTraining = null;

function openTrainerSelectionModalFromFinal(applicantId, applicantName, targetRole) {
    currentApplicantIdForTraining = applicantId;
    document.getElementById('trainerApplicantName').textContent = applicantName;
    document.getElementById('trainerTargetRole').textContent = targetRole;
    document.getElementById('traineeSalaryMin').value = 3900;
    document.getElementById('traineeSalaryMax').value = 4500;
    loadTrainersForRole(targetRole);
    new bootstrap.Modal(document.getElementById('trainerSelectionModal')).show();
}

function loadTrainersForRole(targetRole) {
    const select = document.getElementById('trainerSelect');
    if (!select) return;
    select.innerHTML = '<option value="">Loading trainers...</option>';
    select.disabled = true;
    fetch(`?page=api_get_trainers_by_role&role=${encodeURIComponent(targetRole)}`)
        .then(response => response.json())
        .then(data => {
            select.disabled = false;
            if (data.success && data.data.trainers.length > 0) {
                select.innerHTML = '<option value="">Select a trainer...</option>';
                data.data.trainers.forEach(trainer => {
                    select.innerHTML += `<option value="${trainer.user_id}">${trainer.first_name} ${trainer.last_name}</option>`;
                });
            } else {
                select.innerHTML = '<option value="">No trainers available for this role</option>';
            }
        })
        .catch(error => {
            select.disabled = false;
            select.innerHTML = '<option value="">Error loading trainers</option>';
            console.error('Error loading trainers:', error);
        });
}

function createTraineeWithTrainer(applicantId, trainerId, salaryMin, salaryMax) {
    const data = {
        applicant_id: applicantId,
        trainer_id: trainerId,
        salary_min: salaryMin,
        salary_max: salaryMax
    };
    const modal = bootstrap.Modal.getInstance(document.getElementById('trainerSelectionModal'));
    Swal.fire({
        title: 'Creating Account...',
        text: 'Please wait.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    fetch('?page=api_create_trainee_with_trainer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        Swal.close();
        if (result.success) {
            const passwordDisplay = result.data.default_password
                ? `<p><strong>Default Password:</strong> <code>${result.data.default_password}</code></p>
                   <p class="text-muted small">The trainee will be prompted to change their password on first login.</p>`
                : `<p class="text-muted"><strong>Note:</strong> User already had an account. No password change was made.</p>`;
            Swal.fire({
                icon: 'success',
                title: 'Trainee Account Created!',
                html: `
                    <p><strong>Employee Number:</strong> ${result.data.employee_number}</p>
                    ${passwordDisplay}
                    <p><strong>Trainer:</strong> ${result.data.trainer_name}</p>
                    <p><strong>Salary Range:</strong> ₱${result.data.salary_min} – ₱${result.data.salary_max}</p>
                    <p class="text-muted">The trainee will appear in the Trainees tab.</p>
                `,
                confirmButtonText: 'OK'
            }).then(() => {
                if (modal) modal.hide();
                loadInitialInterviews(initialPage);
                loadFinalInterviews(finalPage);
                loadContractInterviews(contractPage);
                loadAllStats();
                updatePendingBadge();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Creation Failed', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// CANCEL & UPDATE INTERVIEW
// ============================================

function cancelInterview(id, applicant) {
    Swal.fire({
        title: 'Cancel Interview?',
        html: `Cancel the interview for <strong>${applicant}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No, Keep'
    }).then(result => {
        if (result.isConfirmed) {
            updateInterviewStatus(id, 'cancel', null);
        }
    });
}

function updateInterviewStatus(id, action, result) {
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    const data = { interview_id: id, action: action };
    if (result) data.result = result;
    fetch('?page=api_update_interview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        Swal.close();
        if (result.success) {
            Swal.fire({ icon: 'success', title: 'Updated!', text: result.message, timer: 1500, showConfirmButton: false });
            loadInitialInterviews(initialPage);
            loadFinalInterviews(finalPage);
            loadContractInterviews(contractPage);
            loadAllStats();
        } else {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: result.message || 'Please try again.' });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
    });
}

// ============================================
// VIEW DETAIL
// ============================================

function viewInterview(id) {
    const modal = document.getElementById('interviewDetailModal');
    const body = document.getElementById('interviewDetailBody');
    if (!body || !modal) return;
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    new bootstrap.Modal(modal).show();
    fetch(`?page=api_get_interviews&p=1&type=all&status=all&search=`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const interview = data.data.interviews.find(i => i.id == id);
                if (interview) {
                    body.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Applicant:</strong> ${escapeHtml(interview.applicant_name)}</p>
                                <p><strong>Email:</strong> ${escapeHtml(interview.applicant_email)}</p>
                                <p><strong>Role:</strong> ${escapeHtml(interview.target_role)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Type:</strong> ${interview.type_label}</p>
                                <p><strong>Date:</strong> ${interview.formatted_date} at ${interview.formatted_time}</p>
                                <p><strong>HR:</strong> ${escapeHtml(interview.hr_name || 'Not assigned')}</p>
                                <p><strong>Status:</strong> <span class="badge bg-${interview.status_color}">${interview.status}</span></p>
                                <p><strong>Result:</strong> <span class="badge bg-${interview.result_color}">${interview.result || 'Pending'}</span></p>
                            </div>
                        </div>
                        ${interview.gmeet_link ? `<div class="mt-3"><p><strong>Gmeet Link:</strong> <a href="${interview.gmeet_link}" target="_blank">${interview.gmeet_link}</a></p></div>` : ''}
                        ${interview.message ? `<div class="mt-3"><p><strong>Message:</strong></p><div class="p-3 rounded" style="background: var(--bg-card-subtle); color: var(--text-main);">${escapeHtml(interview.message)}</div></div>` : ''}
                        ${interview.notes ? `<div class="mt-3"><p><strong>HR Notes:</strong></p><div class="p-3 rounded" style="background: var(--bg-card-subtle); color: var(--text-main);">${escapeHtml(interview.notes)}</div></div>` : ''}
                    `;
                } else {
                    body.innerHTML = `<div class="text-center text-danger py-4">Interview not found</div>`;
                }
            }
        })
        .catch(() => {
            body.innerHTML = `<div class="text-center text-danger py-4">Error loading details</div>`;
        });
}

// ============================================
// UPDATE PENDING BADGE
// ============================================

function updatePendingBadge() {
    const badge = document.getElementById('pendingBadge');
    if (!badge) return;
    fetch('?page=api_get_applicants&p=1&status=all&role=all&search=')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pending = data.data.stats?.pending || 0;
                if (pending > 0) {
                    badge.textContent = pending;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(() => {});
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// AUTOCOMPLETE FOR INTERVIEWS SEARCH
// ============================================

// Helper: fetch suggestions for a given input and dropdown
function fetchInterviewAutocomplete(query, dropdownId, type) {
    const dropdown = document.getElementById(dropdownId);
    if (!query || query.length < 2) {
        dropdown.classList.remove('show');
        return;
    }

    const params = new URLSearchParams({
        p: 1,
        type: type,
        status: 'all',
        search: query
    });

    fetch(`?page=api_get_interviews&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const results = data.data.interviews || [];
                renderInterviewAutocomplete(results, dropdownId);
            }
        })
        .catch(error => console.error('Autocomplete error:', error));
}

function renderInterviewAutocomplete(results, dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (!results || results.length === 0) {
        dropdown.innerHTML = `<div class="no-results">No interviews found</div>`;
        dropdown.classList.add('show');
        return;
    }

    let html = '';
    results.forEach((interview, index) => {
        const name = interview.applicant_name || 'Unknown';
        const role = interview.target_role || '';
        html += `
            <div class="item" data-index="${index}" data-id="${interview.id}">
                <div class="item-name">${escapeHtml(name)}</div>
                <div class="item-email">${escapeHtml(interview.applicant_email)}</div>
                <div class="item-role">${escapeHtml(role)}</div>
            </div>
        `;
    });

    dropdown.innerHTML = html;
    dropdown.classList.add('show');

    dropdown.querySelectorAll('.item').forEach(item => {
        item.addEventListener('click', function() {
            const name = this.querySelector('.item-name').textContent;
            const input = document.getElementById(this.closest('.autocomplete-wrapper').querySelector('input').id);
            input.value = name;
            dropdown.classList.remove('show');
            // Trigger search
            const event = new Event('input', { bubbles: true });
            input.dispatchEvent(event);
        });
    });
}

// Attach to each search input
document.addEventListener('DOMContentLoaded', function() {
    // Initial Interviews search
    const initialInput = document.getElementById('initialSearchInput');
    if (initialInput) {
        let timeout = null;
        initialInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            if (query.length < 2) {
                document.getElementById('initialAutocompleteDropdown').classList.remove('show');
                return;
            }
            timeout = setTimeout(() => {
                fetchInterviewAutocomplete(query, 'initialAutocompleteDropdown', 'initial');
            }, 300);
        });
    }

    // Final Interviews search
    const finalInput = document.getElementById('finalSearchInput');
    if (finalInput) {
        let timeout = null;
        finalInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            if (query.length < 2) {
                document.getElementById('finalAutocompleteDropdown').classList.remove('show');
                return;
            }
            timeout = setTimeout(() => {
                fetchInterviewAutocomplete(query, 'finalAutocompleteDropdown', 'final');
            }, 300);
        });
    }

    // Contract Interviews search
    const contractInput = document.getElementById('contractSearchInput');
    if (contractInput) {
        let timeout = null;
        contractInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            if (query.length < 2) {
                document.getElementById('contractAutocompleteDropdown').classList.remove('show');
                return;
            }
            timeout = setTimeout(() => {
                fetchInterviewAutocomplete(query, 'contractAutocompleteDropdown', 'contract');
            }, 300);
        });
    }

    // Click outside to close dropdowns
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.autocomplete-dropdown').forEach(dropdown => {
            if (!dropdown.closest('.autocomplete-wrapper').contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    });
});

// Make validateDateInput globally accessible
window.validateDateInput = validateDateInput;
window.validateScheduleDate = validateScheduleDate;
window.closeDatePicker = closeDatePicker;