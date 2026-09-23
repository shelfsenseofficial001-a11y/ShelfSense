// ============================================
// HR - JOB POSTINGS (list + review/archive/reuse)
// Create/edit now lives on its own page -- see job_posting_form.js.
// ============================================

let jpPage = 1;
let jpBusy = false;
let jpCurrentDetail = null;

document.addEventListener('DOMContentLoaded', function () {
    if (window.__INITIAL_DATA__) {
        const result = window.__INITIAL_DATA__;
        jpPage = 1;
        renderTable(result.postings);
        renderStats(result.counts);
        renderPagination(result.pagination);
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        loadPostings(1);
    }
    setupFilters();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'status', type: 'select', elementId: 'filterStatus', defaultValue: 'all' },
            { key: 'search', type: 'search', elementId: 'searchInput' },
        ]);
    }

    document.getElementById('confirmRejectPostingBtn')?.addEventListener('click', submitReject);
    document.getElementById('confirmApprovePostingBtn')?.addEventListener('click', submitApprove);
    document.querySelectorAll('.jp-md-toolbar').forEach(setupMdToolbar);
    document.getElementById('myDraftsBtn')?.addEventListener('click', openMyDrafts);
    document.getElementById('jpDraftsSelectBtn')?.addEventListener('click', function () {
        if (jpDraftsSelecting) { jpDraftsExitSelectMode(); } else { jpDraftsEnterSelectMode(); }
    });
    document.getElementById('jpDraftsCancelSelectBtn')?.addEventListener('click', jpDraftsExitSelectMode);
    document.getElementById('jpDraftsDeleteSelectedBtn')?.addEventListener('click', jpDraftsDeleteSelected);
    jpInitIconTooltips(document);

    // Deep link from a job-posting notification (approved/rejected/etc,
    // ?page=hr_job_postings&posting_id=123) straight into that posting's
    // preview drawer, instead of just landing on the list.
    const deepLinkPostingId = new URLSearchParams(window.location.search).get('posting_id');
    if (deepLinkPostingId) {
        viewPosting(parseInt(deepLinkPostingId, 10));
    }
});

// Bootstrap tooltip (dark bubble + arrow) for any icon-only control with a
// `title` -- the plain browser tooltip it'd fall back to is slow to appear
// and looks inconsistent with the rest of the UI. Safe to call repeatedly
// on the same root (e.g. after re-rendering dynamic content): skips
// elements that already have one wired up.
function jpInitIconTooltips(root) {
    if (!window.bootstrap || !window.bootstrap.Tooltip) return;
    root.querySelectorAll('[title]').forEach(el => {
        if (el.__jpTooltip || !el.title.trim()) return;
        el.__jpTooltip = new window.bootstrap.Tooltip(el, { trigger: 'hover focus', placement: 'top' });
    });
}

function jpEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Qualifications and Key Responsibilities are entered "one per line" --
// render each non-empty line as its own bullet instead of just <br>-joining.
function jpLinesToList(text, extraClass) {
    const lines = (text || '').split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length === 0) return '';
    const cls = 'jp-bullet-list' + (extraClass ? ' ' + extraClass : '');
    return `<ul class="${cls}">` + lines.map(l => `<li>${jpEscapeHtml(l)}</li>`).join('') + '</ul>';
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
    document.getElementById('refreshBtn')?.addEventListener('click', () => loadPostings(jpPage));
    document.getElementById('viewArchivedBtn')?.addEventListener('click', () => jpFilterByStatus('archived'));
    document.getElementById('statsRow')?.addEventListener('click', (e) => {
        const card = e.target.closest('.jp-stat-clickable');
        if (card && card.dataset.status) jpFilterByStatus(card.dataset.status);
    });
}

// Programmatically set the status filter (from a stat-card click or the
// Archived shortcut) -- goes through the searchable-select instance so its
// visible label and internal state stay in sync, not just the raw <select>.
function jpFilterByStatus(status) {
    const select = document.getElementById('filterStatus');
    if (!select) return;
    const label = select.querySelector(`option[value="${status}"]`)?.textContent || status;
    if (select.searchableSelectInstance) {
        select.searchableSelectInstance.selectOption(status, label);
    } else {
        select.value = status;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
    select.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function loadPostings(page) {
    jpPage = page;
    const isApprovalsMode = typeof JP_APPROVALS_MODE !== 'undefined' && JP_APPROVALS_MODE;
    const status = isApprovalsMode ? 'pending_approval' : document.getElementById('filterStatus').value;
    const search = document.getElementById('searchInput').value.trim();

    const tbody = document.getElementById('postingsTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

    const params = new URLSearchParams({ p: page, limit: 10, status });
    if (search) params.append('search', search);

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
    // Approvals mode has no stats row (the global all-status counts don't
    // apply to a pending-only queue) -- these elements simply won't exist.
    const el = id => document.getElementById(id);
    if (el('statDraft')) el('statDraft').textContent = c.draft ?? 0;
    if (el('statPending')) el('statPending').textContent = c.pending_approval ?? 0;
    if (el('statApproved')) el('statApproved').textContent = c.approved ?? 0;
    if (el('statRejected')) el('statRejected').textContent = c.rejected ?? 0;
    if (el('statClosed')) el('statClosed').textContent = c.closed ?? 0;
    if (el('statArchived')) el('statArchived').textContent = c.archived ?? 0;
}

function renderTable(postings) {
    const tbody = document.getElementById('postingsTableBody');
    if (!postings || postings.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No job postings found.</td></tr>`;
        return;
    }
    tbody.innerHTML = postings.map(p => `
        <tr>
            <td><strong>${jpEscapeHtml(p.title)}</strong>${p.reused_from_id ? ' <span class="badge bg-info-subtle text-info-emphasis" title="Reused from an earlier posting">reused</span>' : ''}${(p.shares_location_count > 0) ? ` <span class="badge bg-warning-subtle text-warning-emphasis" title="Shares this location with ${p.shares_location_count} other active posting(s)"><i class="bi bi-geo-alt"></i> shared location</span>` : ''}</td>
            <td>${jpEscapeHtml(p.department_group || p.department)}</td>
            <td>${jpFormatDate(p.open_until)}</td>
            <td>${jpEscapeHtml(p.creator_first)} ${jpEscapeHtml(p.creator_last)}</td>
            <td>${jpStatusBadge(p.status)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary view-posting-btn" data-id="${p.id}" title="View details"><i class="bi bi-eye"></i></button>
                ${p.can_edit ? `<button class="btn btn-sm btn-outline-secondary edit-posting-btn" data-id="${p.id}" title="Edit job posting"><i class="bi bi-pencil"></i></button>` : ''}
            </td>
        </tr>
    `).join('');

    tbody.querySelectorAll('.view-posting-btn').forEach(btn => {
        btn.addEventListener('click', () => viewPosting(parseInt(btn.dataset.id)));
    });
    tbody.querySelectorAll('.edit-posting-btn').forEach(btn => {
        btn.addEventListener('click', () => { window.location.href = `?page=hr_job_posting_form&id=${btn.dataset.id}`; });
    });
    jpInitIconTooltips(tbody);
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
// MY DRAFTS
// ============================================

let jpDraftsSelecting = false;
const jpDraftsSelectedIds = new Set();

function openMyDrafts() {
    const body = document.getElementById('myDraftsBody');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    jpDraftsExitSelectMode();
    // getOrCreateInstance (not `new Modal(...)`) -- this is called again
    // every time a draft gets deleted while the modal is already open, and
    // creating a second Modal instance for the same element makes Bootstrap
    // add a second .modal-backdrop on top of the first instead of no-oping
    // on an already-shown modal, which is what actually darkens the page.
    bootstrap.Modal.getOrCreateInstance(document.getElementById('myDraftsModal')).show();

    fetch('?page=api_hr_get_job_postings&status=draft&mine=1&limit=50')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { body.innerHTML = `<div class="text-danger">${jpEscapeHtml(data.message)}</div>`; return; }
            renderMyDrafts(data.data.postings);
        })
        .catch(() => { body.innerHTML = `<div class="text-danger">Could not load your drafts. Please try again.</div>`; });
}

function renderMyDrafts(drafts) {
    const body = document.getElementById('myDraftsBody');
    const selectBtn = document.getElementById('jpDraftsSelectBtn');
    if (selectBtn) selectBtn.style.display = (drafts && drafts.length > 0) ? '' : 'none';

    if (!drafts || drafts.length === 0) {
        body.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                You don't have any drafts right now.
                <div class="mt-3"><a href="?page=hr_job_posting_form" class="btn btn-yellow-primary btn-sm"><i class="bi bi-plus-circle"></i> New Job Posting</a></div>
            </div>
        `;
        return;
    }

    body.innerHTML = drafts.map(p => `
        <div class="jp-draft-card" data-id="${p.id}">
            <input type="checkbox" class="jp-draft-checkbox form-check-input" data-id="${p.id}" ${jpDraftsSelectedIds.has(p.id) ? 'checked' : ''}>
            <div class="jp-draft-icon"><i class="bi bi-megaphone"></i></div>
            <div class="jp-draft-info">
                <div class="jp-draft-title">${jpEscapeHtml(p.title || 'Untitled position')} <span class="badge bg-secondary">Draft</span></div>
                <div class="jp-draft-subtitle">${jpEscapeHtml(p.department_group || p.department || 'No department set yet')}</div>
            </div>
            <div class="jp-draft-meta"><i class="bi bi-clock-history"></i> Updated ${jpFormatDate(p.updated_at)}</div>
            <a href="?page=hr_job_posting_form&id=${p.id}" class="jp-draft-edit-btn" title="Edit draft"><i class="bi bi-pencil"></i></a>
            <button type="button" class="jp-draft-delete-btn" data-id="${p.id}" data-title="${jpEscapeHtml(p.title || 'Untitled position')}" title="Delete draft"><i class="bi bi-trash"></i></button>
        </div>
    `).join('');

    body.querySelectorAll('.jp-draft-delete-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            deleteDraft(parseInt(btn.dataset.id), btn.dataset.title);
        });
    });
    body.querySelectorAll('.jp-draft-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            jpDraftsToggleSelected(parseInt(cb.dataset.id), cb.checked);
        });
    });
    body.querySelectorAll('.jp-draft-card').forEach(card => {
        card.addEventListener('click', function (e) {
            if (!jpDraftsSelecting) return;
            if (e.target.closest('.jp-draft-checkbox')) return;
            e.preventDefault();
            const cb = card.querySelector('.jp-draft-checkbox');
            cb.checked = !cb.checked;
            jpDraftsToggleSelected(parseInt(card.dataset.id), cb.checked);
        });
    });
    body.classList.toggle('jp-drafts-selecting', jpDraftsSelecting);
    jpInitIconTooltips(body);
}

// Removes just the deleted card(s) from the already-open My Drafts modal
// instead of re-running openMyDrafts() (spinner -> refetch -> re-render
// the whole list). That full reload was firing in the same instant as the
// success toast -- replacing the modal body and re-touching the (already
// open, no-op) modal instance right as the toast's own overlay animates in
// is what read as the backdrop flickering. Removing only the affected
// card(s) is both smoother and cheaper.
function jpDraftsRemoveCards(ids) {
    const body = document.getElementById('myDraftsBody');
    if (!body) return;
    ids.forEach(id => {
        const card = body.querySelector(`.jp-draft-card[data-id="${id}"]`);
        if (card) card.remove();
    });
    if (!body.querySelector('.jp-draft-card')) {
        renderMyDrafts([]);
    }
}

function jpDraftsToggleSelected(id, selected) {
    if (selected) jpDraftsSelectedIds.add(id); else jpDraftsSelectedIds.delete(id);
    const count = jpDraftsSelectedIds.size;
    document.getElementById('jpDraftsBulkCount').textContent = `${count} selected`;
    document.getElementById('jpDraftsDeleteSelectedBtn').disabled = count === 0;
}

function jpDraftsEnterSelectMode() {
    jpDraftsSelecting = true;
    jpDraftsSelectedIds.clear();
    document.getElementById('myDraftsBody').classList.add('jp-drafts-selecting');
    document.getElementById('jpDraftsSelectBtn')?.classList.add('active');
    document.getElementById('jpDraftsBulkFooter').style.display = 'flex';
    document.getElementById('jpDraftsBulkCount').textContent = '0 selected';
    document.getElementById('jpDraftsDeleteSelectedBtn').disabled = true;
    document.querySelectorAll('.jp-draft-checkbox').forEach(cb => { cb.checked = false; });
}

function jpDraftsExitSelectMode() {
    jpDraftsSelecting = false;
    jpDraftsSelectedIds.clear();
    const body = document.getElementById('myDraftsBody');
    if (body) body.classList.remove('jp-drafts-selecting');
    document.getElementById('jpDraftsSelectBtn')?.classList.remove('active');
    const footer = document.getElementById('jpDraftsBulkFooter');
    if (footer) footer.style.display = 'none';
}

// Bottom-right popup toast for post-delete feedback -- deliberately NOT a
// plain Swal.fire() modal, since that adds its own backdrop on top of the
// My Drafts Bootstrap modal (still open/being re-shown right after), and
// the two stacked backdrops are what was darkening the page.
function jpDraftsToast(icon, title, text) {
    Swal.fire({
        toast: true, position: 'bottom-end', icon, title, text,
        showConfirmButton: false, showCloseButton: true, timer: 2500, timerProgressBar: true,
    });
}

function jpDraftsDeleteSelected() {
    const ids = Array.from(jpDraftsSelectedIds);
    if (ids.length === 0) return;
    Swal.fire({
        icon: 'error',
        title: `Delete ${ids.length} draft${ids.length === 1 ? '' : 's'}?`,
        html: `You are about to permanently delete <strong>${ids.length}</strong> draft${ids.length === 1 ? '' : 's'}.<br>This action cannot be undone.`,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-trash"></i> Yes, delete them',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        Promise.all(ids.map(id => fetch('?page=api_hr_delete_job_posting', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
        }).then(r => r.json())))
            .then(results => {
                const succeededIds = ids.filter((id, i) => results[i].success);
                const failed = ids.length - succeededIds.length;
                if (failed > 0) {
                    jpDraftsToast('warning', 'Some deletions failed', `${succeededIds.length} of ${ids.length} drafts were deleted.`);
                } else {
                    jpDraftsToast('success', 'Deleted', `${ids.length} draft${ids.length === 1 ? '' : 's'} permanently deleted.`);
                }
                jpDraftsExitSelectMode();
                jpDraftsRemoveCards(succeededIds);
                loadPostings(jpPage);
            })
            .catch(() => { jpDraftsToast('error', 'Error', 'Something went wrong.'); });
    });
}

function deleteDraft(id, title) {
    Swal.fire({
        icon: 'error',
        title: 'Delete this draft?',
        html: `You are about to permanently delete <strong>${jpEscapeHtml(title || 'this draft')}</strong>.<br>This action cannot be undone.`,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-trash"></i> Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('?page=api_hr_delete_job_posting', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { jpDraftsToast('error', 'Error', data.message); return; }
                jpDraftsToast('success', 'Deleted', data.message);
                jpDraftsRemoveCards([id]);
                loadPostings(jpPage);
            })
            .catch(() => { jpDraftsToast('error', 'Error', 'Something went wrong.'); });
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
    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('postingDetailModal')).show();

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

    const messageCount = (p.messages || []).length;

    body.innerHTML = `
        <div class="jp-detail-header">
            <h3 class="jp-detail-title">${jpEscapeHtml(p.title)}</h3>
            ${jpStatusBadge(p.status)}
        </div>
        <div class="jp-detail-split-layout">
            <div class="jp-detail-main">
                <div class="jp-detail-field-grid">
                    <div><span class="jp-detail-field-label">Department</span><div class="jp-detail-field-value">${jpEscapeHtml(p.department_group || '—')}</div></div>
                    <div><span class="jp-detail-field-label">Closing Date</span><div class="jp-detail-field-value">${jpFormatDate(p.open_until)}</div></div>
                    <div><span class="jp-detail-field-label">Position</span><div class="jp-detail-field-value">${jpEscapeHtml(p.department)}</div></div>
                    <div><span class="jp-detail-field-label">Salary</span><div class="jp-detail-field-value">${jpCurrency(p.salary_range_min)} - ${jpCurrency(p.salary_range_max)}</div></div>
                </div>
                ${p.shares_location_count > 0 ? `<p class="mb-3"><span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-geo-alt"></i> Shares location "${jpEscapeHtml(p.location || '')}" with ${p.shares_location_count} other active posting(s)</span></p>` : ''}
                <div class="mb-2"><strong>Description:</strong><div class="jp-preview-description">${window.mdToHtml ? window.mdToHtml(p.description) : jpEscapeHtml(p.description).replace(/\n/g, '<br>')}</div></div>
                ${p.requirements ? `<div class="mb-2"><strong>Qualifications:</strong>${jpLinesToList(p.requirements, 'jp-check-list')}</div>` : ''}
                ${p.responsibilities ? `<div class="mb-2"><strong>Key Responsibilities:</strong>${jpLinesToList(p.responsibilities, 'jp-check-list')}</div>` : ''}
                <hr>
                <p class="small text-muted mb-1">Created by ${jpEscapeHtml(p.creator_first)} ${jpEscapeHtml(p.creator_last)} on ${jpFormatDate(p.created_at, true)}</p>
                ${p.submitted_at ? `<p class="small text-muted mb-1">Submitted for approval: ${jpFormatDate(p.submitted_at, true)}</p>` : ''}
                ${p.approved_at ? `<p class="small text-success mb-1">Approved by ${jpEscapeHtml(p.approver_first)} ${jpEscapeHtml(p.approver_last)} on ${jpFormatDate(p.approved_at, true)}</p>` : ''}
                ${p.rejected_at ? `<p class="small text-danger mb-1">Rejected by ${jpEscapeHtml(p.rejecter_first)} ${jpEscapeHtml(p.rejecter_last)} on ${jpFormatDate(p.rejected_at, true)}</p>` : ''}
                ${p.archived_at ? `<p class="small text-muted mb-1">Archived: ${jpFormatDate(p.archived_at, true)}</p>` : ''}
                ${lineageHtml}
            </div>
            <div class="jp-detail-side">
                <h6 class="jp-detail-side-title">Messages${messageCount ? ` <span class="badge bg-secondary">${messageCount}</span>` : ''}</h6>
                <div id="jpMessageThread"></div>
            </div>
        </div>
    `;

    renderMessageThread(p);

    let actions = `<div class="jp-detail-actions"><button type="button" class="jp-detail-btn jp-detail-btn-neutral" data-bs-dismiss="modal">Close</button>`;

    const approvalsMode = typeof JP_APPROVALS_MODE !== 'undefined' && JP_APPROVALS_MODE;
    if (HR_IS_HEAD && approvalsMode && p.status === 'pending_approval') {
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-danger" id="rejectBtn"><i class="bi bi-x-circle"></i> Reject</button>`;
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-success" id="approveBtn"><i class="bi bi-check-circle"></i> Approve</button>`;
    }
    if (p.can_edit) {
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-outline" id="editFromDetailBtn"><i class="bi bi-pencil"></i> Edit</button>`;
    }
    if (!HR_IS_HEAD && ['draft', 'rejected'].includes(p.status)) {
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-primary" id="submitFromDetailBtn"><i class="bi bi-send"></i> Submit for Approval</button>`;
    }
    if (p.status === 'approved') {
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-dark" id="closeBtn"><i class="bi bi-lock"></i> Mark Not Hiring</button>`;
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-outline" id="archiveBtn"><i class="bi bi-archive"></i> Archive</button>`;
    }
    if (p.status === 'closed') {
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-outline" id="archiveBtn"><i class="bi bi-archive"></i> Archive</button>`;
    }
    if (!HR_IS_HEAD && ['closed', 'archived'].includes(p.status)) {
        actions += `<button type="button" class="jp-detail-btn jp-detail-btn-primary" id="reuseBtn"><i class="bi bi-arrow-repeat"></i> Reuse for New Hiring</button>`;
    }
    actions += `</div>`;

    footer.innerHTML = actions;
    document.getElementById('editFromDetailBtn')?.addEventListener('click', () => { window.location.href = `?page=hr_job_posting_form&id=${p.id}`; });
    document.getElementById('submitFromDetailBtn')?.addEventListener('click', () => submitForApprovalFromDetail(p.id));
    document.getElementById('approveBtn')?.addEventListener('click', openApproveModal);
    document.getElementById('rejectBtn')?.addEventListener('click', openRejectModal);
    document.getElementById('closeBtn')?.addEventListener('click', () => archivePosting(p.id, 'close'));
    document.getElementById('archiveBtn')?.addEventListener('click', () => archivePosting(p.id, 'archive'));
    document.getElementById('reuseBtn')?.addEventListener('click', () => reusePosting(p.id));
}

function submitForApprovalFromDetail(id) {
    fetch('?page=api_hr_submit_job_posting', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Offcanvas.getInstance(document.getElementById('postingDetailModal'))?.hide();
                Swal.fire({ icon: 'success', title: 'Submitted', text: data.message, timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
            loadPostings(jpPage);
        });
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
                bootstrap.Offcanvas.getInstance(document.getElementById('postingDetailModal'))?.hide();
                bootstrap.Modal.getInstance(document.getElementById('rejectPostingModal'))?.hide();
                bootstrap.Modal.getInstance(document.getElementById('approvePostingModal'))?.hide();
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
    bootstrap.Modal.getOrCreateInstance(document.getElementById('rejectPostingModal')).show();
}

function submitReject() {
    const reason = document.getElementById('rejectPostingReason').value.trim();
    if (!reason) {
        document.getElementById('rejectPostingReason').classList.add('is-invalid');
        return;
    }
    reviewPosting(jpCurrentDetail.id, 'reject', reason);
}

function openApproveModal() {
    document.getElementById('approvePostingMessage').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('approvePostingModal')).show();
}

function submitApprove() {
    const message = document.getElementById('approvePostingMessage').value.trim();
    reviewPosting(jpCurrentDetail.id, 'approve', message);
}

const JP_MSG_ACTION_LABEL = { approved: 'Approved', rejected: 'Rejected', comment: 'Message' };
const JP_MSG_ACTION_CLASS = { approved: 'success', rejected: 'danger', comment: 'secondary' };

// Moderation-message thread, shown in its own "Messages" tab of the detail
// drawer, mirroring a running conversation log (submit -> reviewed ->
// follow-up messages) rather than a single one-shot approve/reject note.
// HR Head (or Super Admin) can drop a new message at any time via the
// composer at the bottom; HR Staff can only read the thread.
function renderMessageThread(p) {
    const container = document.getElementById('jpMessageThread');
    if (!container) return;
    const messages = p.messages || [];
    const canPost = HR_IS_HEAD;

    const entriesHtml = messages.length
        ? messages.map(m => {
            const action = m.action && JP_MSG_ACTION_LABEL[m.action] ? m.action : null;
            const authorName = (m.first_name || m.last_name) ? `${jpEscapeHtml(m.first_name || '')} ${jpEscapeHtml(m.last_name || '')}`.trim() : 'HR Head';
            return `
                <div class="jp-msg-entry">
                    <div class="jp-msg-entry-icon"><i class="bi bi-person-circle"></i></div>
                    <div class="jp-msg-entry-body">
                        <div class="jp-msg-entry-meta">
                            <strong>${authorName}</strong>
                            ${action ? `<span class="badge bg-${JP_MSG_ACTION_CLASS[action]}-subtle text-${JP_MSG_ACTION_CLASS[action]}-emphasis">${JP_MSG_ACTION_LABEL[action]}</span>` : ''}
                            <span class="text-muted small">${jpFormatDate(m.created_at, true)}</span>
                        </div>
                        <div class="jp-preview-description jp-msg-entry-text">${window.mdToHtml ? window.mdToHtml(m.message) : jpEscapeHtml(m.message)}</div>
                    </div>
                </div>
            `;
        }).join('')
        : '<p class="text-muted small mb-0">No messages yet.</p>';

    const composerHtml = canPost ? `
        <div class="jp-msg-composer">
            <div class="jp-md-toolbar" data-target="jpNewMessageInput">
                <button type="button" class="jp-md-btn" data-md="bold" title="Bold"><i class="bi bi-type-bold"></i></button>
                <button type="button" class="jp-md-btn" data-md="italic" title="Italic"><i class="bi bi-type-italic"></i></button>
                <button type="button" class="jp-md-btn" data-md="list" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
            </div>
            <textarea id="jpNewMessageInput" class="form-control" rows="2" maxlength="500" placeholder="Leave a message for HR Staff..."></textarea>
            <button type="button" class="btn btn-yellow-primary btn-sm mt-2" id="jpPostMessageBtn"><i class="bi bi-send"></i> Post Message</button>
        </div>
    ` : '';

    container.innerHTML = `
        <p class="text-muted small mb-2">A running conversation between HR Head and the HR Staff who submitted this posting.</p>
        <div class="jp-msg-thread">${entriesHtml}</div>
        ${composerHtml}
    `;

    const toolbar = container.querySelector('.jp-md-toolbar');
    if (toolbar) setupMdToolbar(toolbar);
    document.getElementById('jpPostMessageBtn')?.addEventListener('click', () => postNewMessage(p.id));
}

function postNewMessage(id) {
    const input = document.getElementById('jpNewMessageInput');
    const message = input.value.trim();
    if (!message) { input.classList.add('is-invalid'); return; }
    if (jpBusy) return;
    jpBusy = true;
    fetch('?page=api_hr_add_job_posting_message', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, message })
    })
        .then(r => r.json())
        .then(data => {
            jpBusy = false;
            if (!data.success) { Swal.fire({ icon: 'error', title: 'Error', text: data.message }); return; }
            viewPosting(id);
            loadPostings(jpPage);
        })
        .catch(() => { jpBusy = false; });
}

// Small, stateless Markdown toolbar (Bold/Italic/List) shared by the
// Approve and Reject moderation-message textareas -- deliberately not the
// Description field's jpMd* system, which keeps its undo/redo history in a
// single module-level global and only supports one textarea at a time.
function setupMdToolbar(toolbarEl) {
    const targetId = toolbarEl.dataset.target;
    const textarea = document.getElementById(targetId);
    if (!textarea) return;
    toolbarEl.querySelectorAll('.jp-md-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const kind = btn.dataset.md;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = textarea.value.slice(start, end);
            let inserted;
            if (kind === 'bold') inserted = `**${selected || 'bold text'}**`;
            else if (kind === 'italic') inserted = `*${selected || 'italic text'}*`;
            else inserted = (selected || 'list item').split('\n').map(line => `- ${line}`).join('\n');
            textarea.value = textarea.value.slice(0, start) + inserted + textarea.value.slice(end);
            textarea.focus();
            textarea.selectionStart = start;
            textarea.selectionEnd = start + inserted.length;
        });
    });
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
            bootstrap.Offcanvas.getInstance(document.getElementById('postingDetailModal'))?.hide();
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
            bootstrap.Offcanvas.getInstance(document.getElementById('postingDetailModal'))?.hide();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Draft Created', text: data.message, timer: 2500, showConfirmButton: false });
                loadPostings(1);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(() => { jpBusy = false; });
}
