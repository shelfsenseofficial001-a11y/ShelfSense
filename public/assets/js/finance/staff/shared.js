// ============================================
// FINANCE STAFF — SHARED HELPERS
// ============================================

function fnEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function fnCurrency(amount) {
    return '₱' + (parseFloat(amount) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// The four standardized budget departments (see revenue_split_rules) with
// their display names -- any other department slug (e.g. an older
// free-text value) just falls back to a capitalized version of itself.
const FN_DEPARTMENT_LABELS = {
    hr: 'Human Resources Department',
    store: 'Store Department',
    finance: 'Finance Department',
    general: 'General Budget'
};
function fnDeptLabel(dept) {
    if (!dept) return '';
    return FN_DEPARTMENT_LABELS[dept] || (dept.charAt(0).toUpperCase() + dept.slice(1));
}

// Turns a budget cutoff key ("2026-08-H1"/"2026-08-H2") into a human label
// ("August 1-15, 2026"), mirroring App\Core\CutoffPeriod::describeKey() on
// the PHP side so raw keys are never shown to users. Falls back to the raw
// value for anything that isn't a recognized cutoff key (e.g. legacy data).
function fnCutoffLabel(key) {
    const m = /^(\d{4})-(\d{2})-H([12])$/.exec(key || '');
    if (!m) return key || '';
    const year = parseInt(m[1], 10);
    const month = parseInt(m[2], 10);
    const half = parseInt(m[3], 10);
    const daysInMonth = new Date(year, month, 0).getDate();
    const mid = daysInMonth >= 30 ? (daysInMonth === 31 ? 16 : 15) : 15;
    const monthName = new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long' });
    return half === 1 ? `${monthName} 1-${mid - 1}, ${year}` : `${monthName} ${mid}-${daysInMonth}, ${year}`;
}

function fnFormatDate(dateStr, withTime = false) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return fnEscapeHtml(dateStr);
    const opts = { year: 'numeric', month: 'short', day: 'numeric' };
    if (withTime) { opts.hour = '2-digit'; opts.minute = '2-digit'; }
    return d.toLocaleDateString('en-US', opts);
}

function fnPRNumber(id) {
    return 'PR-' + String(id).padStart(3, '0');
}

const FN_PR_STATUS_LABELS = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };
function fnPaymentStatusBadge(status) {
    const label = FN_PR_STATUS_LABELS[status] || status;
    return `<span class="fn-status-badge status-${fnEscapeHtml(status)}">${fnEscapeHtml(label)}</span>`;
}

// Human-readable labels for store_requisitions.status, used anywhere Finance Staff
// views a requisition's raw status so "awaiting_finance" doesn't read as ambiguous
// or get mistaken for something already paid.
const FN_REQ_STATUS_LABELS = {
    draft: 'Draft',
    pending_supplier: 'Pending Supplier',
    sent_to_supplier: 'Sent to Supplier',
    supplier_processed: 'Invoiced by Supplier',
    awaiting_finance_staff: 'Awaiting Finance Staff Review',
    awaiting_finance: 'Awaiting Finance Head Approval',
    paid: 'Paid',
    shipped: 'Shipped',
    completed: 'Completed',
    partial_received: 'Partially Received'
};
function fnReqStatusLabel(status) {
    return FN_REQ_STATUS_LABELS[status] || status;
}
function fnReqStatusBadge(status) {
    return `<span class="fn-status-badge status-${fnEscapeHtml(status)}">${fnEscapeHtml(fnReqStatusLabel(status))}</span>`;
}

const FN_BUDGET_STATUS_LABELS = {
    within_budget: '✅ Within Budget',
    near_limit: '⚠️ Near Limit',
    exceeded: '⚠️ Exceeded',
    no_budget: 'No Budget Allocated'
};
function fnBudgetStatusBadge(status) {
    const label = FN_BUDGET_STATUS_LABELS[status] || status;
    return `<span class="fn-status-badge status-${fnEscapeHtml(status)}">${fnEscapeHtml(label)}</span>`;
}

function fnEmptyState(message, icon = 'bi-inbox') {
    return `<div class="fn-empty-state"><i class="bi ${icon}"></i>${fnEscapeHtml(message)}</div>`;
}
function fnErrorState(message) {
    return `<div class="fn-error-state"><i class="bi bi-exclamation-triangle"></i>${fnEscapeHtml(message || 'Something went wrong. Please try again.')}</div>`;
}

function fnRenderPagination(container, infoEl, pagination, itemLabel, onPageChange) {
    if (!container) return;
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        if (infoEl) infoEl.textContent = `${pagination?.totalRecords || 0} ${itemLabel}`;
        return;
    }
    if (infoEl) infoEl.textContent = `Page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} ${itemLabel})`;

    let html = '';
    html += pagination.currentPage > 1
        ? `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>`
        : `<li class="page-item disabled"><span class="page-link">«</span></li>`;

    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);
    if (start > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    for (let i = start; i <= end; i++) {
        html += i === pagination.currentPage
            ? `<li class="page-item active"><span class="page-link">${i}</span></li>`
            : `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    if (end < pagination.totalPages) {
        if (end < pagination.totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
    }
    html += pagination.currentPage < pagination.totalPages
        ? `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage + 1}">»</a></li>`
        : `<li class="page-item disabled"><span class="page-link">»</span></li>`;

    container.innerHTML = html;
    container.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            onPageChange(parseInt(this.dataset.page));
        });
    });
}

// Renders the standard "budget check" box used on requisition cards and the
// Create Payment Request modal. Uses ONLY the fields returned by getBudgetStatus()
// on the server — never fabricates or recomputes budget numbers client-side.
function fnBudgetBox(bs) {
    if (!bs.has_allocation) {
        return `
            <div class="fn-doc-box">
                <div class="fn-doc-title">💰 Budget Status</div>
                <div class="fn-status-badge status-no_budget">No budget allocated</div>
                <div class="small text-muted mt-1">No allocation exists for ${fnEscapeHtml(fnDeptLabel(bs.department))} / ${fnEscapeHtml(fnCutoffLabel(bs.month_year))}. Requested: ${fnCurrency(bs.requested)}.</div>
            </div>
        `;
    }
    const barClass = bs.status === 'exceeded' ? 'exceeded' : (bs.status === 'near_limit' ? 'near_limit' : '');
    const pct = Math.min(100, bs.used_percentage || 0);
    return `
        <div class="fn-doc-box">
            <div class="fn-doc-title">💰 Budget Status (${fnEscapeHtml(fnDeptLabel(bs.department))} — ${fnEscapeHtml(fnCutoffLabel(bs.month_year))})</div>
            <div class="d-flex justify-content-between small mb-1">
                <span>Allocated: <strong>${fnCurrency(bs.allocated)}</strong></span>
                <span>Used: <strong>${fnCurrency(bs.used)}</strong></span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span>Reserved: <strong>${fnCurrency(bs.reserved)}</strong></span>
                <span>Available: <strong>${fnCurrency(bs.available)}</strong></span>
            </div>
            <div class="fn-budget-bar-track mb-1"><div class="fn-budget-bar-fill ${barClass}" style="width:${pct}%;"></div></div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="small">Requested: <strong>${fnCurrency(bs.requested)}</strong></span>
                ${fnBudgetStatusBadge(bs.status)}
            </div>
            ${bs.exceeded ? `<div class="fn-budget-warning mt-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>Exceeds available budget by <strong>${fnCurrency(bs.shortfall)}</strong>. Finance Head approval required; a justification is needed to submit.</div>` : ''}
        </div>
    `;
}

// ============================================
// SHARED INPUT VALIDATION (all Finance pages)
// Loaded by views/layouts/finance.php for every Finance role.
// ============================================

// Keeps a field's error text in sync with Bootstrap's .is-invalid styling.
// The feedback node is toggled with d-block because some of these fields
// are not an immediate sibling of their input (e.g. an amount inside an
// .input-group), so Bootstrap's .is-invalid ~ .invalid-feedback rule
// never matches on its own.
function fnSetFieldError(input, feedbackId, message) {
    const feedback = document.getElementById(feedbackId);
    if (input) input.classList.toggle('is-invalid', !!message);
    if (feedback) {
        feedback.textContent = message || '';
        feedback.classList.toggle('d-block', !!message);
    }
}

// Stops the characters a number input would otherwise accept but that are
// never valid for money or percentages -- the exponent form (1e5) and any
// sign, so a negative can't even be typed.
function fnBlockInvalidNumberKeys(input, allowDecimal) {
    if (!input) return;
    input.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        const blocked = ['e', 'E', '+', '-'];
        if (!allowDecimal) blocked.push('.', ',');
        if (blocked.includes(e.key)) e.preventDefault();
    });
    input.addEventListener('paste', function (e) {
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const ok = allowDecimal ? /^\d*\.?\d*$/.test(text.trim()) : /^\d+$/.test(text.trim());
        if (!ok) e.preventDefault();
    });
}

// Validates one numeric field against a min/max range and 2-decimal money
// precision. Returns the error string ('' when valid) so callers can
// aggregate several fields before deciding whether to enable submit.
function fnValidateNumber(raw, opts) {
    const o = opts || {};
    const text = (raw || '').trim();
    if (!text) return o.required ? (o.label || 'This field') + ' is required.' : '';
    if (!/^\d+(\.\d{1,2})?$/.test(text)) return 'Use a number with at most 2 decimal places.';
    const value = parseFloat(text);
    if (!isFinite(value)) return 'Enter a valid number.';
    if (o.min !== undefined && value < o.min) return 'Cannot be less than ' + o.min + '.';
    if (o.max !== undefined && value > o.max) return 'Cannot be more than ' + o.max.toLocaleString() + '.';
    return '';
}

// Validates a free-text field (reasons, justifications, references).
// opts: {required, min, max, pattern, patternMessage, label}
function fnValidateText(raw, opts) {
    const o = opts || {};
    const text = (raw || '').trim();
    if (!text) return o.required ? (o.label || 'This field') + ' is required.' : '';
    if (o.min && text.length < o.min) return 'Use at least ' + o.min + ' characters.';
    if (o.max && text.length > o.max) return 'Keep it under ' + o.max + ' characters.';
    if (o.pattern && !o.pattern.test(text)) return o.patternMessage || 'Contains characters that are not allowed.';
    return '';
}

// Wires a field for live validation: marks it touched on first input, runs
// `validate` on every change, and keeps a submit button disabled while the
// form is invalid. Returns the validate function for direct calls.
function fnWireLiveValidation(inputIds, validate) {
    inputIds.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        ['input', 'blur', 'change'].forEach(evt => el.addEventListener(evt, () => {
            el.dataset.touched = '1';
            validate();
        }));
    });
    return validate;
}
