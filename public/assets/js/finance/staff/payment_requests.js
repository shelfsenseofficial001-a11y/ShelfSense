// ============================================
// FINANCE STAFF — PURCHASE ORDERS & PAYMENTS (pay-before-delivery)
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    loadDispatch();
    loadAwaitingSupplier();
    loadRequestPayment();
    loadHolds();
    loadMyPayments();
});

const PAYMENT_METHOD_LABELS = {
    bank_transfer: 'Bank Transfer',
    check: 'Check',
    cash: 'Cash',
    paymongo_simulated: 'PayMongo (Simulated)',
    other: 'Other'
};

async function loadMyPayments() {
    const tbody = document.getElementById('myPaymentsTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fs_list_my_po_payments&limit=50');
        const rows = data.payment_requests || [];
        tbody.innerHTML = rows.length ? rows.map(pr => {
            let methodCell = '<span class="text-muted">&mdash;</span>';
            if (pr.status === 'approved' && pr.payment_method) {
                const label = PAYMENT_METHOD_LABELS[pr.payment_method] || pr.payment_method;
                methodCell = `${prEscapeHtml(label)}<br><small class="text-muted">${prEscapeHtml(pr.payment_reference || '')}</small>`;
            } else if (pr.status === 'rejected' && pr.rejection_reason) {
                methodCell = `<small class="text-danger">${prEscapeHtml(pr.rejection_reason)}</small>`;
            }
            return `
            <tr>
                <td>${prEscapeHtml(pr.po_number)}</td>
                <td>${prEscapeHtml(pr.supplier_name)}</td>
                <td>${prCurrency(pr.amount)}</td>
                <td>${prStatusBadge(pr.status)}</td>
                <td>${methodCell}</td>
                <td>${prEscapeHtml(pr.approved_by_name || '')}</td>
                <td><button class="btn btn-sm btn-outline-secondary" onclick="openPoDetail(${pr.po_id})" title="View details"><i class="bi bi-eye"></i></button></td>
            </tr>
        `;
        }).join('') : prEmptyRow(7, "You haven't submitted any payment requests yet.");
    } catch (e) {
        tbody.innerHTML = prErrorRow(7, e.message);
    }
}

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
                <td>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openPoDetail(${po.id})" title="View details"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-yellow-primary" onclick="confirmDispatchPo(${po.id}, '${prEscapeHtml(po.po_number)}', this)"><i class="bi bi-send"></i> Dispatch</button>
                </td>
            </tr>
        `).join('') : prEmptyRow(4, 'No purchase orders pending dispatch.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(4, e.message);
    }
}

async function loadAwaitingSupplier() {
    const tbody = document.getElementById('awaitingSupplierTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_fs_list_pos_awaiting_supplier&limit=20');
        const rows = data.purchase_orders || [];
        prSetTabBadge('tabAwaitingSupplier', rows.length);
        tbody.innerHTML = rows.length ? rows.map(po => `
            <tr>
                <td>${prEscapeHtml(po.po_number)}</td>
                <td>${prEscapeHtml(po.supplier_name)}</td>
                <td>${prCurrency(po.total)}</td>
                <td>${prStatusBadge(po.status)}</td>
                <td><button class="btn btn-sm btn-outline-secondary" onclick="openPoDetail(${po.id})" title="View details"><i class="bi bi-eye"></i></button></td>
            </tr>
        `).join('') : prEmptyRow(5, 'No purchase orders awaiting the supplier right now.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}

function confirmDispatchPo(poId, poNumber, btn) {
    Swal.fire({
        icon: 'question',
        title: `Dispatch ${poNumber}?`,
        html: `This emails the purchase order to the supplier and moves it to Pending Confirmation. This can't be undone from here.`,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-send"></i> Yes, dispatch it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d4a017',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        dispatchPo(poId, btn);
    });
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
                <td>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openPoDetail(${po.id})" title="View details"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-yellow-primary" onclick="requestPayment(${po.id}, this)"><i class="bi bi-cash-coin"></i> Request Payment</button>
                </td>
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
        loadMyPayments();
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
                <td>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openPoDetail(${inv.po_id})" title="View PO details"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openVariance(${inv.id})"><i class="bi bi-tools"></i> Resolve</button>
                </td>
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
                <td>${i.good_received_quantity}</td>
                <td>${i.billed_quantity} @ ${prCurrency(i.billed_unit_price)}</td>
                <td><span class="badge ${i.variance_flag === 'ok' ? 'bg-success' : 'bg-danger'}">${prEscapeHtml((i.variance_flag || '').replace(/_/g, ' '))}</span></td>
                <td>
                    <input type="number" class="form-control form-control-sm new-price" style="width:110px" step="0.01" value="${i.po_unit_price}">
                    <button class="btn btn-sm btn-outline-secondary mt-1" onclick="adjustPoPrice(${invoiceId}, ${i.po_item_id}, this)">Correct PO Price</button>
                </td>
            </tr>
        `).join('');
        document.getElementById('varianceModalBody').innerHTML = `
            <div class="alert alert-warning d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div>
                    <div class="fw-bold">Variance found</div>
                    <div class="small mb-0">Invoice <strong>${prEscapeHtml(inv.invoice_number)}</strong> doesn't match the Purchase Order and/or Goods Receipt on every line.</div>
                </div>
            </div>
            <table class="table table-sm"><thead><tr><th>Product</th><th>PO Ordered @ Price</th><th>GR Received (Good)</th><th>Invoice Billed @ Price</th><th>Flag</th><th>Adjust</th></tr></thead><tbody>${rowsHtml}</tbody></table>
            <hr>
            <label class="form-label">Reason (shown to the Supplier)</label>
            <textarea id="returnReason" class="form-control" rows="2" placeholder="What needs to be corrected on resubmission?"></textarea>
            <button class="btn btn-danger btn-sm mt-2" onclick="returnToSupplier(${invoiceId}, this)"><i class="bi bi-arrow-return-left"></i> Return to Supplier</button>
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

async function openPoDetail(poId) {
    const modal = new bootstrap.Modal(document.getElementById('fsPoDetailModal'));
    document.getElementById('fsPoDetailBody').innerHTML = 'Loading...';
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
        document.getElementById('fsPoDetailBody').innerHTML = `
            <p><strong>Supplier:</strong> ${prEscapeHtml(po.supplier_name)} &nbsp; <strong>Status:</strong> ${prStatusBadge(po.status)}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Received</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(po.total)}</p>
            ${invoicesHtml}
            <hr><h6>Goods Receipts</h6>
            ${prRenderGoodsReceipts(po.goods_receipts)}
            <hr><h6>History</h6>
            ${prRenderTimeline(po.events)}
        `;
    } catch (e) {
        document.getElementById('fsPoDetailBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

async function returnToSupplier(invoiceId, btn) {
    const reason = document.getElementById('returnReason').value.trim();
    if (!reason) {
        Swal.fire('Reason required', 'Tell the Supplier what needs fixing.', 'warning');
        return;
    }
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_fs_return_invoice_to_supplier', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoice_id: invoiceId, reason }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Returned', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('varianceModal'))?.hide();
        loadHolds();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}
