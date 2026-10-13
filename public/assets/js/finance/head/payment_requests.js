// ============================================
// FINANCE HEAD — REQUISITION APPROVAL & PO PAYMENT APPROVAL
// ============================================

let fhApproveTargetId = null;
let fhApproveExceeded = false;
let fhRejectReqTargetId = null;
let fhRejectPoPaymentTargetId = null;
let fhPayTargetId = null;

document.addEventListener('DOMContentLoaded', function () {
    loadPendingRequisitions();
    loadPendingPoPayments();
    document.getElementById('confirmApproveReqBtn').addEventListener('click', confirmApproveReq);
    document.getElementById('confirmRejectReqBtn').addEventListener('click', confirmRejectReq);
    document.getElementById('confirmRejectPoPaymentBtn').addEventListener('click', confirmRejectPoPayment);
    document.getElementById('payMethodSelect')?.addEventListener('change', function () {
        document.getElementById('payReferenceWrap').classList.toggle('d-none', this.value === 'paymongo_simulated');
        document.getElementById('paySimulatedNotice').classList.toggle('d-none', this.value !== 'paymongo_simulated');
        // Switching to the simulated method clears any reference error, since
        // that field no longer applies.
        validateApprovePoPayment();
    });
    document.getElementById('confirmApprovePoPaymentBtn')?.addEventListener('click', confirmApprovePoPayment);

    // Live validation: show the problem as the Head types, and keep the
    // confirm button disabled until the field is actually usable.
    fnWireLiveValidation(['approveReqJustification'], validateApproveReq);
    fnWireLiveValidation(['rejectReqReason'], validateRejectReq);
    fnWireLiveValidation(['rejectPoPaymentReason'], validateRejectPoPayment);
    fnWireLiveValidation(['payReferenceInput'], validateApprovePoPayment);
});

async function loadPendingRequisitions() {
    const tbody = document.getElementById('fhReqTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fh_list_requisitions_pending&limit=20');
        const rows = data.requisitions || [];
        prSetTabBadge('tabPendingPos', rows.length);
        tbody.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td>${prEscapeHtml(r.requisition_number)}</td>
                <td>${prEscapeHtml(r.supplier_name)}</td>
                <td>${prEscapeHtml(r.department_name)}</td>
                <td>${prCurrency(r.subtotal)}</td>
                <td>${prCurrency(r.budget_status.available)} ${r.budget_status.exceeded ? '<span class="badge bg-danger">Exceeded</span>' : ''}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openRequisitionDetail(${r.id})" title="View details"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-success" onclick="openApprove(${r.id}, '${prEscapeHtml(r.requisition_number)}', ${r.budget_status.exceeded}, ${r.budget_status.shortfall})"><i class="bi bi-check"></i> Approve</button>
                    <button class="btn btn-sm btn-warning" onclick="openRejectReq(${r.id})"><i class="bi bi-arrow-counterclockwise"></i> Request Revision</button>
                </td>
            </tr>
        `).join('') : prEmptyRow(6, 'No requisitions pending approval.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(6, e.message);
    }
}

// The justification only applies when the requisition is over budget --
// when it isn't, the field is hidden and must not block approval.
function validateApproveReq() {
    const input = document.getElementById('approveReqJustification');
    const error = fhApproveExceeded
        ? fnValidateText(input.value, { required: true, min: 10, max: 500, label: 'Justification' })
        : '';
    fnSetFieldError(input, 'approveReqJustificationError', input.dataset.touched ? error : '');
    const btn = document.getElementById('confirmApproveReqBtn');
    if (btn) btn.disabled = !!error;
    return !error;
}

function openApprove(id, number, exceeded, shortfall) {
    fhApproveTargetId = id;
    fhApproveExceeded = exceeded;
    document.getElementById('approveReqSummary').textContent = exceeded
        ? `Requisition ${number} exceeds available budget by ${prCurrency(shortfall)}.`
        : `Approve requisition ${number}? This will reserve the budget and create the Purchase Order.`;
    document.getElementById('approveReqJustificationWrap').classList.toggle('d-none', !exceeded);
    const input = document.getElementById('approveReqJustification');
    input.value = '';
    delete input.dataset.touched;
    fnSetFieldError(input, 'approveReqJustificationError', '');
    validateApproveReq();
    new bootstrap.Modal(document.getElementById('approveReqModal')).show();
}

async function confirmApproveReq() {
    const input = document.getElementById('approveReqJustification');
    input.dataset.touched = '1';
    if (!validateApproveReq()) { input.focus(); return; }
    const justification = input.value.trim();
    const unlock = prLockButton(document.getElementById('confirmApproveReqBtn'));
    try {
        const res = await fetch('?page=api_fh_requisition_approve', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ requisition_id: fhApproveTargetId, justification }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('approveReqModal'))?.hide();
        Swal.fire('Approved', data.message, 'success');
        loadPendingRequisitions();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

// A revision request goes back to the Store Manager as actionable
// feedback, so a bare "no" is not enough -- require a real sentence.
function validateRejectReq() {
    const input = document.getElementById('rejectReqReason');
    const error = fnValidateText(input.value, { required: true, min: 10, max: 500, label: 'Reason' });
    fnSetFieldError(input, 'rejectReqReasonError', input.dataset.touched ? error : '');
    const btn = document.getElementById('confirmRejectReqBtn');
    if (btn) btn.disabled = !!error;
    return !error;
}

function openRejectReq(id) {
    fhRejectReqTargetId = id;
    const input = document.getElementById('rejectReqReason');
    input.value = '';
    delete input.dataset.touched;
    fnSetFieldError(input, 'rejectReqReasonError', '');
    validateRejectReq();
    new bootstrap.Modal(document.getElementById('rejectReqModal')).show();
}

async function confirmRejectReq() {
    const input = document.getElementById('rejectReqReason');
    input.dataset.touched = '1';
    if (!validateRejectReq()) { input.focus(); return; }
    const reason = input.value.trim();
    const unlock = prLockButton(document.getElementById('confirmRejectReqBtn'));
    try {
        const res = await fetch('?page=api_fh_requisition_request_revision', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ requisition_id: fhRejectReqTargetId, reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('rejectReqModal'))?.hide();
        Swal.fire('Sent', data.message, 'success');
        loadPendingRequisitions();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function loadPendingPoPayments() {
    const tbody = document.getElementById('fhPoPaymentTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fh_list_pending_po_payments&status=pending&limit=20');
        const rows = data.payment_requests || [];
        prSetTabBadge('tabPendingPoPayments', rows.length);
        tbody.innerHTML = rows.length ? rows.map(pr => `
            <tr>
                <td>${prEscapeHtml(pr.po_number)}</td>
                <td>${prEscapeHtml(pr.supplier_name)}</td>
                <td>${prCurrency(pr.amount)}</td>
                <td>${prEscapeHtml(pr.requested_by_name || '')}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openPoDetail(${pr.po_id})" title="View details"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-success" onclick="openApprovePoPayment(${pr.id}, ${pr.amount}, '${prEscapeHtml(pr.po_number)}')"><i class="bi bi-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="openRejectPoPayment(${pr.id})"><i class="bi bi-x"></i> Reject</button>
                </td>
            </tr>
        `).join('') : prEmptyRow(5, 'No payment requests pending approval.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}

// The reference stays optional (it's how a real payout is traced, but not
// every method produces one). When it IS filled in, keep it to the shape a
// bank/cheque reference actually takes rather than accepting free text.
function validateApprovePoPayment() {
    const input = document.getElementById('payReferenceInput');
    const method = document.getElementById('payMethodSelect').value;
    const error = method === 'paymongo_simulated'
        ? ''
        : fnValidateText(input.value, {
            required: false,
            max: 50,
            pattern: /^[A-Za-z0-9/\-#. ]+$/,
            patternMessage: 'Letters, numbers and - / # . only.',
        });
    fnSetFieldError(input, 'payReferenceInputError', input.dataset.touched ? error : '');
    const btn = document.getElementById('confirmApprovePoPaymentBtn');
    if (btn) btn.disabled = !!error;
    return !error;
}

function openApprovePoPayment(id, amount, poNumber) {
    fhPayTargetId = id;
    document.getElementById('payApproveSummary').textContent = `Pay ${prCurrency(amount)} to the supplier for PO ${poNumber}.`;
    document.getElementById('payMethodSelect').value = 'bank_transfer';
    document.getElementById('payReferenceWrap').classList.remove('d-none');
    document.getElementById('paySimulatedNotice').classList.add('d-none');
    const input = document.getElementById('payReferenceInput');
    input.value = '';
    delete input.dataset.touched;
    fnSetFieldError(input, 'payReferenceInputError', '');
    validateApprovePoPayment();
    new bootstrap.Modal(document.getElementById('approvePoPaymentModal')).show();
}

async function confirmApprovePoPayment() {
    const refInput = document.getElementById('payReferenceInput');
    refInput.dataset.touched = '1';
    if (!validateApprovePoPayment()) { refInput.focus(); return; }
    const method = document.getElementById('payMethodSelect').value;
    const referenceNumber = refInput.value.trim();
    const btn = document.getElementById('confirmApprovePoPaymentBtn');
    const originalBtnHtml = btn.innerHTML;
    const unlock = prLockButton(btn);

    // The simulated disbursement gets its own brief "processing" beat so
    // it reads as a real payout step, not just an instant DB write --
    // makes it obvious to whoever's watching that something happened,
    // even though the label already says it's simulated.
    if (method === 'paymongo_simulated') {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Simulating PayMongo disbursement...';
        await new Promise(r => setTimeout(r, 1200));
    }

    try {
        const res = await fetch('?page=api_fh_approve_po_payment', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                payment_request_id: fhPayTargetId,
                action: 'approve',
                method: method,
                reference_number: method === 'paymongo_simulated' ? '' : referenceNumber
            }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('approvePoPaymentModal'))?.hide();
        showPaymentReceipt(data.receipt);
        loadPendingPoPayments();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        btn.innerHTML = originalBtnHtml;
        unlock();
    }
}

const PO_PAYMENT_METHOD_LABELS = {
    bank_transfer: 'Bank Transfer',
    check: 'Check',
    cash: 'Cash',
    paymongo_simulated: 'PayMongo (Simulated)',
    other: 'Other',
};

function showPaymentReceipt(receipt) {
    if (!receipt) return;
    const isSimulated = receipt.method === 'paymongo_simulated';
    document.getElementById('paymentReceiptBody').innerHTML = `
        ${isSimulated ? `<div class="alert alert-warning small"><strong>Demo notice:</strong> no real funds were transferred -- this simulates the payout.</div>` : ''}
        <table class="table table-sm mb-0">
            <tbody>
                <tr><th class="text-muted" style="width:40%">Purchase Order</th><td>${prEscapeHtml(receipt.po_number)}</td></tr>
                <tr><th class="text-muted">Supplier</th><td>${prEscapeHtml(receipt.supplier_name)}</td></tr>
                <tr><th class="text-muted">Amount</th><td class="fw-bold">${prCurrency(receipt.amount)}</td></tr>
                <tr><th class="text-muted">Method</th><td>${prEscapeHtml(PO_PAYMENT_METHOD_LABELS[receipt.method] || receipt.method)}</td></tr>
                <tr><th class="text-muted">Reference</th><td><code>${prEscapeHtml(receipt.reference_number)}</code></td></tr>
                <tr><th class="text-muted">Approved By</th><td>${prEscapeHtml(receipt.approved_by_name)}</td></tr>
                <tr><th class="text-muted">Date</th><td>${prFormatDateTime(receipt.paid_at)}</td></tr>
            </tbody>
        </table>
    `;
    new bootstrap.Modal(document.getElementById('paymentReceiptModal')).show();
}

// Rejecting a payment request sends the reason back to the Finance Staff
// who raised it, so it needs to say something actionable.
function validateRejectPoPayment() {
    const input = document.getElementById('rejectPoPaymentReason');
    const error = fnValidateText(input.value, { required: true, min: 10, max: 500, label: 'Reason' });
    fnSetFieldError(input, 'rejectPoPaymentReasonError', input.dataset.touched ? error : '');
    const btn = document.getElementById('confirmRejectPoPaymentBtn');
    if (btn) btn.disabled = !!error;
    return !error;
}

function openRejectPoPayment(id) {
    fhRejectPoPaymentTargetId = id;
    const input = document.getElementById('rejectPoPaymentReason');
    input.value = '';
    delete input.dataset.touched;
    fnSetFieldError(input, 'rejectPoPaymentReasonError', '');
    validateRejectPoPayment();
    new bootstrap.Modal(document.getElementById('rejectPoPaymentModal')).show();
}

async function confirmRejectPoPayment() {
    const input = document.getElementById('rejectPoPaymentReason');
    input.dataset.touched = '1';
    if (!validateRejectPoPayment()) { input.focus(); return; }
    const reason = input.value.trim();
    const unlock = prLockButton(document.getElementById('confirmRejectPoPaymentBtn'));
    try {
        const res = await fetch('?page=api_fh_approve_po_payment', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ payment_request_id: fhRejectPoPaymentTargetId, action: 'reject', reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('rejectPoPaymentModal'))?.hide();
        Swal.fire('Rejected', data.message, 'success');
        loadPendingPoPayments();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function openRequisitionDetail(reqId) {
    const modal = new bootstrap.Modal(document.getElementById('fhReqDetailModal'));
    document.getElementById('fhReqDetailBody').innerHTML = 'Loading...';
    modal.show();
    try {
        const r = await prFetchJson(`?page=api_get_requisition&id=${reqId}`);
        const itemsHtml = r.items.map(i => `
            <tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.estimated_unit_price)}</td><td>${prCurrency(i.estimated_total)}</td></tr>
        `).join('');
        const budgetHtml = r.budget_status ? `
            <p><strong>Budget Available:</strong> ${prCurrency(r.budget_status.available)} ${r.budget_status.exceeded ? '<span class="badge bg-danger">Exceeded</span>' : ''}</p>
        ` : '';
        document.getElementById('fhReqDetailBody').innerHTML = `
            <p><strong>Supplier:</strong> ${prEscapeHtml(r.supplier_name)} &nbsp; <strong>Department:</strong> ${prEscapeHtml(r.department_name)} &nbsp; <strong>Status:</strong> ${prStatusBadge(r.status)}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(r.subtotal)}</p>
            ${budgetHtml}
            ${r.purchase_order ? `<p><strong>Purchase Order:</strong> ${prEscapeHtml(r.purchase_order.po_number)} (${prStatusBadge(r.purchase_order.status)})</p>` : ''}
            <hr><h6>History</h6>
            ${prRenderTimeline(r.events)}
        `;
    } catch (e) {
        document.getElementById('fhReqDetailBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

async function openPoDetail(poId) {
    const modal = new bootstrap.Modal(document.getElementById('fhPoDetailModal'));
    document.getElementById('fhPoDetailBody').innerHTML = 'Loading...';
    modal.show();
    try {
        const po = await prFetchJson(`?page=api_get_purchase_order&id=${poId}`);
        const itemsHtml = po.items.map(i => `
            <tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.unit_price)}</td><td>${prCurrency(i.total)}</td><td>${i.received_quantity_total ?? 0}</td></tr>
        `).join('');
        const invoicesHtml = (po.invoices || []).length ? `
            <hr><h6>Invoices</h6>
            <table class="table table-sm"><thead><tr><th>Invoice #</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
            ${po.invoices.map(inv => `<tr><td>${prEscapeHtml(inv.invoice_number)}</td><td>${prCurrency(inv.total)}</td><td>${prStatusBadge(inv.match_status)}</td><td><button class="btn btn-sm btn-outline-secondary" onclick="prOpenMatchDetail(${inv.id})"><i class="bi bi-arrow-left-right"></i> View Match</button></td></tr>`).join('')}
            </tbody></table>
        ` : '';
        document.getElementById('fhPoDetailBody').innerHTML = `
            <p><strong>Supplier:</strong> ${prEscapeHtml(po.supplier_name)} &nbsp; <strong>Status:</strong> ${prStatusBadge(po.status)}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Received</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(po.total)}</p>
            ${invoicesHtml}
            <hr><h6>History</h6>
            ${prRenderTimeline(po.events)}
        `;
    } catch (e) {
        document.getElementById('fhPoDetailBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}
