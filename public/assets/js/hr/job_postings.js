// ============================================
// HR - JOB POSTINGS
// ============================================

let jpPage = 1;
let jpBusy = false;
let jpCurrentDetail = null;

document.addEventListener('DOMContentLoaded', function () {
    loadPostings(1);
    setupFilters();
    setupForm();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'status', type: 'select', elementId: 'filterStatus', defaultValue: 'all' },
            { key: 'search', type: 'search', elementId: 'searchInput' },
            { key: 'mine', type: 'checkbox', elementId: 'mineOnly', label: 'My postings only' },
        ]);
    }

    document.getElementById('confirmRejectPostingBtn')?.addEventListener('click', submitReject);
});

function jpEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function jpCurrency(v) {
    if (v === null || v === undefined || v === '') return '—';
    return '₱' + parseFloat(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function jpFormatDate(d, withTime = false) {
    if (!d) return '—';
    const date = new Date(String(d).replace(' ', 'T'));
    if (isNaN(date.getTime())) return jpEscapeHtml(d);
    const opts = { year: 'numeric', month: 'short', day: 'numeric' };
    if (withTime) { opts.hour = '2-digit'; opts.minute = '2-digit'; }
    return date.toLocaleDateString('en-US', opts);
}

const JP_STATUS_LABELS = {
    draft: 'Draft', pending_approval: 'Pending Approval', approved: 'Approved',
    rejected: 'Rejected', closed: 'Closed', archived: 'Archived'
};
const JP_STATUS_CLASS = {
    draft: 'secondary', pending_approval: 'warning', approved: 'success',
    rejected: 'danger', closed: 'dark', archived: 'secondary'
};
function jpStatusBadge(status) {
    return `<span class="badge bg-${JP_STATUS_CLASS[status] || 'secondary'}">${JP_STATUS_LABELS[status] || status}</span>`;
}

function jpDebounce(fn, wait) {
    let t;
    return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
}

function setupFilters() {
    document.getElementById('filterStatus')?.addEventListener('change', () => loadPostings(1));
    document.getElementById('searchInput')?.addEventListener('input', jpDebounce(() => loadPostings(1), 400));
    document.getElementById('mineOnly')?.addEventListener('change', () => loadPostings(1));
    document.getElementById('refreshBtn')?.addEventListener('click', () => loadPostings(jpPage));
    document.getElementById('createBtn')?.addEventListener('click', () => openFormModal(null));
}

function loadPostings(page) {
    jpPage = page;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('searchInput').value.trim();
    const mine = document.getElementById('mineOnly').checked;

    const tbody = document.getElementById('postingsTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

    const params = new URLSearchParams({ p: page, limit: 10, status });
    if (search) params.append('search', search);
    if (mine) params.append('mine', '1');

    fetch(`?page=api_hr_get_job_postings&${params}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${jpEscapeHtml(data.message)}</td></tr>`; return; }
            renderTable(data.data.postings);
            renderStats(data.data.counts);
            renderPagination(data.data.pagination);
        })
        .catch(() => { tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">An error occurred. Please try again.</td></tr>`; });
}

function renderStats(c) {
    if (!c) return;
    document.getElementById('statDraft').textContent = c.draft ?? 0;
    document.getElementById('statPending').textContent = c.pending_approval ?? 0;
    document.getElementById('statApproved').textContent = c.approved ?? 0;
    document.getElementById('statRejected').textContent = c.rejected ?? 0;
    document.getElementById('statClosed').textContent = c.closed ?? 0;
    document.getElementById('statArchived').textContent = c.archived ?? 0;
}

function renderTable(postings) {
    const tbody = document.getElementById('postingsTableBody');
    if (!postings || postings.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No job postings found.</td></tr>`;
        return;
    }
    tbody.innerHTML = postings.map(p => `
        <tr>
            <td><strong>${jpEscapeHtml(p.title)}</strong>${p.reused_from_id ? ' <span class="badge bg-info-subtle text-info-emphasis" title="Reused from an earlier posting">reused</span>' : ''}</td>
            <td>${jpEscapeHtml(p.department)}</td>
            <td>${jpFormatDate(p.open_until)}</td>
            <td>${jpEscapeHtml(p.creator_first)} ${jpEscapeHtml(p.creator_last)}</td>
            <td>${jpStatusBadge(p.status)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary view-posting-btn" data-id="${p.id}"><i class="bi bi-eye"></i></button>
            </td>
        </tr>
    `).join('');

    tbody.querySelectorAll('.view-posting-btn').forEach(btn => {
        btn.addEventListener('click', () => viewPosting(parseInt(btn.dataset.id)));
    });
}

function renderPagination(p) {
    const container = document.getElementById('paginationContainer');
    const info = document.getElementById('tableInfo');
    if (!p || p.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        info.textContent = `${p?.totalRecords || 0} postings`;
        return;
    }
    info.textContent = `Page ${p.currentPage} of ${p.totalPages} (${p.totalRecords} postings)`;
    let html = '';
    for (let i = 1; i <= p.totalPages; i++) {
        html += `<li class="page-item ${i === p.currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    container.innerHTML = html;
    container.querySelectorAll('.page-link').forEach(a => {
        a.addEventListener('click', e => { e.preventDefault(); loadPostings(parseInt(a.dataset.page)); });
    });
}

// ============================================
// CREATE / EDIT FORM
// ============================================

function setupForm() {
    document.getElementById('postingForm').addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(false);
    });
    document.getElementById('saveAndSubmitBtn').addEventListener('click', function () {
        submitForm(true);
    });
}

function openFormModal(posting) {
    const form = document.getElementById('postingForm');
    form.reset();
    document.getElementById('postingFormAlert').innerHTML = '';
    document.getElementById('postingId').value = posting ? posting.id : '';
    document.getElementById('postingFormTitle').textContent = posting ? 'Edit Job Posting' : 'New Job Posting';
    document.getElementById('postingTitle').value = posting ? posting.title : '';
    document.getElementById('postingDepartment').value = posting ? posting.department : '';
    window.refreshSearchableSelect && window.refreshSearchableSelect('postingDepartment');
    document.getElementById('postingRole').value = posting ? posting.role : '';
    document.getElementById('postingLocation').value = posting ? (posting.location || '') : '';
    document.getElementById('postingEmploymentType').value = posting ? (posting.employment_type || 'Full-Time') : 'Full-Time';
    document.getElementById('postingSlots').value = posting && posting.slots !== null ? posting.slots : '';
    document.getElementById('postingDescription').value = posting ? posting.description : '';
    document.getElementById('postingRequirements').value = posting ? (posting.requirements || '') : '';
    document.getElementById('postingResponsibilities').value = posting ? (posting.responsibilities || '') : '';
    document.getElementById('postingSalaryMin').value = posting ? (posting.salary_range_min || '') : '';
    document.getElementById('postingSalaryMax').value = posting ? (posting.salary_range_max || '') : '';

    const openUntilInput = document.getElementById('postingOpenUntil');
    const today = new Date();
    const maxDate = new Date();
    maxDate.setMonth(maxDate.getMonth() + 6);
    const toIso = d => d.toISOString().slice(0, 10);
    openUntilInput.min = toIso(today);
    openUntilInput.max = toIso(maxDate);
    openUntilInput.value = posting ? posting.open_until : '';

    bootstrap.Modal.getInstance(document.getElementById('postingDetailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('postingFormModal')).show();
}

function collectFormPayload() {
    return {
        id: document.getElementById('postingId').value || undefined,
        title: document.getElementById('postingTitle').value.trim(),
        department: document.getElementById('postingDepartment').value.trim(),
        role: document.getElementById('postingRole').value.trim(),
        location: document.getElementById('postingLocation').value.trim(),
        employment_type: document.getElementById('postingEmploymentType').value,
        slots: document.getElementById('postingSlots').value,
        description: document.getElementById('postingDescription').value.trim(),
        requirements: document.getElementById('postingRequirements').value.trim(),
        responsibilities: document.getElementById('postingResponsibilities').value.trim(),
        salary_range_min: document.getElementById('postingSalaryMin').value,
        salary_range_max: document.getElementById('postingSalaryMax').value,
        open_until: document.getElementById('postingOpenUntil').value
    };
}

function submitForm(alsoSubmit) {
    if (jpBusy) return;
    jpBusy = true;
    const payload = collectFormPayload();
    const isEdit = !!payload.id;
    const alertBox = document.getElementById('postingFormAlert');
    alertBox.innerHTML = '';

    const url = isEdit ? '?page=api_hr_update_job_posting' : '?page=api_hr_create_job_posting';
    if (!isEdit) payload.submit = false;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(data => {
            jpBusy = false;
            if (!data.success) {
                const errs = data.data ? Object.values(data.data).join(' ') : '';
                alertBox.innerHTML = `<div class="alert alert-danger small">${jpEscapeHtml(data.message)} ${jpEscapeHtml(errs)}</div>`;
                return;
            }
            const id = isEdit ? payload.id : data.data.id;
            if (alsoSubmit) {
                submitForApproval(id, true);
            } else {
                bootstrap.Modal.getInstance(document.getElementById('postingFormModal'))?.hide();
                Swal.fire({ icon: 'success', title: 'Saved', text: data.message, timer: 2000, showConfirmButton: false });
                loadPostings(jpPage);
            }
        })
        .catch(() => { jpBusy = false; alertBox.innerHTML = `<div class="alert alert-danger small">Something went wrong.</div>`; });
}

function submitForApproval(id, fromForm) {
    fetch('?page=api_hr_submit_job_posting', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
    })
        .then(r => r.json())
        .then(data => {
            if (fromForm) bootstrap.Modal.getInstance(document.getElementById('postingFormModal'))?.hide();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Submitted', text: data.message, timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
            loadPostings(jpPage);
        });
}

// ============================================
// DETAIL / REVIEW / ARCHIVE / REUSE
// ============================================

function viewPosting(id) {
    const body = document.getElementById('postingDetailBody');
    const footer = document.getElementById('postingDetailFooter');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    footer.innerHTML = '';
    new bootstrap.Modal(document.getElementById('postingDetailModal')).show();

    fetch(`?page=api_hr_get_job_posting&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { body.innerHTML = `<div class="text-danger">${jpEscapeHtml(data.message)}</div>`; return; }
            jpCurrentDetail = data.data.posting;
            renderDetail(jpCurrentDetail);
        });
}

function renderDetail(p) {
    const body = document.getElementById('postingDetailBody');
    const footer = document.getElementById('postingDetailFooter');

    let lineageHtml = '';
    if (p.lineage && p.lineage.length > 1) {
        lineageHtml = `
            <h6 class="fw-bold mt-3">Reuse History</h6>
            <ul class="list-unstyled small">
                ${p.lineage.map(l => `<li>${l.id === p.id ? '<strong>' : ''}#${l.id} — ${jpEscapeHtml(l.title)} — ${jpStatusBadge(l.status)} (created ${jpFormatDate(l.created_at)})${l.id === p.id ? '</strong>' : ''}</li>`).join('')}
            </ul>
        `;
    }

    body.innerHTML = `
        <div class="row mb-2">
            <div class="col-md-6">
                <p class="mb-1"><strong>Title:</strong> ${jpEscapeHtml(p.title)}</p>
                <p class="mb-1"><strong>Department:</strong> ${jpEscapeHtml(p.department)}</p>
                <p class="mb-0"><strong>Role Key:</strong> ${jpEscapeHtml(p.role)}</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Status:</strong> ${jpStatusBadge(p.status)}</p>
                <p class="mb-1"><strong>Closing Date:</strong> ${jpFormatDate(p.open_until)}</p>
                <p class="mb-0"><strong>Salary:</strong> ${jpCurrency(p.salary_range_min)} - ${jpCurrency(p.salary_range_max)}</p>
            </div>
        </div>
        <p><strong>Description:</strong><br>${jpEscapeHtml(p.description).replace(/\n/g, '<br>')}</p>
        ${p.requirements ? `<p><strong>Requirements:</strong><br>${jpEscapeHtml(p.requirements).replace(/\n/g, '<br>')}</p>` : ''}
        <hr>
        <p class="small text-muted mb-1">Created by ${jpEscapeHtml(p.creator_first)} ${jpEscapeHtml(p.creator_last)} on ${jpFormatDate(p.created_at, true)}</p>
        ${p.submitted_at ? `<p class="small text-muted mb-1">Submitted for approval: ${jpFormatDate(p.submitted_at, true)}</p>` : ''}
        ${p.approved_at ? `<p class="small text-success mb-1">Approved by ${jpEscapeHtml(p.approver_first)} ${jpEscapeHtml(p.approver_last)} on ${jpFormatDate(p.approved_at, true)}</p>` : ''}
        ${p.rejected_at ? `<p class="small text-danger mb-1">Rejected by ${jpEscapeHtml(p.rejecter_first)} ${jpEscapeHtml(p.rejecter_last)} on ${jpFormatDate(p.rejected_at, true)}<br>Reason: ${jpEscapeHtml(p.rejection_reason)}</p>` : ''}
        ${p.archived_at ? `<p class="small text-muted mb-1">Archived: ${jpFormatDate(p.archived_at, true)}</p>` : ''}
        ${lineageHtml}
    `;

    let actions = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;

    if (HR_IS_HEAD && p.status === 'pending_approval') {
        actions += `<button type="button" class="btn btn-outline-secondary btn-sm" id="editFromDetailBtn"><i class="bi bi-pencil"></i> Edit</button>`;
        actions += `<button type="button" class="btn btn-danger btn-sm" id="rejectBtn"><i class="bi bi-x-circle"></i> Reject</button>`;
        actions += `<button type="button" class="btn btn-success btn-sm" id="approveBtn"><i class="bi bi-check-circle"></i> Approve</button>`;
    }
    if (['draft', 'rejected'].includes(p.status)) {
        actions += `<button type="button" class="btn btn-outline-secondary btn-sm" id="editFromDetailBtn"><i class="bi bi-pencil"></i> Edit</button>`;
        actions += `<button type="button" class="btn btn-yellow-primary btn-sm" id="submitFromDetailBtn"><i class="bi bi-send"></i> Submit for Approval</button>`;
    }
    if (p.status === 'approved') {
        actions += `<button type="button" class="btn btn-outline-dark btn-sm" id="closeBtn"><i class="bi bi-lock"></i> Mark Not Hiring</button>`;
        actions += `<button type="button" class="btn btn-outline-secondary btn-sm" id="archiveBtn"><i class="bi bi-archive"></i> Archive</button>`;
    }
    if (p.status === 'closed') {
        actions += `<button type="button" class="btn btn-outline-secondary btn-sm" id="archiveBtn"><i class="bi bi-archive"></i> Archive</button>`;
    }
    if (['closed', 'archived'].includes(p.status)) {
        actions += `<button type="button" class="btn btn-yellow-primary btn-sm" id="reuseBtn"><i class="bi bi-arrow-repeat"></i> Reuse for New Hiring</button>`;
    }

    footer.innerHTML = actions;
    document.getElementById('editFromDetailBtn')?.addEventListener('click', () => openFormModal(p));
    document.getElementById('submitFromDetailBtn')?.addEventListener('click', () => submitForApproval(p.id, false));
    document.getElementById('approveBtn')?.addEventListener('click', () => reviewPosting(p.id, 'approve'));
    document.getElementById('rejectBtn')?.addEventListener('click', openRejectModal);
    document.getElementById('closeBtn')?.addEventListener('click', () => archivePosting(p.id, 'close'));
    document.getElementById('archiveBtn')?.addEventListener('click', () => archivePosting(p.id, 'archive'));
    document.getElementById('reuseBtn')?.addEventListener('click', () => reusePosting(p.id));
}

function reviewPosting(id, action, reason) {
    if (jpBusy) return;
    jpBusy = true;
    fetch('?page=api_hr_review_job_posting', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, action, reason: reason || '' })
    })
        .then(r => r.json())
        .then(data => {
            jpBusy = false;
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('postingDetailModal'))?.hide();
                bootstrap.Modal.getInstance(document.getElementById('rejectPostingModal'))?.hide();
                Swal.fire({ icon: 'success', title: action === 'approve' ? 'Approved' : 'Rejected', text: data.message, timer: 2000, showConfirmButton: false });
                loadPostings(jpPage);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(() => { jpBusy = false; });
}

function openRejectModal() {
    document.getElementById('rejectPostingReason').value = '';
    document.getElementById('rejectPostingReason').classList.remove('is-invalid');
    new bootstrap.Modal(document.getElementById('rejectPostingModal')).show();
}

function submitReject() {
    const reason = document.getElementById('rejectPostingReason').value.trim();
    if (!reason) {
        document.getElementById('rejectPostingReason').classList.add('is-invalid');
        return;
    }
    reviewPosting(jpCurrentDetail.id, 'reject', reason);
}

function archivePosting(id, action) {
    if (jpBusy) return;
    jpBusy = true;
    fetch('?page=api_hr_archive_job_posting', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, action })
    })
        .then(r => r.json())
        .then(data => {
            jpBusy = false;
            bootstrap.Modal.getInstance(document.getElementById('postingDetailModal'))?.hide();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Done', text: data.message, timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
            loadPostings(jpPage);
        })
        .catch(() => { jpBusy = false; });
}

function reusePosting(id) {
    if (jpBusy) return;
    jpBusy = true;
    fetch('?page=api_hr_reuse_job_posting', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
    })
        .then(r => r.json())
        .then(data => {
            jpBusy = false;
            bootstrap.Modal.getInstance(document.getElementById('postingDetailModal'))?.hide();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Draft Created', text: data.message, timer: 2500, showConfirmButton: false });
                loadPostings(1);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(() => { jpBusy = false; });
}
