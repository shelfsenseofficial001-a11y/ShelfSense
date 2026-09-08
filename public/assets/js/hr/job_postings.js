// ============================================
// HR - JOB POSTINGS
// ============================================

let jpPage = 1;
let jpBusy = false;
let jpCurrentDetail = null;

// Mirrors JOB_POSTING_GROUP_POSITIONS in app/helpers/functions.php -- keep
// in sync if a department ever gets more positions.
const JP_GROUP_POSITIONS = {
    'Front Department': ['Cashier'],
    'Human Resources Department': ['HR Staff'],
    'Finance Department': ['Finance Staff'],
};

function populatePositionOptions(group, selectedPosition) {
    const posSelect = document.getElementById('postingDepartment');
    const positions = JP_GROUP_POSITIONS[group] || [];
    posSelect.innerHTML = '<option value=""></option>' + positions.map(p => `<option value="${p}">${p}</option>`).join('');
    posSelect.disabled = positions.length === 0;
    posSelect.value = selectedPosition && positions.includes(selectedPosition) ? selectedPosition : '';
    window.refreshSearchableSelect && window.refreshSearchableSelect(posSelect);
    // Assigning .value directly doesn't fire 'change', so the live preview
    // (which listens on postingDepartment) would otherwise miss this.
    if (typeof renderFullPreview === 'function') renderFullPreview();
}

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
    setupForm();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'status', type: 'select', elementId: 'filterStatus', defaultValue: 'all' },
            { key: 'search', type: 'search', elementId: 'searchInput' },
            { key: 'mine', type: 'checkbox', elementId: 'mineOnly', label: 'My postings only' },
        ]);
    }

    document.getElementById('confirmRejectPostingBtn')?.addEventListener('click', submitReject);

    document.getElementById('postingDepartmentGroup').addEventListener('change', function () {
        populatePositionOptions(this.value, null);
    });
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

// Snaps an out-of-range closing date to the nearest allowed bound instead of
// silently clearing it, so a typed/invalid date never gets past validation.
function validatePostingDate(input) {
    const value = input.value;
    if (!value) return;
    const selected = new Date(value + 'T00:00:00');
    const min = new Date(input.min + 'T00:00:00');
    const max = new Date(input.max + 'T00:00:00');
    if (isNaN(selected.getTime())) {
        input.value = '';
        return;
    }
    if (selected < min) {
        input.value = input.min;
        Swal.fire({ icon: 'warning', title: 'Date Too Early', text: 'Closing date cannot be in the past. Snapped to today.', timer: 2500, timerProgressBar: true });
    } else if (selected > max) {
        input.value = input.max;
        Swal.fire({ icon: 'warning', title: 'Date Too Far', text: 'Closing date cannot exceed 6 months out. Snapped to the latest allowed date.', timer: 2500, timerProgressBar: true });
    }
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
            <td><strong>${jpEscapeHtml(p.title)}</strong>${p.reused_from_id ? ' <span class="badge bg-info-subtle text-info-emphasis" title="Reused from an earlier posting">reused</span>' : ''}${(p.shares_location_count > 0) ? ` <span class="badge bg-warning-subtle text-warning-emphasis" title="Shares this location with ${p.shares_location_count} other active posting(s)"><i class="bi bi-geo-alt"></i> shared location</span>` : ''}</td>
            <td>${jpEscapeHtml(p.department_group || p.department)}</td>
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
    setupMarkdownEditor();
}

// ============================================
// DESCRIPTION MARKDOWN TOOLBAR
// Wraps/inserts markdown syntax around the current selection (or at the
// cursor, with placeholder text, when nothing is selected) -- the same
// interaction every markdown editor toolbar uses. Rendering back to HTML
// (for the Preview toggle here, and on the public Apply page) is handled
// by the shared mdToHtml() in assets/js/shared/markdown.js.
// ============================================
const JP_MD_ACTIONS = {
    h1: { type: 'line-prefix', prefix: '# ' },
    h2: { type: 'line-prefix', prefix: '## ' },
    h3: { type: 'line-prefix', prefix: '### ' },
    bold: { type: 'wrap', before: '**', after: '**', placeholder: 'bold text' },
    italic: { type: 'wrap', before: '*', after: '*', placeholder: 'italic text' },
    strike: { type: 'wrap', before: '~~', after: '~~', placeholder: 'strikethrough text' },
    code: { type: 'wrap', before: '`', after: '`', placeholder: 'code' },
    ul: { type: 'line-prefix', prefix: '- ' },
    ol: { type: 'line-prefix', prefix: '1. ' },
};

function applyMarkdownAction(textarea, action) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;
    const selected = value.slice(start, end);

    if (action.type === 'wrap') {
        const text = selected || action.placeholder;
        const newValue = value.slice(0, start) + action.before + text + action.after + value.slice(end);
        textarea.value = newValue;
        const selStart = start + action.before.length;
        textarea.setSelectionRange(selStart, selStart + text.length);
    } else if (action.type === 'line-prefix') {
        // Prefix every line touched by the selection (so selecting several
        // lines and hitting "bullet list" turns all of them into list items).
        let lineStart = value.lastIndexOf('\n', start - 1) + 1;
        let lineEnd = value.indexOf('\n', end);
        if (lineEnd === -1) lineEnd = value.length;
        const block = value.slice(lineStart, lineEnd);
        const prefixed = block.split('\n').map(l => action.prefix + l).join('\n');
        textarea.value = value.slice(0, lineStart) + prefixed + value.slice(lineEnd);
        textarea.setSelectionRange(lineStart, lineStart + prefixed.length);
    }

    textarea.focus();
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    jpMdCommit(textarea); // discrete toolbar/keyboard actions are always their own undo step
}

// Grows the textarea to fit its content (up to a sane cap, beyond which it
// scrolls internally like before) so typing -- including pressing Enter for
// a new line -- pushes the box taller instead of hiding what's just been
// typed below the fold.
function autosizeTextarea(textarea) {
    const maxHeight = 500;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, maxHeight) + 'px';
    textarea.style.overflowY = textarea.scrollHeight > maxHeight ? 'auto' : 'hidden';
}

// ============================================
// UNDO / REDO
// Toolbar actions (and the link modal) replace textarea.value directly,
// which silently wipes the browser's own undo history for that field --
// so once any button is used, native Ctrl+Z would otherwise stop working
// for everything, even plain typing. This is a small self-contained undo
// stack instead: every discrete action (toolbar click, keyboard shortcut,
// link insert) commits its own step immediately; plain typing is coalesced
// into one step per ~500ms pause, so undo doesn't take one press per
// keystroke.
// ============================================
const jpMdHistory = { stack: [], index: -1 };
let jpMdCommitTimer = null;
const JP_MD_HISTORY_LIMIT = 100;

function jpMdSnapshotNow(textarea) {
    return { value: textarea.value, start: textarea.selectionStart, end: textarea.selectionEnd };
}

function jpMdCommit(textarea) {
    clearTimeout(jpMdCommitTimer);
    jpMdCommitTimer = null;
    const current = jpMdSnapshotNow(textarea);
    const top = jpMdHistory.stack[jpMdHistory.index];
    if (top && top.value === current.value) return; // nothing actually changed
    jpMdHistory.stack = jpMdHistory.stack.slice(0, jpMdHistory.index + 1);
    jpMdHistory.stack.push(current);
    if (jpMdHistory.stack.length > JP_MD_HISTORY_LIMIT) jpMdHistory.stack.shift();
    jpMdHistory.index = jpMdHistory.stack.length - 1;
    jpUpdateUndoRedoButtons();
}

function jpMdScheduleCommit(textarea) {
    clearTimeout(jpMdCommitTimer);
    jpMdCommitTimer = setTimeout(() => jpMdCommit(textarea), 500);
}

function jpMdResetHistory(textarea) {
    clearTimeout(jpMdCommitTimer);
    jpMdCommitTimer = null;
    jpMdHistory.stack = [jpMdSnapshotNow(textarea)];
    jpMdHistory.index = 0;
    jpUpdateUndoRedoButtons();
}

function jpMdRestoreSnapshot(textarea, snapshot) {
    textarea.value = snapshot.value;
    textarea.setSelectionRange(snapshot.start, snapshot.end);
    textarea.focus();
    autosizeTextarea(textarea);
    renderFullPreview();
    jpUpdateUndoRedoButtons();
}

function jpMdUndo(textarea) {
    jpMdCommit(textarea); // finalize any typing in progress before stepping back
    if (jpMdHistory.index <= 0) return;
    jpMdHistory.index--;
    jpMdRestoreSnapshot(textarea, jpMdHistory.stack[jpMdHistory.index]);
}

function jpMdRedo(textarea) {
    if (jpMdHistory.index >= jpMdHistory.stack.length - 1) return;
    jpMdHistory.index++;
    jpMdRestoreSnapshot(textarea, jpMdHistory.stack[jpMdHistory.index]);
}

function jpUpdateUndoRedoButtons() {
    const undoBtn = document.getElementById('jpMdUndoBtn');
    const redoBtn = document.getElementById('jpMdRedoBtn');
    if (undoBtn) undoBtn.disabled = jpMdHistory.index <= 0;
    if (redoBtn) redoBtn.disabled = jpMdHistory.index >= jpMdHistory.stack.length - 1;
}

// ============================================
// INSERT LINK MODAL
// ============================================
let jpLinkTextarea = null;
let jpLinkSelStart = 0;
let jpLinkSelEnd = 0;

function openLinkModal(textarea) {
    jpLinkTextarea = textarea;
    jpLinkSelStart = textarea.selectionStart;
    jpLinkSelEnd = textarea.selectionEnd;
    const selectedText = textarea.value.slice(jpLinkSelStart, jpLinkSelEnd);

    document.getElementById('jpLinkLabel').value = selectedText;
    document.getElementById('jpLinkUrl').value = '';
    updateLinkPreview();

    const modalEl = document.getElementById('jpLinkModal');
    modalEl.addEventListener('shown.bs.modal', function focusField() {
        modalEl.removeEventListener('shown.bs.modal', focusField);
        document.getElementById(selectedText ? 'jpLinkUrl' : 'jpLinkLabel').focus();
    });
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function isValidLinkUrl(url) {
    return /^https?:\/\/\S+$/.test(url);
}

function updateLinkPreview() {
    const label = document.getElementById('jpLinkLabel').value.trim();
    const url = document.getElementById('jpLinkUrl').value.trim();
    const preview = document.getElementById('jpLinkPreview');
    const insertBtn = document.getElementById('jpLinkInsertBtn');
    const valid = isValidLinkUrl(url);
    insertBtn.disabled = !valid;

    if (!url) {
        preview.innerHTML = '<span class="text-muted small fst-italic">Nothing to preview yet.</span>';
    } else if (!valid) {
        preview.innerHTML = '<span class="text-danger small">URL must start with http:// or https://</span>';
    } else {
        preview.innerHTML = `<a href="${jpEscapeHtml(url)}" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> ${jpEscapeHtml(label || url)}</a>`;
    }
}

function insertLinkFromModal() {
    const label = document.getElementById('jpLinkLabel').value.trim();
    const url = document.getElementById('jpLinkUrl').value.trim();
    if (!isValidLinkUrl(url) || !jpLinkTextarea) return;

    const textarea = jpLinkTextarea;
    const markdown = `[${label || url}](${url})`;
    const value = textarea.value;
    textarea.value = value.slice(0, jpLinkSelStart) + markdown + value.slice(jpLinkSelEnd);
    const caret = jpLinkSelStart + markdown.length;
    textarea.setSelectionRange(caret, caret);
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    jpMdCommit(textarea);

    bootstrap.Modal.getInstance(document.getElementById('jpLinkModal'))?.hide();
}

// ============================================
// KEYBOARD SHORTCUTS
// Digit-based combos use e.code (the physical key) rather than e.key,
// because Shift+8/Shift+7/etc. produce symbol characters ('*', '&') in
// e.key on a US layout -- e.code stays "Digit8"/"Digit7" regardless.
// ============================================
function handleMarkdownShortcut(e, textarea) {
    const mod = e.ctrlKey || e.metaKey;
    if (!mod) return;

    if (e.code === 'KeyZ' && !e.shiftKey) { e.preventDefault(); jpMdUndo(textarea); return; }
    if ((e.code === 'KeyZ' && e.shiftKey) || e.code === 'KeyY') { e.preventDefault(); jpMdRedo(textarea); return; }

    if (e.code === 'KeyB') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.bold); return; }
    if (e.code === 'KeyI') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.italic); return; }
    if (e.code === 'KeyE') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.code); return; }
    if (e.code === 'KeyK') { e.preventDefault(); openLinkModal(textarea); return; }
    if (e.shiftKey && e.code === 'KeyX') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.strike); return; }
    if (e.shiftKey && e.code === 'Digit8') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.ul); return; }
    if (e.shiftKey && e.code === 'Digit7') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.ol); return; }
    if (e.altKey && e.code === 'Digit1') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.h1); return; }
    if (e.altKey && e.code === 'Digit2') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.h2); return; }
    if (e.altKey && e.code === 'Digit3') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.h3); return; }
}

function setupMarkdownEditor() {
    const textarea = document.getElementById('postingDescription');
    if (!textarea || textarea.dataset.mdWired) return;
    textarea.dataset.mdWired = '1';

    textarea.addEventListener('input', () => {
        autosizeTextarea(textarea);
        renderFullPreview();
        jpMdScheduleCommit(textarea);
    });
    textarea.addEventListener('keydown', e => handleMarkdownShortcut(e, textarea));

    document.querySelectorAll('.jp-md-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (btn.dataset.md === 'link') { openLinkModal(textarea); return; }
            const action = JP_MD_ACTIONS[btn.dataset.md];
            if (action) applyMarkdownAction(textarea, action);
        });
        // Same hover tooltip component used app-wide (e.g. the table
        // action icons) -- title already carries the label + shortcut,
        // e.g. "Bold (Ctrl+B)".
        if (btn.title && window.bootstrap && window.bootstrap.Tooltip) {
            btn.setAttribute('data-bs-toggle', 'tooltip');
            new window.bootstrap.Tooltip(btn, { trigger: 'hover focus', placement: 'top' });
        }
    });

    document.getElementById('jpMdUndoBtn')?.addEventListener('click', () => jpMdUndo(textarea));
    document.getElementById('jpMdRedoBtn')?.addEventListener('click', () => jpMdRedo(textarea));

    document.getElementById('jpLinkLabel')?.addEventListener('input', updateLinkPreview);
    document.getElementById('jpLinkUrl')?.addEventListener('input', updateLinkPreview);
    document.getElementById('jpLinkInsertBtn')?.addEventListener('click', insertLinkFromModal);

    // Live preview panel (permanent split, not a tab): re-render on every
    // field that feeds it, so it always reflects unsaved edits as you type.
    ['postingTitle', 'postingDepartment', 'postingLocation', 'postingSlots', 'postingOpenUntil'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', renderFullPreview);
        document.getElementById(id)?.addEventListener('change', renderFullPreview);
    });
}

function renderFullPreview() {
    const container = document.getElementById('postingFullPreview');
    if (!container) return;

    const title = document.getElementById('postingTitle').value.trim();
    const department = document.getElementById('postingDepartment').value.trim();
    const location = document.getElementById('postingLocation').value.trim();
    const slots = document.getElementById('postingSlots').value.trim();
    const description = document.getElementById('postingDescription').value.trim();
    const openUntil = document.getElementById('postingOpenUntil').value;

    const badges = [];
    if (department) badges.push(`<span class="jp-preview-badge"><i class="bi bi-briefcase"></i> ${jpEscapeHtml(department)}</span>`);
    if (location) badges.push(`<span class="jp-preview-badge"><i class="bi bi-geo-alt"></i> ${jpEscapeHtml(location)}</span>`);
    if (slots) badges.push(`<span class="jp-preview-badge"><i class="bi bi-people"></i> ${jpEscapeHtml(slots)} slot${slots == 1 ? '' : 's'}</span>`);

    const descriptionHtml = description
        ? window.mdToHtml(description)
        : '<p class="text-muted fst-italic mb-0">No description written yet.</p>';

    const closingHtml = openUntil
        ? `<p class="mb-0"><i class="bi bi-calendar-event"></i> Applications close <strong>${jpEscapeHtml(new Date(openUntil + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }))}</strong></p>`
        : '<p class="text-muted fst-italic mb-0">No closing date set yet.</p>';

    container.innerHTML = `
        <h4 class="jp-preview-title">${title ? jpEscapeHtml(title) : '<span class="text-muted fst-italic">Untitled position</span>'}</h4>
        <div class="jp-preview-badges">${badges.join('') || '<span class="text-muted small fst-italic">No department/location/slots set yet.</span>'}</div>
        <div class="jp-preview-section">
            <h6><i class="bi bi-file-text"></i> Job Description</h6>
            <div class="jp-preview-description">${descriptionHtml}</div>
        </div>
        <div class="jp-preview-section jp-preview-section-last">
            ${closingHtml}
        </div>
    `;
}

function openFormModal(posting) {
    const form = document.getElementById('postingForm');
    form.reset();
    document.getElementById('postingFormAlert').innerHTML = '';
    document.getElementById('postingId').value = posting ? posting.id : '';
    document.getElementById('postingFormTitle').textContent = posting ? 'Edit Job Posting' : 'New Job Posting';
    document.getElementById('postingTitle').value = posting ? posting.title : '';
    document.getElementById('postingDepartmentGroup').value = posting ? (posting.department_group || '') : '';
    window.refreshSearchableSelect && window.refreshSearchableSelect('postingDepartmentGroup');
    populatePositionOptions(posting ? (posting.department_group || '') : '', posting ? posting.department : null);
    document.getElementById('postingLocation').value = posting ? (posting.location || '') : '';
    document.getElementById('postingSlots').value = posting && posting.slots !== null ? posting.slots : '';
    const descriptionTextarea = document.getElementById('postingDescription');
    descriptionTextarea.value = posting ? posting.description : '';
    descriptionTextarea.style.height = '';
    jpMdResetHistory(descriptionTextarea);

    const openUntilInput = document.getElementById('postingOpenUntil');
    const today = new Date();
    const maxDate = new Date();
    maxDate.setMonth(maxDate.getMonth() + 6);
    const toIso = d => d.toISOString().slice(0, 10);
    openUntilInput.min = toIso(today);
    openUntilInput.max = toIso(maxDate);
    openUntilInput.value = posting ? posting.open_until : '';

    renderFullPreview();

    bootstrap.Offcanvas.getInstance(document.getElementById('postingDetailModal'))?.hide();
    const formModalEl = document.getElementById('postingFormModal');
    // scrollHeight reads 0 while the modal is still display:none, so the
    // initial autosize (for an existing description on Edit) has to wait
    // until Bootstrap has actually shown it.
    formModalEl.addEventListener('shown.bs.modal', () => autosizeTextarea(descriptionTextarea), { once: true });
    new bootstrap.Modal(formModalEl).show();
}

// job_postings.role has no user-facing meaning anymore (the form no longer
// asks for it) -- it only still exists in the DB as a NOT NULL, unique-among-
// active-postings internal key. Slugify the title for new postings so it
// still gets a sane, differentiated value; the id suffix keeps two postings
// with the same title from colliding on the uniqueness check.
function slugifyForRoleKey(title, id) {
    const slug = (title || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    return (slug || 'posting') + '-' + id;
}

function collectFormPayload() {
    const id = document.getElementById('postingId').value || undefined;
    const title = document.getElementById('postingTitle').value.trim();
    const payload = {
        id,
        title,
        department_group: document.getElementById('postingDepartmentGroup').value.trim(),
        department: document.getElementById('postingDepartment').value.trim(),
        location: document.getElementById('postingLocation').value.trim(),
        slots: document.getElementById('postingSlots').value,
        description: document.getElementById('postingDescription').value.trim(),
        open_until: document.getElementById('postingOpenUntil').value
    };
    // Editing an existing posting: leave `role` out entirely so the backend
    // keeps whatever value it already has (see update_job_posting.php).
    if (!id) {
        payload.role = slugifyForRoleKey(title, Date.now());
    }
    return payload;
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
                const errs = data.errors ? Object.values(data.errors).join(' ') : '';
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

    body.innerHTML = `
        <div class="row mb-2">
            <div class="col-md-6">
                <p class="mb-1"><strong>Title:</strong> ${jpEscapeHtml(p.title)}</p>
                <p class="mb-1"><strong>Department:</strong> ${jpEscapeHtml(p.department_group || '—')}</p>
                <p class="mb-0"><strong>Position:</strong> ${jpEscapeHtml(p.department)}</p>
                ${p.shares_location_count > 0 ? `<p class="mb-0 mt-1"><span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-geo-alt"></i> Shares location "${jpEscapeHtml(p.location || '')}" with ${p.shares_location_count} other active posting(s)</span></p>` : ''}
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
                bootstrap.Offcanvas.getInstance(document.getElementById('postingDetailModal'))?.hide();
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
