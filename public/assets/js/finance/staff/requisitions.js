// ============================================
// FINANCE STAFF — REQUISITION BUDGET CHECK
// ============================================

let fsAllRequisitions = [];
let fsSelectedReqId = null;
let fsRejectTargetId = null;

document.addEventListener('DOMContentLoaded', function () {
    loadPending();
    document.getElementById('confirmRejectBtn').addEventListener('click', confirmReject);
    document.getElementById('pendingSearchInput').addEventListener('input', renderPendingList);
});

async function loadPending() {
    const list = document.getElementById('pendingListGroup');
    list.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
    try {
        const data = await prFetchJson('?page=api_fs_list_requisitions_pending_budget_check&limit=100');
        fsAllRequisitions = data.requisitions || [];
        renderPendingList();
    } catch (e) {
        list.innerHTML = `<div class="alert alert-danger m-0">${prEscapeHtml(e.message)}</div>`;
    }
}

function renderPendingList() {
    const list = document.getElementById('pendingListGroup');
    const query = document.getElementById('pendingSearchInput').value.trim().toLowerCase();
    const rows = query
        ? fsAllRequisitions.filter(r => [r.requisition_number, r.supplier_name, r.department_name].some(v => (v || '').toLowerCase().includes(query)))
        : fsAllRequisitions;

    if (!rows.length) {
        list.innerHTML = `<div class="text-center text-muted py-4">${fsAllRequisitions.length ? 'No matches.' : 'No requisitions pending a budget check.'}</div>`;
        return;
    }

    list.innerHTML = rows.map(r => `
        <button type="button" class="list-group-item list-group-item-action ${r.id === fsSelectedReqId ? 'active' : ''}" onclick="selectRequisition(${r.id})">
            <div class="d-flex justify-content-between align-items-start">
                <strong>${prEscapeHtml(r.requisition_number)}</strong>
                ${r.budget_status.exceeded ? '<span class="badge bg-danger">Exceeded</span>' : ''}
            </div>
            <div class="small ${r.id === fsSelectedReqId ? '' : 'text-muted'}">${prEscapeHtml(r.supplier_name)} &middot; ${prEscapeHtml(r.department_name)}</div>
            <div class="small fw-semibold">${prCurrency(r.subtotal)}</div>
        </button>
    `).join('');
}

function selectRequisition(id) {
    fsSelectedReqId = id;
    renderPendingList();
    openRequisitionDetail(id);
}

async function passBudgetCheck(id, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_fs_requisition_check_budget', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ requisition_id: id, action: 'pass' }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Passed', data.message, 'success');
        clearDetail();
        loadPending();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

function openReject(id) {
    fsRejectTargetId = id;
    document.getElementById('rejectReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

async function confirmReject() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        Swal.fire('Reason required', 'Please provide a rejection reason.', 'warning');
        return;
    }
    const unlock = prLockButton(document.getElementById('confirmRejectBtn'));
    try {
        const res = await fetch('?page=api_fs_requisition_check_budget', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ requisition_id: fsRejectTargetId, action: 'reject', reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide();
        Swal.fire('Rejected', data.message, 'success');
        clearDetail();
        loadPending();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

function clearDetail() {
    fsSelectedReqId = null;
    document.getElementById('fsReqDetailBody').innerHTML = `
        <div class="text-center text-muted py-5">
            <i class="bi bi-arrow-left-circle fs-1 d-block mb-2"></i>
            Select a requisition from the list to see its details.
        </div>
    `;
}

async function openRequisitionDetail(reqId) {
    const body = document.getElementById('fsReqDetailBody');
    body.innerHTML = '<div class="text-center text-muted py-5">Loading...</div>';
    try {
        const r = await prFetchJson(`?page=api_get_requisition&id=${reqId}`);
        const itemsHtml = r.items.map(i => `
            <tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.estimated_unit_price)}</td><td>${prCurrency(i.estimated_total)}</td></tr>
        `).join('');
        const budgetHtml = r.budget_status ? `
            <p><strong>Budget Available:</strong> ${prCurrency(r.budget_status.available)} ${r.budget_status.exceeded ? '<span class="badge bg-danger">Exceeded</span>' : ''}</p>
        ` : '';
        body.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="mb-0">${prEscapeHtml(r.requisition_number)}</h5>
                ${prStatusBadge(r.status)}
            </div>
            <p><strong>Supplier:</strong> ${prEscapeHtml(r.supplier_name)} &nbsp; <strong>Department:</strong> ${prEscapeHtml(r.department_name)} &nbsp; <strong>Requested By:</strong> ${prEscapeHtml(r.requested_by_name || '')}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(r.subtotal)}</p>
            ${budgetHtml}
            ${r.purchase_order ? `<p><strong>Purchase Order:</strong> ${prEscapeHtml(r.purchase_order.po_number)} (${prStatusBadge(r.purchase_order.status)})</p>` : ''}
            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-sm btn-success" onclick="passBudgetCheck(${r.id}, this)"><i class="bi bi-check"></i> Pass</button>
                <button class="btn btn-sm btn-danger" onclick="openReject(${r.id})"><i class="bi bi-x"></i> Reject</button>
            </div>
            <hr><h6>History</h6>
            ${prRenderTimeline(r.events)}
        `;
    } catch (e) {
        body.innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}
