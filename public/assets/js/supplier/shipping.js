// ============================================
// SUPPLIER — SHIPPING
// ============================================

let spShipPage = 1;
let spShipTab = 'awaiting';
let spShipDiscrepancyTarget = null;

// "Awaiting Shipment" = accepted, waiting on the supplier to mark it shipped.
// "History" = everything shipped onward (in transit through closed).
const SP_SHIP_AWAITING_STATUSES = ['confirmed'];
const SP_SHIP_HISTORY_STATUSES = ['shipped', 'partially_received', 'received', 'pending_payment', 'paid', 'closed', 'discrepancy_logged'];

document.addEventListener('DOMContentLoaded', function () {
    loadShipList();
    document.getElementById('spShipSubmitDiscrepancyBtn').addEventListener('click', submitShipDiscrepancyResolution);
});

function spSwitchShipTab(tabKey) {
    if (spShipTab === tabKey) return;
    spShipTab = tabKey;
    spShipPage = 1;
    document.querySelectorAll('#spShipTabs .nav-link').forEach(btn => btn.classList.toggle('active', btn.dataset.tabKey === tabKey));
    loadShipList();
}

async function loadShipList() {
    const tbody = document.getElementById('spShipTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const statuses = (spShipTab === 'history' ? SP_SHIP_HISTORY_STATUSES : SP_SHIP_AWAITING_STATUSES).join(',');
        const data = await prFetchJson(`?page=api_supplier_list_pos&page_num=${spShipPage}&limit=15&statuses=${encodeURIComponent(statuses)}`);
        const rows = data.purchase_orders || [];
        tbody.innerHTML = rows.length ? rows.map(po => `
            <tr>
                <td>${prEscapeHtml(po.po_number)}</td>
                <td>${prFormatDate(po.order_date)}</td>
                <td>${prCurrency(po.total)}</td>
                <td>${prStatusBadge(po.status)}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="openShipDetail(${po.id})"><i class="bi bi-eye"></i> View</button></td>
            </tr>
        `).join('') : prEmptyRow(5, spShipTab === 'history' ? 'No shipped purchase orders yet.' : 'No purchase orders awaiting shipment.');
        prRenderPagination(document.getElementById('spShipPagination'), document.getElementById('spShipPageInfo'), data.pagination, 'purchase orders', (p) => { spShipPage = p; loadShipList(); });
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}

async function openShipDetail(poId) {
    const modal = new bootstrap.Modal(document.getElementById('spShipDetailModal'));
    document.getElementById('spShipDetailBody').innerHTML = 'Loading...';
    modal.show();
    try {
        const po = await prFetchJson(`?page=api_get_purchase_order&id=${poId}`);
        const itemsHtml = po.items.map(i => `<tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.unit_price)}</td><td>${prCurrency(i.total)}</td></tr>`).join('');
        let actions = '';
        if (po.status === 'confirmed') {
            actions = `
                <button class="btn btn-yellow-primary btn-sm" onclick="shipPoFromShipping(${po.id}, this)"><i class="bi bi-truck"></i> Mark as Shipped</button>
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
                        <button class="btn btn-sm ${d.type === 'missing' ? 'btn-outline-warning' : 'btn-outline-danger'}" onclick="openShipDiscrepancyModal(${po.id}, ${d.id}, '${d.type}')">
                            ${d.type === 'missing' ? 'Backorder' : 'Request RMA'}
                        </button>
                    </div>
                `).join('')}
            </div>
        ` : '';

        document.getElementById('spShipDetailBody').innerHTML = `
            <p><strong>Status:</strong> ${prStatusBadge(po.status)} &nbsp; <strong>Terms:</strong> ${prEscapeHtml(po.terms || '')}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(po.total)}</p>
            ${actions}
            ${discrepancyHtml}
            <hr><h6>History</h6>
            ${prRenderTimeline(po.events)}
        `;
    } catch (e) {
        document.getElementById('spShipDetailBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

async function shipPoFromShipping(poId, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_supplier_ship_po', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: poId }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Shipped', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('spShipDetailModal'))?.hide();
        loadShipList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

function openShipDiscrepancyModal(poId, discrepancyId, type) {
    spShipDiscrepancyTarget = { poId, discrepancyId, type };
    const isMissing = type === 'missing';
    document.getElementById('spShipDiscrepancyModalTitle').textContent = isMissing ? 'Backorder Missing Item' : 'Request RMA — Damaged Item';
    document.getElementById('spShipDiscrepancyModalDesc').textContent = isMissing
        ? 'Give the Store Manager a new delivery date for the missing units. Your invoice for the units already received in good condition can go through right away.'
        : 'This generates an RMA for pickup of the damaged units. Invoicing for this PO stays on hold until the replacement is delivered and received clean.';
    document.getElementById('spShipDiscrepancyBackorderField').classList.toggle('d-none', !isMissing);
    document.getElementById('spShipDiscrepancyPickupField').classList.toggle('d-none', isMissing);
    document.getElementById('spShipDiscrepancyNewDeliveryDate').value = '';
    document.getElementById('spShipDiscrepancyPickupDate').value = '';
    bootstrap.Modal.getInstance(document.getElementById('spShipDetailModal'))?.hide();
    new bootstrap.Modal(document.getElementById('spShipDiscrepancyModal')).show();
}

async function submitShipDiscrepancyResolution() {
    if (!spShipDiscrepancyTarget) return;
    const { poId, discrepancyId, type } = spShipDiscrepancyTarget;
    const isMissing = type === 'missing';
    const payload = {
        po_id: poId,
        discrepancy_id: discrepancyId,
        action: isMissing ? 'backorder' : 'rma',
    };
    if (isMissing) {
        payload.new_delivery_date = document.getElementById('spShipDiscrepancyNewDeliveryDate').value;
        if (!payload.new_delivery_date) { Swal.fire('Date required', 'Enter the new delivery date.', 'warning'); return; }
    } else {
        payload.pickup_date = document.getElementById('spShipDiscrepancyPickupDate').value;
        if (!payload.pickup_date) { Swal.fire('Date required', 'Enter the pickup date.', 'warning'); return; }
    }

    const unlock = prLockButton(document.getElementById('spShipSubmitDiscrepancyBtn'));
    try {
        const res = await fetch('?page=api_supplier_resolve_po_discrepancy', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Recorded', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('spShipDiscrepancyModal'))?.hide();
        loadShipList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}
