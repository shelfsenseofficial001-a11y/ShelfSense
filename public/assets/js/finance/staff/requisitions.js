// ============================================
// FINANCE STAFF — REQUISITION BUDGET CHECK
// ============================================

let fsReqPage = 1;
let fsRejectTargetId = null;

document.addEventListener('DOMContentLoaded', function () {
    loadPending();
    document.getElementById('confirmRejectBtn').addEventListener('click', confirmReject);
});

async function loadPending() {
    const tbody = document.getElementById('pendingTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson(`?page=api_fs_list_pos_pending_budget_check&page_num=${fsReqPage}&limit=10`);
        const rows = data.purchase_orders || [];
        tbody.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td>${prEscapeHtml(r.po_number)} <small class="text-muted">(${prEscapeHtml(r.requisition_number)})</small></td>
                <td>${prEscapeHtml(r.supplier_name)}</td>
                <td>${prEscapeHtml(r.department_name)}</td>
                <td>${prCurrency(r.total)}</td>
                <td>${prCurrency(r.budget_status.available)} ${r.budget_status.exceeded ? '<span class="badge bg-danger">Exceeded</span>' : ''}</td>
                <td>${prStatusBadge(r.status)}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="passBudgetCheck(${r.id}, this)"><i class="bi bi-check"></i> Pass</button>
                    <button class="btn btn-sm btn-danger" onclick="openReject(${r.id})"><i class="bi bi-x"></i> Reject</button>
                </td>
            </tr>
        `).join('') : prEmptyRow(7, 'No Purchase Orders pending a budget check.');
        prRenderPagination(document.getElementById('pendingPagination'), document.getElementById('pendingPageInfo'), data.pagination, 'purchase orders', (p) => { fsReqPage = p; loadPending(); });
    } catch (e) {
        tbody.innerHTML = prErrorRow(7, e.message);
    }
}

async function passBudgetCheck(id, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_fs_check_po_budget', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: id, action: 'pass' }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Passed', data.message, 'success');
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
        const res = await fetch('?page=api_fs_check_po_budget', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: fsRejectTargetId, action: 'reject', reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide();
        Swal.fire('Rejected', data.message, 'success');
        loadPending();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}
