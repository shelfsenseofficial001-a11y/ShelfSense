// ============================================
// FINANCE STAFF — PURCHASE ORDERS & PAYMENTS (pay-before-delivery)
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    loadDispatch();
    loadRequestPayment();
    loadHolds();
});

async function loadDispatch() {
    const tbody = document.getElementById('dispatchTableBody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fs_list_pos_pending_dispatch&limit=20');
        const rows = data.purchase_orders || [];
        prSetTabBadge('tabDispatch', rows.length);
        tbody.innerHTML = rows.length ? rows.map(po => `
            <tr>
                <td>${prEscapeHtml(po.po_number)}</td>
                <td>${prEscapeHtml(po.supplier_name)}</td>
                <td>${prCurrency(po.total)}</td>
                <td><button class="btn btn-sm btn-yellow-primary" onclick="dispatchPo(${po.id}, this)"><i class="bi bi-send"></i> Dispatch</button></td>
            </tr>
        `).join('') : prEmptyRow(4, 'No purchase orders pending dispatch.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(4, e.message);
    }
}

async function dispatchPo(poId, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_fs_dispatch_po', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: poId }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Dispatched', data.message, 'success');
        loadDispatch();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function loadRequestPayment() {
    const tbody = document.getElementById('requestPaymentTableBody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fs_list_po_payment_eligible');
        const rows = data.purchase_orders || [];
        prSetTabBadge('tabRequestPayment', rows.length);
        tbody.innerHTML = rows.length ? rows.map(po => `
            <tr>
                <td>${prEscapeHtml(po.po_number)}</td>
                <td>${prEscapeHtml(po.supplier_name)}</td>
                <td>${prCurrency(po.total)}</td>
                <td><button class="btn btn-sm btn-yellow-primary" onclick="requestPayment(${po.id}, this)"><i class="bi bi-cash-coin"></i> Request Payment</button></td>
            </tr>
        `).join('') : prEmptyRow(4, 'No confirmed purchase orders awaiting a payment request.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(4, e.message);
    }
}

async function requestPayment(poId, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_fs_request_po_payment', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: poId }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Requested', data.message, 'success');
        loadRequestPayment();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function loadHolds() {
    const tbody = document.getElementById('holdsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fs_list_invoices&scope=holds&limit=20');
        const rows = data.invoices || [];
        prSetTabBadge('tabHolds', rows.length);
        tbody.innerHTML = rows.length ? rows.map(inv => `
            <tr>
                <td>${prEscapeHtml(inv.invoice_number)}</td>
                <td>${prEscapeHtml(inv.po_number)}</td>
                <td>${prEscapeHtml(inv.supplier_name)}</td>
                <td>${prCurrency(inv.total)}</td>
                <td>${prStatusBadge(inv.match_status)}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="openVariance(${inv.id})"><i class="bi bi-tools"></i> Resolve</button></td>
            </tr>
        `).join('') : prEmptyRow(6, 'No invoices on hold.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(6, e.message);
    }
}

async function openVariance(invoiceId) {
    const modal = new bootstrap.Modal(document.getElementById('varianceModal'));
    document.getElementById('varianceModalBody').innerHTML = 'Loading...';
    modal.show();
    try {
        const inv = await prFetchJson(`?page=api_get_invoice&id=${invoiceId}`);
        const rowsHtml = inv.items.map(i => `
            <tr data-po-item-id="${i.po_item_id}">
                <td>${prEscapeHtml(i.store_product_name)}</td>
                <td>${i.po_quantity} @ ${prCurrency(i.po_unit_price)}</td>
                <td>${i.billed_quantity} @ ${prCurrency(i.billed_unit_price)}</td>
                <td><span class="badge ${i.variance_flag === 'ok' ? 'bg-success' : 'bg-danger'}">${prEscapeHtml(i.variance_flag)}</span></td>
                <td>
                    <input type="number" class="form-control form-control-sm new-price" style="width:110px" step="0.01" value="${i.po_unit_price}">
                    <button class="btn btn-sm btn-outline-secondary mt-1" onclick="adjustPoPrice(${invoiceId}, ${i.po_item_id}, this)">Correct PO Price</button>
                </td>
            </tr>
        `).join('');
        document.getElementById('varianceModalBody').innerHTML = `
            <table class="table table-sm"><thead><tr><th>Product</th><th>PO Qty/Price</th><th>Billed Qty/Price</th><th>Flag</th><th>Adjust</th></tr></thead><tbody>${rowsHtml}</tbody></table>
            <hr>
            <label class="form-label">Override Justification (required to mark reconciled despite the hold)</label>
            <textarea id="overrideNote" class="form-control" rows="2"></textarea>
            <button class="btn btn-warning btn-sm mt-2" onclick="overrideHold(${invoiceId}, this)"><i class="bi bi-shield-check"></i> Override & Reconcile</button>
        `;
    } catch (e) {
        document.getElementById('varianceModalBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

async function adjustPoPrice(invoiceId, poItemId, btn) {
    const newPrice = parseFloat(btn.closest('td').querySelector('.new-price').value);
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_fs_resolve_variance', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoice_id: invoiceId, action: 'adjust_po_price', po_item_id: poItemId, new_unit_price: newPrice }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Updated', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('varianceModal'))?.hide();
        loadHolds();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function overrideHold(invoiceId, btn) {
    const note = document.getElementById('overrideNote').value.trim();
    if (!note) {
        Swal.fire('Justification required', 'Explain why this hold is being overridden.', 'warning');
        return;
    }
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_fs_resolve_variance', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoice_id: invoiceId, action: 'override', note }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Reconciled', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('varianceModal'))?.hide();
        loadHolds();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}
