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
    });
    document.getElementById('confirmApprovePoPaymentBtn')?.addEventListener('click', confirmApprovePoPayment);
});

async function loadPendingRequisitions() {
    const tbody = document.getElementById('fhReqTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fh_list_pending_pos&limit=20');
        const rows = data.purchase_orders || [];
        prSetTabBadge('tabPendingPos', rows.length);
        tbody.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td>${prEscapeHtml(r.po_number)} <small class="text-muted">(${prEscapeHtml(r.requisition_number)})</small></td>
                <td>${prEscapeHtml(r.supplier_name)}</td>
                <td>${prEscapeHtml(r.department_name)}</td>
                <td>${prCurrency(r.total)}</td>
                <td>${prCurrency(r.budget_status.available)} ${r.budget_status.exceeded ? '<span class="badge bg-danger">Exceeded</span>' : ''}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="openApprove(${r.id}, '${prEscapeHtml(r.po_number)}', ${r.budget_status.exceeded}, ${r.budget_status.shortfall})"><i class="bi bi-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="openRejectReq(${r.id})"><i class="bi bi-x"></i> Reject</button>
                </td>
            </tr>
        `).join('') : prEmptyRow(6, 'No Purchase Orders pending approval.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(6, e.message);
    }
}

function openApprove(id, number, exceeded, shortfall) {
    fhApproveTargetId = id;
    fhApproveExceeded = exceeded;
    document.getElementById('approveReqSummary').textContent = exceeded
        ? `PO ${number} exceeds available budget by ${prCurrency(shortfall)}.`
        : `Approve PO ${number}? This will reserve the budget and forward it to dispatch.`;
    document.getElementById('approveReqJustificationWrap').classList.toggle('d-none', !exceeded);
    document.getElementById('approveReqJustification').value = '';
    new bootstrap.Modal(document.getElementById('approveReqModal')).show();
}

async function confirmApproveReq() {
    const justification = document.getElementById('approveReqJustification').value.trim();
    if (fhApproveExceeded && !justification) {
        Swal.fire('Justification required', 'This request exceeds budget.', 'warning');
        return;
    }
    const unlock = prLockButton(document.getElementById('confirmApproveReqBtn'));
    try {
        const res = await fetch('?page=api_fh_approve_po', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: fhApproveTargetId, action: 'approve', justification }),
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

function openRejectReq(id) {
    fhRejectReqTargetId = id;
    document.getElementById('rejectReqReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectReqModal')).show();
}

async function confirmRejectReq() {
    const reason = document.getElementById('rejectReqReason').value.trim();
    if (!reason) { Swal.fire('Reason required', '', 'warning'); return; }
    const unlock = prLockButton(document.getElementById('confirmRejectReqBtn'));
    try {
        const res = await fetch('?page=api_fh_approve_po', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: fhRejectReqTargetId, action: 'reject', reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('rejectReqModal'))?.hide();
        Swal.fire('Rejected', data.message, 'success');
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
                    <button class="btn btn-sm btn-success" onclick="openApprovePoPayment(${pr.id}, ${pr.amount}, '${prEscapeHtml(pr.po_number)}')"><i class="bi bi-check"></i> Approve</button>
                    <button class="btn btn-sm btn-danger" onclick="openRejectPoPayment(${pr.id})"><i class="bi bi-x"></i> Reject</button>
                </td>
            </tr>
        `).join('') : prEmptyRow(5, 'No payment requests pending approval.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}

function openApprovePoPayment(id, amount, poNumber) {
    fhPayTargetId = id;
    document.getElementById('payApproveSummary').textContent = `Pay ${prCurrency(amount)} to the supplier for PO ${poNumber}.`;
    document.getElementById('payMethodSelect').value = 'bank_transfer';
    document.getElementById('payReferenceWrap').classList.remove('d-none');
    document.getElementById('paySimulatedNotice').classList.add('d-none');
    document.getElementById('payReferenceInput').value = '';
    new bootstrap.Modal(document.getElementById('approvePoPaymentModal')).show();
}

async function confirmApprovePoPayment() {
    const method = document.getElementById('payMethodSelect').value;
    const referenceNumber = document.getElementById('payReferenceInput').value.trim();
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
        Swal.fire('Payment sent', data.message, 'success');
        loadPendingPoPayments();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        btn.innerHTML = originalBtnHtml;
        unlock();
    }
}

function openRejectPoPayment(id) {
    fhRejectPoPaymentTargetId = id;
    document.getElementById('rejectPoPaymentReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectPoPaymentModal')).show();
}

async function confirmRejectPoPayment() {
    const reason = document.getElementById('rejectPoPaymentReason').value.trim();
    if (!reason) { Swal.fire('Reason required', '', 'warning'); return; }
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
