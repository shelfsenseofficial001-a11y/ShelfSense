// ============================================
// SUPPLIER — PURCHASE ORDERS
// ============================================

let spPoPage = 1;
let spCounterPoId = null;
let spDiscrepancyTarget = null;

document.addEventListener('DOMContentLoaded', function () {
    loadPoList();
    document.getElementById('spSubmitCounterBtn').addEventListener('click', submitCounter);
    document.getElementById('spSubmitDiscrepancyBtn').addEventListener('click', submitDiscrepancyResolution);
});

async function loadPoList() {
    const tbody = document.getElementById('spPoTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson(`?page=api_supplier_list_pos&page_num=${spPoPage}&limit=15`);
        const rows = data.purchase_orders || [];
        tbody.innerHTML = rows.length ? rows.map(po => `
            <tr>
                <td>${prEscapeHtml(po.po_number)}</td>
                <td>${prFormatDate(po.order_date)}</td>
                <td>${prCurrency(po.total)}</td>
                <td>${prStatusBadge(po.status)}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="openPoDetail(${po.id})"><i class="bi bi-eye"></i> View</button></td>
            </tr>
        `).join('') : prEmptyRow(5, 'No purchase orders yet.');
        prRenderPagination(document.getElementById('spPoPagination'), document.getElementById('spPoPageInfo'), data.pagination, 'purchase orders', (p) => { spPoPage = p; loadPoList(); });
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}

async function openPoDetail(poId) {
    const modal = new bootstrap.Modal(document.getElementById('spPoDetailModal'));
    document.getElementById('spPoDetailBody').innerHTML = 'Loading...';
    modal.show();
    try {
        const po = await prFetchJson(`?page=api_get_purchase_order&id=${poId}`);
        const itemsHtml = po.items.map(i => `<tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.unit_price)}</td><td>${prCurrency(i.total)}</td></tr>`).join('');
        let actions = '';
        if (po.status === 'pending_confirmation') {
            actions = `
                <button class="btn btn-success btn-sm" onclick="acceptPo(${po.id}, this)"><i class="bi bi-check"></i> Accept</button>
                <button class="btn btn-warning btn-sm" onclick="openCounter(${po.id})"><i class="bi bi-pencil"></i> Propose Quantity Change</button>
            `;
        } else if (po.status === 'confirmed') {
            actions = `
                <button class="btn btn-yellow-primary btn-sm" onclick="shipPo(${po.id}, this)"><i class="bi bi-truck"></i> Mark as Shipped</button>
            `;
        }

        const openDiscrepancies = (po.discrepancies || []).filter(d => d.status === 'open');
        const discrepancyHtml = openDiscrepancies.length ? `
            <div class="alert alert-warning mt-3">
                <strong><i class="bi bi-exclamation-triangle"></i> Needs Your Action</strong>
                <p class="small text-muted mb-2">The Store Manager logged these items as not received in good condition. Resolve each before this PO can be invoiced.</p>
                ${openDiscrepancies.map(d => `
                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                        <div>
                            <strong>${prEscapeHtml(d.store_product_name)}</strong>
                            <div class="text-muted small">${d.quantity}x ${d.type === 'missing' ? 'missing' : 'damaged'}</div>
                        </div>
                        <button class="btn btn-sm ${d.type === 'missing' ? 'btn-outline-warning' : 'btn-outline-danger'}" onclick="openDiscrepancyModal(${po.id}, ${d.id}, '${d.type}')">
                            ${d.type === 'missing' ? 'Backorder' : 'Request RMA'}
                        </button>
                    </div>
                `).join('')}
            </div>
        ` : '';

        document.getElementById('spPoDetailBody').innerHTML = `
            <p><strong>Status:</strong> ${prStatusBadge(po.status)} &nbsp; <strong>Terms:</strong> ${prEscapeHtml(po.terms || '')}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(po.total)}</p>
            ${actions}
            ${discrepancyHtml}
            <hr><h6>History</h6>
            ${prRenderTimeline(po.events)}
        `;
        window.spCurrentPo = po;
    } catch (e) {
        document.getElementById('spPoDetailBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

async function acceptPo(poId, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_supplier_respond_po', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: poId, action: 'accept' }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Accepted', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('spPoDetailModal'))?.hide();
        loadPoList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function shipPo(poId, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_supplier_ship_po', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: poId }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Shipped', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('spPoDetailModal'))?.hide();
        loadPoList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

function openCounter(poId) {
    spCounterPoId = poId;
    const po = window.spCurrentPo;
    document.getElementById('spCounterItemsBody').innerHTML = po.items.map(i => `
        <tr data-po-item-id="${i.id}">
            <td>${prEscapeHtml(i.store_product_name)}</td>
            <td>${i.quantity}</td>
            <td><input type="number" class="form-control form-control-sm counter-qty" min="0" value="${i.quantity}"></td>
            <td>${prCurrency(i.unit_price)}</td>
        </tr>
    `).join('');
    document.getElementById('spCounterReason').value = '';
    bootstrap.Modal.getInstance(document.getElementById('spPoDetailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('spCounterModal')).show();
}

async function submitCounter() {
    const items = [];
    document.querySelectorAll('#spCounterItemsBody tr').forEach(tr => {
        items.push({
            po_item_id: parseInt(tr.dataset.poItemId),
            proposed_quantity: parseInt(tr.querySelector('.counter-qty').value) || 0,
        });
    });
    const reason = document.getElementById('spCounterReason').value.trim();
    const unlock = prLockButton(document.getElementById('spSubmitCounterBtn'));
    try {
        const res = await fetch('?page=api_supplier_respond_po', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: spCounterPoId, action: 'counter_propose', reason, items }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Submitted', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('spCounterModal'))?.hide();
        loadPoList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

function openDiscrepancyModal(poId, discrepancyId, type) {
    spDiscrepancyTarget = { poId, discrepancyId, type };
    const isMissing = type === 'missing';
    document.getElementById('spDiscrepancyModalTitle').textContent = isMissing ? 'Backorder Missing Item' : 'Request RMA — Damaged Item';
    document.getElementById('spDiscrepancyModalDesc').textContent = isMissing
        ? 'Give the Store Manager a new delivery date for the missing units. Your invoice for the units already received in good condition can go through right away.'
        : 'This generates an RMA for pickup of the damaged units. Invoicing for this PO stays on hold until the replacement is delivered and received clean.';
    document.getElementById('spDiscrepancyBackorderField').classList.toggle('d-none', !isMissing);
    document.getElementById('spDiscrepancyPickupField').classList.toggle('d-none', isMissing);
    document.getElementById('spDiscrepancyNewDeliveryDate').value = '';
    document.getElementById('spDiscrepancyPickupDate').value = '';
    bootstrap.Modal.getInstance(document.getElementById('spPoDetailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('spDiscrepancyModal')).show();
}

async function submitDiscrepancyResolution() {
    if (!spDiscrepancyTarget) return;
    const { poId, discrepancyId, type } = spDiscrepancyTarget;
    const isMissing = type === 'missing';
    const payload = {
        po_id: poId,
        discrepancy_id: discrepancyId,
        action: isMissing ? 'backorder' : 'rma',
    };
    if (isMissing) {
        payload.new_delivery_date = document.getElementById('spDiscrepancyNewDeliveryDate').value;
        if (!payload.new_delivery_date) { Swal.fire('Date required', 'Enter the new delivery date.', 'warning'); return; }
    } else {
        payload.pickup_date = document.getElementById('spDiscrepancyPickupDate').value;
        if (!payload.pickup_date) { Swal.fire('Date required', 'Enter the pickup date.', 'warning'); return; }
    }

    const unlock = prLockButton(document.getElementById('spSubmitDiscrepancyBtn'));
    try {
        const res = await fetch('?page=api_supplier_resolve_po_discrepancy', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Recorded', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('spDiscrepancyModal'))?.hide();
        loadPoList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}
