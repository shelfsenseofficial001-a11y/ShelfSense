// ============================================
// FINANCE HEAD - PAYMENT REQUESTS (4-tab: pending/approved/rejected/all)
// ============================================

console.log('✅ finance/head/payment_requests.js loaded');

let fhTab = 'pending';
let fhPage = 1;
let fhBusy = false; // guards against duplicate approve/reject submissions
let fhActiveRow = null; // the row currently open in a modal

document.addEventListener('DOMContentLoaded', function () {
    setupTabs();
    setupModals();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'search', type: 'search', elementId: 'searchInput' },
            { key: 'budgetStatus', type: 'select', elementId: 'budgetStatusFilter' },
            { key: 'dateFrom', type: 'date', elementId: 'dateFrom', labelPrefix: 'From' },
            { key: 'dateTo', type: 'date', elementId: 'dateTo', labelPrefix: 'To' },
        ]);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    const initialTab = ['pending', 'approved', 'rejected', 'all'].includes(requestedTab) ? requestedTab : 'pending';
    if (initialTab !== 'pending') {
        document.querySelectorAll('#reqTabs button').forEach(b => b.classList.remove('active'));
        document.querySelector(`#reqTabs button[data-tab-key="${initialTab}"]`)?.classList.add('active');
    }
    loadTab(initialTab, 1);
});

function setupTabs() {
    document.querySelectorAll('#reqTabs button[data-tab-key]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#reqTabs button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadTab(this.dataset.tabKey, 1);
        });
    });

    document.getElementById('searchInput')?.addEventListener('input', fhDebounce(() => loadTab(fhTab, 1), 400));
    document.getElementById('budgetStatusFilter')?.addEventListener('change', () => loadTab(fhTab, 1));
    document.getElementById('dateFrom')?.addEventListener('change', () => loadTab(fhTab, 1));
    document.getElementById('dateTo')?.addEventListener('change', () => loadTab(fhTab, 1));
    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        document.getElementById('searchInput').value = '';
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        const bs = document.getElementById('budgetStatusFilter');
        if (bs) { bs.value = ''; window.refreshSearchableSelect && window.refreshSearchableSelect(bs); }
        loadTab(fhTab, 1);
    });
}

function fhDebounce(fn, wait) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function loadTab(tab, page) {
    fhTab = tab;
    fhPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const budgetStatus = document.getElementById('budgetStatusFilter')?.value || '';
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    const container = document.getElementById('fn-cards-container');
    container.innerHTML = `<div class="text-center py-4" style="grid-column:1/-1;"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading...</p></div>`;

    const params = new URLSearchParams({ p: page, limit: 9, tab });
    if (search) params.append('search', search);
    if (budgetStatus) params.append('budget_status', budgetStatus);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    fetch(`?page=api_finance_get_payment_requests&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderCards(container, data.data.payment_requests, tab);
                fnRenderPagination(
                    document.getElementById('paginationContainer'),
                    document.getElementById('tableInfo'),
                    data.data.pagination,
                    'requests',
                    (p) => loadTab(tab, p)
                );
                updateTabCounts(data.data.tab_counts);
            } else {
                container.innerHTML = fnErrorState(data.message || 'Failed to load payment requests');
            }
        })
        .catch(() => { container.innerHTML = fnErrorState(); });
}

function updateTabCounts(counts) {
    if (!counts) return;
    document.getElementById('countPending').textContent = counts.pending ?? 0;
    document.getElementById('countApproved').textContent = counts.approved ?? 0;
    document.getElementById('countRejected').textContent = counts.rejected ?? 0;
    document.getElementById('countAll').textContent = counts.all ?? 0;
}

function renderCards(container, requests, tab) {
    if (!requests || requests.length === 0) {
        container.innerHTML = fnEmptyState('Nothing to show in this view.');
        return;
    }
    container.innerHTML = requests.map(r => buildCard(r)).join('');

    container.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', () => viewRequisitionDetail(parseInt(btn.dataset.id)));
    });
}

function buildCard(r) {
    const isPending = r.status === 'pending';
    const exceeded = isPending ? (r.budget_status ? r.budget_status.exceeded : false) : !!r.budget_exceeded;

    let statusLine = '';
    if (r.status === 'approved') {
        statusLine = `<div class="small text-success mt-1">✅ Approved by ${fnEscapeHtml(r.approved_first || '')} ${fnEscapeHtml(r.approved_last || '')} — ${fnFormatDate(r.approved_at, true)}</div>`;
    } else if (r.status === 'rejected') {
        statusLine = `<div class="small text-danger mt-1">❌ Rejected: ${fnEscapeHtml(r.rejection_reason || '')}</div>`;
    }

    return `
        <div class="fn-req-card ${exceeded ? 'exceeded' : ''}">
            <div class="fn-req-header">
                <div>
                    <div class="fn-req-number">${fnPRNumber(r.id)} &nbsp;${fnEscapeHtml(r.requisition_number)}</div>
                    <div class="fn-req-sub">${fnEscapeHtml(r.company_name)}</div>
                </div>
                ${exceeded ? fnBudgetStatusBadge('exceeded') : fnPaymentStatusBadge(r.status)}
            </div>
            <div class="small text-muted mb-2">Requested by ${fnEscapeHtml(r.requested_first)} ${fnEscapeHtml(r.requested_last)} — ${fnFormatDate(r.requested_at, true)}</div>

            <div class="fn-doc-box mb-2">
                <div class="fn-doc-title">📄 Supplier Invoice</div>
                <div>Invoice #: <strong>${fnEscapeHtml(r.invoice_number)}</strong> &nbsp; Due: <strong>${fnFormatDate(r.due_date)}</strong></div>
                <div>Total: <strong>${fnCurrency(r.invoice_total)}</strong></div>
            </div>

            ${isPending && r.budget_status ? fnBudgetBox(r.budget_status) : ''}
            ${statusLine}

            ${r.notes ? `<div class="fn-doc-box mt-2"><div class="fn-doc-title">📋 Finance Staff Notes</div><div>${fnEscapeHtml(r.notes)}</div></div>` : ''}

            <div class="fn-req-actions mt-2">
                <button class="btn btn-sm btn-outline-primary view-btn" data-id="${r.requisition_id}"><i class="bi bi-eye"></i> View Full Details</button>
            </div>
        </div>
    `;
}

// ============================================
// REQUISITION / PAYMENT REQUEST DETAIL
// ============================================

function viewRequisitionDetail(requisitionId) {
    const modal = document.getElementById('requestDetailModal');
    const body = document.getElementById('requestDetailBody');
    const footer = document.getElementById('requestDetailFooter');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    footer.innerHTML = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;
    new bootstrap.Modal(modal).show();

    fetch(`?page=api_finance_get_requisition_detail&id=${requisitionId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderRequisitionDetail(data.data.requisition);
            } else {
                body.innerHTML = fnErrorState(data.message);
            }
        })
        .catch(() => { body.innerHTML = fnErrorState(); });
}

function renderRequisitionDetail(req) {
    const body = document.getElementById('requestDetailBody');
    const footer = document.getElementById('requestDetailFooter');
    const pr = req.payment_request;

    let itemsHtml = '<tr><td colspan="5" class="text-center text-muted">No items</td></tr>';
    if (req.items && req.items.length > 0) {
        itemsHtml = req.items.map(item => `
            <tr>
                <td>${fnEscapeHtml(item.store_product_name)}</td>
                <td>${item.quantity}</td>
                <td>${fnCurrency(item.unit_price)}</td>
                <td>${fnCurrency(item.total)}</td>
            </tr>
        `).join('');
    }

    // 3-way matching: PO (requisition items) vs Invoice (same items — invoices are
    // generated 1:1 from requisition items) vs Goods Receipt (only if it exists).
    const gr = req.goods_receipt;
    let matchRows = '';
    if (req.items && req.items.length > 0) {
        matchRows = req.items.map(item => {
            let grQty = '—';
            let matchIcon = '<span class="fn-match-pending">⏳ Pending (not yet received)</span>';
            if (gr && gr.items) {
                const grItem = gr.items.find(g => g.product_name === item.store_product_name);
                if (grItem) {
                    grQty = grItem.quantity_received;
                    matchIcon = grItem.quantity_received == item.quantity
                        ? '<span class="fn-match-ok">✅ Match</span>'
                        : `<span class="text-danger">⚠️ Mismatch (ordered ${item.quantity}, received ${grItem.quantity_received})</span>`;
                }
            }
            return `<tr><td>${fnEscapeHtml(item.store_product_name)}</td><td>${item.quantity}</td><td>${item.quantity}</td><td>${grQty}</td><td>${matchIcon}</td></tr>`;
        }).join('');
    }

    body.innerHTML = `
        <div class="row mb-2">
            <div class="col-md-6">
                <p class="mb-1"><strong>Requisition #:</strong> ${fnEscapeHtml(req.requisition_number)}</p>
                <p class="mb-1"><strong>Store:</strong> ${fnEscapeHtml(req.first_name)} ${fnEscapeHtml(req.last_name)}</p>
                <p class="mb-0"><strong>Order Date:</strong> ${fnFormatDate(req.order_date)}</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Status:</strong> ${fnReqStatusBadge(req.status)}</p>
                <p class="mb-1"><strong>Total:</strong> ${fnCurrency(req.total)}</p>
                ${pr ? `<p class="mb-0"><strong>Payment Request:</strong> ${fnPRNumber(pr.id)} — ${fnPaymentStatusBadge(pr.status)}</p>` : ''}
            </div>
        </div>

        ${req.invoice ? `
        <div class="fn-doc-box mb-2">
            <div class="fn-doc-title">📄 Supplier Invoice (Primary Document)</div>
            <div>Invoice #: <strong>${fnEscapeHtml(req.invoice.invoice_number)}</strong> &nbsp; Invoice Date: <strong>${fnFormatDate(req.invoice.invoice_date)}</strong></div>
            <div>Due Date: <strong>${fnFormatDate(req.invoice.due_date)}</strong> &nbsp; Total: <strong>${fnCurrency(req.invoice.total)}</strong></div>
            ${req.invoice.notes ? `<div class="text-muted">Supplier notes: ${fnEscapeHtml(req.invoice.notes)}</div>` : ''}
        </div>` : '<p class="text-muted">No invoice on file yet.</p>'}

        <div class="fn-doc-box mb-2">
            <div class="fn-doc-title">📋 Original Requisition (Reference / Audit)</div>
            <div>Created: <strong>${fnFormatDate(req.created_at, true)}</strong> by ${fnEscapeHtml(req.first_name)} ${fnEscapeHtml(req.last_name)} (Store Manager)</div>
            <div>Expected Delivery: <strong>${fnFormatDate(req.expected_delivery)}</strong></div>
            ${req.notes ? `<div class="text-muted">Notes: ${fnEscapeHtml(req.notes)}</div>` : ''}
        </div>

        ${req.budget_status ? fnBudgetBox(req.budget_status) : ''}

        ${pr && pr.notes ? `<div class="fn-doc-box mt-2 mb-2"><div class="fn-doc-title">📋 Finance Staff Notes</div><div>${fnEscapeHtml(pr.notes)}</div></div>` : ''}
        ${pr && pr.approval_notes ? `<div class="fn-doc-box mt-2 mb-2"><div class="fn-doc-title">🏛️ Finance Head Notes</div><div>${fnEscapeHtml(pr.approval_notes)}</div></div>` : ''}
        ${pr && pr.status === 'rejected' && pr.rejection_reason ? `<div class="fn-budget-warning mt-2 mb-2"><strong>Rejection reason:</strong> ${fnEscapeHtml(pr.rejection_reason)}</div>` : ''}

        <hr>
        <h6 class="fw-bold">Items</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>${itemsHtml}</tbody>
            </table>
        </div>

        <h6 class="fw-bold">3-Way Matching</h6>
        <div class="table-responsive mb-2">
            <table class="table table-sm fn-match-table">
                <thead><tr><th>Item</th><th>PO Qty</th><th>Invoice Qty</th><th>Received</th><th>Status</th></tr></thead>
                <tbody>${matchRows || '<tr><td colspan="5" class="text-center text-muted">No items</td></tr>'}</tbody>
            </table>
        </div>
        <p class="text-muted small mb-0">${gr ? 'Goods receipt data is available for this requisition.' : 'No Goods Receipt recorded yet — this is a pre-payment workflow, so matching against received quantities is expected to be pending at this stage.'}</p>

        <h6 class="fw-bold mt-3">Timeline</h6>
        <div class="fn-timeline">
            <div class="fn-timeline-item"><div class="fn-timeline-title">Requisition created</div><div class="fn-timeline-date">${fnFormatDate(req.created_at, true)}</div></div>
            ${req.invoice ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Supplier processed &amp; invoice created</div><div class="fn-timeline-date">${fnFormatDate(req.invoice.created_at, true)}</div></div>` : ''}
            ${pr ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Payment Request created by Finance Staff</div><div class="fn-timeline-date">${fnFormatDate(pr.requested_at, true)}</div></div>` : ''}
            ${pr && pr.status === 'approved' ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Approved by Finance Head</div><div class="fn-timeline-date">${fnFormatDate(pr.approved_at, true)}</div></div>` : ''}
            ${pr && pr.status === 'rejected' ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Rejected by Finance Head</div><div class="fn-timeline-date">&nbsp;</div></div>` : ''}
            ${pr && pr.status === 'pending' ? `<div class="fn-timeline-item pending"><div class="fn-timeline-title text-muted">⏳ Awaiting Finance Head Approval (You)</div></div>` : ''}
            ${req.goods_receipt ? `<div class="fn-timeline-item"><div class="fn-timeline-title">Goods received by store</div><div class="fn-timeline-date">${fnFormatDate(req.goods_receipt.created_at, true)}</div></div>` : ''}
        </div>
        <p class="text-muted small mb-0">Only events with a real recorded timestamp are shown.</p>
    `;

    if (pr && pr.status === 'pending') {
        const row = Object.assign({}, pr, {
            requisition_id: req.id,
            requisition_number: req.requisition_number,
            company_name: req.company_name,
            requisition_total: req.total,
            invoice_number: req.invoice ? req.invoice.invoice_number : null,
            invoice_total: req.invoice ? req.invoice.total : null,
            due_date: req.invoice ? req.invoice.due_date : null,
            budget_status: req.budget_status
        });
        footer.innerHTML = `
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success btn-sm" id="detailApproveBtn"><i class="bi bi-check-circle"></i> Approve</button>
            <button type="button" class="btn btn-danger btn-sm" id="detailRejectBtn"><i class="bi bi-x-circle"></i> Reject</button>
        `;
        document.getElementById('detailApproveBtn').addEventListener('click', () => openApproveModalFromRow(row));
        document.getElementById('detailRejectBtn').addEventListener('click', () => openRejectModalFromRow(row));
    } else {
        footer.innerHTML = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;
    }
}

// ============================================
// APPROVE / REJECT MODALS
// ============================================

function setupModals() {
    document.getElementById('confirmApproveBtn')?.addEventListener('click', submitApprove);
    document.getElementById('confirmRejectBtn')?.addEventListener('click', submitReject);
}

function openApproveModalFromRow(row) {
    fhActiveRow = row;
    bootstrap.Modal.getInstance(document.getElementById('requestDetailModal'))?.hide();

    const bs = row.budget_status;
    const exceeded = bs ? bs.exceeded : false;

    document.getElementById('approveModalTitle').textContent = exceeded ? '✅ Approve Payment Request (Budget Exceeded)' : '✅ Approve Payment Request';
    document.getElementById('approveNotesLabel').textContent = exceeded ? 'Justification for Overage (Required)' : 'Approval Notes (Optional)';
    document.getElementById('approveNotes').value = '';
    document.getElementById('approveNotes').classList.remove('is-invalid');

    document.getElementById('approveModalSummary').innerHTML = `
        <p>You are about to approve ${fnPRNumber(row.id)} for <strong>${fnEscapeHtml(row.requisition_number)}</strong>.</p>
        <div class="fn-doc-box">
            <div>Invoice #: <strong>${fnEscapeHtml(row.invoice_number)}</strong></div>
            <div>Supplier: <strong>${fnEscapeHtml(row.company_name)}</strong></div>
            <div>Total: <strong>${fnCurrency(row.requisition_total)}</strong></div>
        </div>
        ${bs ? fnBudgetBox(bs) : ''}
    `;

    let consequences = '1. ✅ Approve the payment request<br>2. 💰 Auto-record the payment<br>3. 📨 Notify Finance Staff and Supplier<br>4. 📦 Supplier will be able to ship goods';
    if (exceeded) consequences += '<br>5. ⚠️ Budget will show an over-budget balance for the month';
    document.getElementById('approveConsequences').innerHTML = consequences;

    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function submitApprove() {
    if (fhBusy || !fhActiveRow) return;
    const bs = fhActiveRow.budget_status;
    const exceeded = bs ? bs.exceeded : false;
    const notes = document.getElementById('approveNotes').value.trim();

    if (exceeded && !notes) {
        document.getElementById('approveNotes').classList.add('is-invalid');
        return;
    }
    document.getElementById('approveNotes').classList.remove('is-invalid');

    fhBusy = true;
    const btn = document.getElementById('confirmApproveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Approving...';

    fetch('?page=api_finance_approve_payment_request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ payment_request_id: fhActiveRow.id, action: 'approve', notes })
    })
        .then(r => r.json())
        .then(data => {
            fhBusy = false;
            btn.disabled = false;
            btn.innerHTML = 'Confirm Approval';
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('approveModal'))?.hide();
                Swal.fire({ icon: 'success', title: 'Payment Approved!', text: data.message, timer: 2500, showConfirmButton: false });
                loadTab(fhTab, fhPage);
            } else {
                Swal.fire({ icon: 'error', title: 'Could Not Approve', text: data.message || 'Please try again.' });
            }
        })
        .catch(() => {
            fhBusy = false;
            btn.disabled = false;
            btn.innerHTML = 'Confirm Approval';
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
        });
}

function openRejectModalFromRow(row) {
    fhActiveRow = row;
    bootstrap.Modal.getInstance(document.getElementById('requestDetailModal'))?.hide();

    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectReason').classList.remove('is-invalid');
    document.getElementById('rejectModalSummary').innerHTML = `
        <p>You are about to reject ${fnPRNumber(row.id)} for <strong>${fnEscapeHtml(row.requisition_number)}</strong>.</p>
        <div class="fn-doc-box">
            <div>Invoice #: <strong>${fnEscapeHtml(row.invoice_number)}</strong></div>
            <div>Supplier: <strong>${fnEscapeHtml(row.company_name)}</strong></div>
            <div>Total: <strong>${fnCurrency(row.requisition_total)}</strong></div>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function submitReject() {
    if (fhBusy || !fhActiveRow) return;
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        document.getElementById('rejectReason').classList.add('is-invalid');
        return;
    }
    document.getElementById('rejectReason').classList.remove('is-invalid');

    fhBusy = true;
    const btn = document.getElementById('confirmRejectBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Rejecting...';

    fetch('?page=api_finance_approve_payment_request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ payment_request_id: fhActiveRow.id, action: 'reject', reason })
    })
        .then(r => r.json())
        .then(data => {
            fhBusy = false;
            btn.disabled = false;
            btn.innerHTML = 'Confirm Rejection';
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide();
                Swal.fire({ icon: 'success', title: 'Payment Request Rejected', text: data.message, timer: 2500, showConfirmButton: false });
                loadTab(fhTab, fhPage);
            } else {
                Swal.fire({ icon: 'error', title: 'Could Not Reject', text: data.message || 'Please try again.' });
            }
        })
        .catch(() => {
            fhBusy = false;
            btn.disabled = false;
            btn.innerHTML = 'Confirm Rejection';
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
        });
}
