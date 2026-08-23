// ============================================
// FINANCE STAFF - PENDING REQUISITIONS (4-tab)
// ============================================

console.log('✅ finance/staff/requisitions.js loaded');

let currentTab = 'to_review';
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function () {
    setupTabs();
    setupModals();
    loadTab('to_review', 1);

    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    if (requestedTab) {
        const btn = document.querySelector(`#reqTabs button[data-tab-key="${requestedTab}"]`);
        if (btn) btn.click();
    }
});

function setupTabs() {
    document.querySelectorAll('#reqTabs button[data-tab-key]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#reqTabs button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentTab = this.dataset.tabKey;
            loadTab(currentTab, 1);
        });
    });

    document.getElementById('searchInput')?.addEventListener('input', debounceFn(() => loadTab(currentTab, 1), 400));
    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        document.getElementById('searchInput').value = '';
        loadTab(currentTab, 1);
    });
}

function debounceFn(fn, wait) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function loadTab(tab, page) {
    currentTab = tab;
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();

    const container = document.getElementById('fn-cards-container');
    container.innerHTML = `<div class="text-center py-4" style="grid-column:1/-1;"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading...</p></div>`;

    const params = new URLSearchParams({ p: page, limit: 9, tab });
    if (search) params.append('search', search);

    fetch(`?page=api_finance_get_pending_requisitions&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderCards(container, data.data.requisitions, tab);
                fnRenderPagination(
                    document.getElementById('paginationContainer'),
                    document.getElementById('tableInfo'),
                    data.data.pagination,
                    'requisitions',
                    (p) => loadTab(tab, p)
                );
                updateTabCounts(data.data.tab_counts);
            } else {
                container.innerHTML = fnErrorState(data.message || 'Failed to load requisitions');
            }
        })
        .catch(() => { container.innerHTML = fnErrorState(); });
}

function updateTabCounts(counts) {
    if (!counts) return;
    document.getElementById('countToReview').textContent = counts.to_review ?? 0;
    document.getElementById('countExceeded').textContent = counts.budget_exceeded ?? 0;
    document.getElementById('countAwaiting').textContent = counts.awaiting_approval ?? 0;
    document.getElementById('countHistory').textContent = counts.my_history ?? 0;
}

function renderCards(container, requisitions, tab) {
    if (!requisitions || requisitions.length === 0) {
        container.innerHTML = fnEmptyState('Nothing to show in this view.');
        return;
    }
    container.innerHTML = requisitions.map(req => buildCard(req, tab)).join('');

    container.querySelectorAll('.view-req-btn').forEach(btn => {
        btn.addEventListener('click', () => viewRequisitionDetail(btn.dataset.id));
    });
    container.querySelectorAll('.create-pr-btn').forEach(btn => {
        btn.addEventListener('click', () => openPaymentRequestModal(btn.dataset.id));
    });
}

function buildCard(req, tab) {
    const isReviewTab = tab === 'to_review' || tab === 'budget_exceeded';
    const bs = req.budget_status;
    const exceeded = isReviewTab ? bs.exceeded : !!req.budget_exceeded;

    let docBox = '';
    if (req.invoice) {
        docBox = `
            <div class="fn-doc-box mb-2">
                <div class="fn-doc-title">📄 Supplier Invoice</div>
                <div>Invoice #: <strong>${fnEscapeHtml(req.invoice.invoice_number)}</strong></div>
                <div>Due Date: <strong>${fnFormatDate(req.invoice.due_date)}</strong></div>
                <div>Total: <strong>${fnCurrency(req.invoice.total)}</strong></div>
                ${req.invoice.notes ? `<div class="text-muted">Notes: ${fnEscapeHtml(req.invoice.notes)}</div>` : ''}
            </div>
        `;
    } else if (req.invoice_number) {
        docBox = `
            <div class="fn-doc-box mb-2">
                <div class="fn-doc-title">📄 Supplier Invoice</div>
                <div>Invoice #: <strong>${fnEscapeHtml(req.invoice_number)}</strong></div>
                <div>Due Date: <strong>${fnFormatDate(req.due_date)}</strong></div>
                <div>Total: <strong>${fnCurrency(req.invoice_total)}</strong></div>
                ${req.supplier_notes ? `<div class="text-muted">Notes: ${fnEscapeHtml(req.supplier_notes)}</div>` : ''}
            </div>
        `;
    }

    let budgetBox = '';
    if (isReviewTab) {
        budgetBox = fnBudgetBox(bs);
    } else if (req.payment_request_id) {
        budgetBox = `
            <div class="fn-doc-box">
                <div class="fn-doc-title">💰 Payment Request</div>
                <div>${fnPRNumber(req.payment_request_id)} — ${fnPaymentStatusBadge(req.payment_request_status)}</div>
                <div>Requested: <strong>${fnFormatDate(req.requested_at)}</strong></div>
                ${req.budget_exceeded ? `<div class="fn-budget-warning mt-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>${fnEscapeHtml(req.budget_exceeded_reason || 'Budget exceeded')}</div>` : ''}
            </div>
        `;
    }

    let actions = `<button class="btn btn-sm btn-outline-primary view-req-btn" data-id="${req.id}"><i class="bi bi-eye"></i> View Details</button>`;
    if (isReviewTab) {
        actions += `<button class="btn btn-sm btn-yellow-primary create-pr-btn" data-id="${req.id}"><i class="bi bi-cash"></i> Create Payment Request</button>`;
    }

    return `
        <div class="fn-req-card ${exceeded ? 'exceeded' : ''}">
            <div class="fn-req-header">
                <div>
                    <div class="fn-req-number">${fnEscapeHtml(req.requisition_number)}</div>
                    <div class="fn-req-sub">Supplier: ${fnEscapeHtml(req.company_name)} · Store: ${fnEscapeHtml(req.first_name)} ${fnEscapeHtml(req.last_name)}</div>
                </div>
                ${exceeded ? '<span class="fn-status-badge status-exceeded">⚠️ Budget Exceeded</span>' : ''}
            </div>
            ${docBox}
            ${budgetBox}
            <div class="d-flex justify-content-between small text-muted mt-2">
                <span>Items: ${req.item_count ?? 0}</span>
                <span>Ordered: ${fnFormatDate(req.order_date)}</span>
            </div>
            <div class="fn-req-actions">${actions}</div>
        </div>
    `;
}

// ============================================
// REQUISITION DETAIL
// ============================================

function viewRequisitionDetail(id) {
    const modal = document.getElementById('requisitionDetailModal');
    const body = document.getElementById('requisitionDetailBody');
    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>`;
    new bootstrap.Modal(modal).show();

    fetch(`?page=api_finance_get_requisition_detail&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderRequisitionDetail(data.data.requisition);
            } else {
                body.innerHTML = fnErrorState(data.message || 'Failed to load details');
            }
        })
        .catch(() => { body.innerHTML = fnErrorState(); });
}

function renderRequisitionDetail(req) {
    const body = document.getElementById('requisitionDetailBody');
    const footer = document.getElementById('requisitionDetailFooter');

    let itemsHtml = '<tr><td colspan="5" class="text-center text-muted">No items</td></tr>';
    if (req.items && req.items.length > 0) {
        itemsHtml = req.items.map(item => `
            <tr>
                <td>${fnEscapeHtml(item.store_product_name)}</td>
                <td>${fnEscapeHtml(item.supplier_product_name)}</td>
                <td>${item.quantity}</td>
                <td>${fnCurrency(item.unit_price)}</td>
                <td>${fnCurrency(item.total)}</td>
            </tr>
        `).join('');
    }

    // 3-way matching: PO (requisition items) vs Invoice (same items — the schema has no
    // separate invoice-line-item table, invoices are generated 1:1 from requisition items)
    // vs Goods Receipt (only if the store has actually recorded receiving goods).
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
                <p class="mb-0"><strong>Expected Delivery:</strong> ${fnFormatDate(req.expected_delivery)}</p>
            </div>
        </div>
        ${req.status === 'awaiting_finance' ? `<div class="fn-budget-warning mb-2"><i class="bi bi-hourglass-split me-1"></i>A payment request has been submitted and is waiting for the Finance Head to approve it. <strong>No payment has been made yet.</strong></div>` : ''}
        ${req.notes ? `<p><strong>Requisition Notes:</strong> ${fnEscapeHtml(req.notes)}</p>` : ''}
        ${req.invoice ? `
            <hr><h6 class="fw-bold">Supplier Invoice</h6>
            <p class="mb-1"><strong>Invoice #:</strong> ${fnEscapeHtml(req.invoice.invoice_number)} &nbsp; <strong>Due:</strong> ${fnFormatDate(req.invoice.due_date)}</p>
            ${req.invoice.notes ? `<p class="mb-0 text-muted">Supplier notes: ${fnEscapeHtml(req.invoice.notes)}</p>` : ''}
        ` : '<p class="text-muted">No invoice on file yet.</p>'}
        <hr>
        <h6 class="fw-bold">Items</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead><tr><th>Store Product</th><th>Supplier Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
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
        <p class="text-muted small mb-0">${gr ? 'Goods receipt data is available for this requisition.' : 'No Goods Receipt recorded yet — matching against actual received quantities is pending until the Store Manager receives the goods.'}</p>
    `;

    let actionsHtml = `<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>`;
    if (req.status === 'awaiting_finance_staff') {
        actionsHtml += `<button type="button" class="btn btn-yellow-primary btn-sm" id="detailCreatePrBtn" data-id="${req.id}"><i class="bi bi-cash"></i> Create Payment Request</button>`;
    }
    footer.innerHTML = actionsHtml;
    document.getElementById('detailCreatePrBtn')?.addEventListener('click', function () {
        openPaymentRequestModal(this.dataset.id);
    });
}

// ============================================
// CREATE PAYMENT REQUEST
// ============================================

function setupModals() {
    document.getElementById('paymentRequestForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitPaymentRequest();
    });
}

function openPaymentRequestModal(id) {
    bootstrap.Modal.getInstance(document.getElementById('requisitionDetailModal'))?.hide();

    const modal = document.getElementById('paymentRequestModal');
    document.getElementById('paymentRequisitionId').value = id;
    document.getElementById('paymentRequestNotes').value = '';
    document.getElementById('paymentRequestJustification').value = '';
    document.getElementById('justificationGroup').style.display = 'none';
    document.getElementById('paymentRequestJustification').classList.remove('is-invalid');
    document.getElementById('paymentRequestSummary').innerHTML = `<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>`;

    fetch(`?page=api_finance_get_requisition_detail&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('paymentRequestSummary').innerHTML = fnErrorState(data.message);
                return;
            }
            const req = data.data.requisition;
            const dept = req.department || 'store';
            const monthYear = req.budget_month_year || new Date().toISOString().slice(0, 7);

            // Fetch a fresh budget status for this specific requisition's amount —
            // reuses the same authoritative endpoint the requisition list uses.
            fetch(`?page=api_finance_get_pending_requisitions&tab=to_review&search=${encodeURIComponent(req.requisition_number)}&limit=5`)
                .then(r2 => r2.json())
                .then(data2 => {
                    const match = (data2.data?.requisitions || []).find(r => r.id == id);
                    const bs = match ? match.budget_status : null;

                    let html = `
                        <div class="fn-doc-box mb-2">
                            <div class="fn-doc-title">📄 Supplier Invoice</div>
                            <div>Invoice #: <strong>${fnEscapeHtml(req.invoice?.invoice_number)}</strong></div>
                            <div>Invoice Date: <strong>${fnFormatDate(req.invoice?.invoice_date)}</strong> &nbsp; Due: <strong>${fnFormatDate(req.invoice?.due_date)}</strong></div>
                            <div>Total: <strong>${fnCurrency(req.invoice?.total)}</strong></div>
                        </div>
                        <div class="fn-doc-box mb-2">
                            <div class="fn-doc-title">📋 Original Requisition</div>
                            <div>${fnEscapeHtml(req.requisition_number)} — ${fnEscapeHtml(req.first_name)} ${fnEscapeHtml(req.last_name)}</div>
                        </div>
                    `;
                    if (bs) {
                        html += fnBudgetBox(bs);
                        document.getElementById('justificationGroup').style.display = bs.exceeded ? 'block' : 'none';
                    }
                    document.getElementById('paymentRequestSummary').innerHTML = html;
                });

            new bootstrap.Modal(modal).show();
        })
        .catch(() => {
            document.getElementById('paymentRequestSummary').innerHTML = fnErrorState();
        });
}

function submitPaymentRequest() {
    const requisitionId = document.getElementById('paymentRequisitionId').value;
    const notes = document.getElementById('paymentRequestNotes').value.trim();
    const justification = document.getElementById('paymentRequestJustification').value.trim();
    const justificationNeeded = document.getElementById('justificationGroup').style.display !== 'none';

    if (justificationNeeded && !justification) {
        document.getElementById('paymentRequestJustification').classList.add('is-invalid');
        return;
    }
    document.getElementById('paymentRequestJustification').classList.remove('is-invalid');

    const submitBtn = document.querySelector('#paymentRequestForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';

    fetch('?page=api_finance_create_payment_request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ requisition_id: parseInt(requisitionId), notes, justification })
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send"></i> Submit for Approval';

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('paymentRequestModal'))?.hide();
            Swal.fire({
                icon: 'success',
                title: 'Payment Request Created!',
                html: data.data.budget_exceeded
                    ? 'Submitted with a budget-exceeded justification. Awaiting Finance Head approval.'
                    : 'Submitted to the Finance Head for approval.',
                timer: 2500,
                showConfirmButton: false
            });
            loadTab(currentTab, currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to create payment request.' });
        }
    })
    .catch(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send"></i> Submit for Approval';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}
