// ============================================
// STORE MANAGER — REQUISITIONS & PURCHASE ORDERS
// ============================================

let smProducts = [];
let smEligibleSuppliers = [];
let smSelectedSupplierId = null;
let minePage = 1;
let smDiscrepancyType = '';

document.addEventListener('DOMContentLoaded', function () {
    loadProducts();
    loadMineList();
    loadPoList();
    loadDiscrepancies();
    setDefaultOrderDate();
    setNeededByBounds();
    activateTabFromQuery();

    document.getElementById('mineStatusFilter').addEventListener('change', () => { minePage = 1; loadMineList(); });
    document.getElementById('addItemRowBtn').addEventListener('click', () => addItemRow());
    document.getElementById('submitRequisitionBtn').addEventListener('click', submitRequisition);

    document.getElementById('mineCreateFab').addEventListener('click', function (e) {
        e.preventDefault();
        bootstrap.Tab.getOrCreateInstance(document.getElementById('create-tab')).show();
    });

    document.getElementById('confirmRateSupplierBtn').addEventListener('click', submitSupplierRating);
});

// ============================================
// SUPPLIER RATING (Close & Review)
// ============================================

let smRateTargetPoId = null;

function openRateSupplier(poId) {
    smRateTargetPoId = poId;
    document.getElementById('rateOnTime').checked = true;
    document.getElementById('rateFillRate').value = 100;
    document.getElementById('rateQuality').value = '3';
    document.getElementById('rateNotes').value = '';
    new bootstrap.Modal(document.getElementById('rateSupplierModal')).show();
}

async function submitSupplierRating() {
    const payload = {
        po_id: smRateTargetPoId,
        on_time: document.getElementById('rateOnTime').checked,
        fill_rate_percent: parseFloat(document.getElementById('rateFillRate').value) || 0,
        quality_rating: parseInt(document.getElementById('rateQuality').value, 10),
        notes: document.getElementById('rateNotes').value,
    };
    const unlock = prLockButton(document.getElementById('confirmRateSupplierBtn'));
    try {
        const res = await fetch('?page=api_sm_rate_supplier', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        bootstrap.Modal.getInstance(document.getElementById('rateSupplierModal'))?.hide();
        Swal.fire('Thanks!', data.message, 'success');
        loadPoList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

function activateTabFromQuery() {
    const tabKey = new URLSearchParams(window.location.search).get('tab');
    if (!tabKey) return;
    const tabButton = document.querySelector(`#requisitionTabs [data-tab-key="${tabKey}"]`);
    if (tabButton) bootstrap.Tab.getOrCreateInstance(tabButton).show();
}

// Matches the server-side rule in validateExpectedDeliveryDate() exactly:
// today through +6 months, no earlier, no later. Set directly on the real
// (now-hidden) input's min/max attributes before date-picker-modal.js
// upgrades it, so the calendar disables everything outside that window.
function setNeededByBounds() {
    const input = document.getElementById('createNeededBy');
    if (!input) return;
    const toIso = (d) => d.toISOString().split('T')[0];
    const today = new Date();
    const max = new Date();
    max.setMonth(max.getMonth() + 6);
    input.min = toIso(today);
    input.max = toIso(max);
}

function setDefaultOrderDate() {
    // Display-only -- always today, never editable. Not part of the submit
    // payload at all; the server stamps order_date itself.
    const input = document.getElementById('createOrderDate');
    if (input) input.value = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

async function loadProducts() {
    try {
        const data = await prFetchJson('?page=api_get_products_for_requisition');
        smProducts = data.products || [];
        addItemRow();
    } catch (e) {
        console.error(e);
    }
}

// Product ids already picked in OTHER rows -- excluded from a row's own
// dropdown so the same product can never be added twice. A row keeps
// showing its own current selection regardless (it's not "taken" from
// its own point of view).
function selectedProductIds(excludeRowId) {
    const ids = new Set();
    document.querySelectorAll('#createItemsBody .sm-item-row').forEach(row => {
        if (row.id === excludeRowId) return;
        const v = parseInt(row.querySelector('.item-product')?.value);
        if (v) ids.add(v);
    });
    return ids;
}

function buildProductOptions(excludeRowId, currentValue) {
    const taken = selectedProductIds(excludeRowId);
    return smProducts
        .filter(p => p.store_product_id === currentValue || !taken.has(p.store_product_id))
        .map(p => `<option value="${p.store_product_id}" ${p.store_product_id === currentValue ? 'selected' : ''}>${prEscapeHtml(p.name)}</option>`)
        .join('');
}

// Re-renders every OTHER row's option list after one row's selection
// changes, so a product just picked disappears from the rest immediately
// (and reappears if that row is changed away from it again).
function refreshAllProductOptions(justChangedRowId) {
    document.querySelectorAll('#createItemsBody .sm-item-row').forEach(row => {
        if (row.id === justChangedRowId) return;
        const select = row.querySelector('.item-product');
        const current = parseInt(select.value);
        select.innerHTML = buildProductOptions(row.id, current);
    });
}

function addItemRow(presetProductId, presetQty) {
    const list = document.getElementById('createItemsBody');
    const rowId = 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const options = buildProductOptions(rowId, presetProductId);
    const row = document.createElement('div');
    row.className = 'sm-item-row';
    row.id = rowId;
    row.innerHTML = `
        <select class="form-select form-select-sm item-product">${options}</select>
        <input type="number" class="form-control form-control-sm item-qty" min="1" max="1000" value="${presetQty || 1}">
        <button type="button" class="sm-item-remove" onclick="document.getElementById('${rowId}').remove(); refreshAllProductOptions(); refreshEligibleSuppliers();" title="Remove item"><i class="bi bi-trash"></i></button>
    `;
    list.appendChild(row);

    row.querySelector('.item-product').addEventListener('change', function () {
        refreshAllProductOptions(rowId);
        refreshEligibleSuppliers();
    });
    const qtyInput = row.querySelector('.item-qty');
    qtyInput.addEventListener('input', function () {
        if (this.value !== '' && parseInt(this.value, 10) > 1000) {
            this.value = 1000;
            Swal.fire({ icon: 'warning', title: 'Limit is 1000', text: 'Quantity cannot exceed 1000 per item.', timer: 2000, timerProgressBar: true });
        }
        refreshEligibleSuppliers();
    });
    refreshAllProductOptions(rowId);
    refreshEligibleSuppliers();
}

function collectItems() {
    const items = [];
    document.querySelectorAll('#createItemsBody .sm-item-row').forEach(row => {
        const storeProductId = parseInt(row.querySelector('.item-product')?.value);
        const quantity = parseInt(row.querySelector('.item-qty')?.value) || 0;
        if (storeProductId && quantity > 0) {
            items.push({ store_product_id: storeProductId, quantity });
        }
    });
    return items;
}

let refreshSuppliersTimer = null;
function refreshEligibleSuppliers() {
    clearTimeout(refreshSuppliersTimer);
    refreshSuppliersTimer = setTimeout(doRefreshEligibleSuppliers, 250);
}

async function doRefreshEligibleSuppliers() {
    const panel = document.getElementById('eligibleSuppliersPanel');
    const submitBtn = document.getElementById('submitRequisitionBtn');
    smSelectedSupplierId = null;
    submitBtn.disabled = true;
    document.getElementById('createSubtotal').textContent = prCurrency(0);

    const items = collectItems();
    if (items.length === 0) {
        panel.innerHTML = '<p class="text-muted small">Add at least one item to see which suppliers can fulfill this request.</p>';
        return;
    }

    panel.innerHTML = '<p class="text-muted small">Checking suppliers...</p>';
    try {
        const data = await prFetchJson(`?page=api_sm_list_eligible_suppliers&items=${encodeURIComponent(JSON.stringify(items))}`);
        smEligibleSuppliers = data.suppliers || [];
        if (smEligibleSuppliers.length === 0) {
            panel.innerHTML = '<div class="alert alert-warning small mb-0">No supplier carries all of these products at all.</div>';
            return;
        }
        panel.innerHTML = smEligibleSuppliers.map(s => {
            return `
            <label class="sm-supplier-option" for="supplier_${s.supplier_id}">
                <input class="form-check-input" type="radio" name="supplierChoice" id="supplier_${s.supplier_id}" value="${s.supplier_id}" onchange="selectSupplier(${s.supplier_id})">
                <span class="sm-supplier-name">${prEscapeHtml(s.supplier_name)}</span>
                <span class="sm-supplier-price">${prCurrency(s.total)}</span>
            </label>
            <ul class="sm-supplier-items">
                ${s.items.map(item => {
                    const storeName = smProducts.find(p => p.store_product_id === item.store_product_id)?.name || '';
                    const sameName = storeName.trim().toLowerCase() === (item.supplier_product_name || '').trim().toLowerCase();
                    const stockLabel = `<span class="text-muted small">(${item.available_quantity} in stock at this supplier)</span>`;
                    return `<li>${prEscapeHtml(storeName)}${!sameName && item.supplier_product_name ? ` <span class="text-muted">(${prEscapeHtml(item.supplier_product_name)} at ${prEscapeHtml(s.supplier_name)})</span>` : ''} &times; ${item.quantity} ${stockLabel}</li>`;
                }).join('')}
            </ul>
        `;
        }).join('');
    } catch (e) {
        panel.innerHTML = `<div class="alert alert-danger small mb-0">${prEscapeHtml(e.message)}</div>`;
    }
}

function selectSupplier(supplierId) {
    smSelectedSupplierId = supplierId;
    const supplier = smEligibleSuppliers.find(s => s.supplier_id === supplierId);
    document.getElementById('createSubtotal').textContent = prCurrency(supplier ? supplier.total : 0);
    document.getElementById('submitRequisitionBtn').disabled = !supplier;
    document.querySelectorAll('#eligibleSuppliersPanel .sm-supplier-option').forEach(el => {
        el.classList.toggle('selected', el.querySelector('input').value == supplierId);
    });
}

let smResubmitTargetId = null;

async function submitRequisition() {
    const items = collectItems();
    if (items.length === 0) {
        Swal.fire('No items', 'Add at least one item.', 'warning');
        return;
    }
    if (!smSelectedSupplierId) {
        Swal.fire('Choose a supplier', 'Select one of the eligible suppliers before submitting.', 'warning');
        return;
    }

    const payload = {
        supplier_id: smSelectedSupplierId,
        needed_by_date: document.getElementById('createNeededBy').value,
        notes: document.getElementById('createNotes').value,
        supplier_note: document.getElementById('createSupplierNote').value,
        items,
    };

    const isResubmit = !!smResubmitTargetId;
    if (isResubmit) payload.requisition_id = smResubmitTargetId;
    const endpoint = isResubmit ? 'api_sm_resubmit_requisition' : 'api_sm_create_requisition';

    const unlock = prLockButton(document.getElementById('submitRequisitionBtn'));
    try {
        const res = await fetch(`?page=${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire(isResubmit ? 'Resubmitted' : 'Submitted', data.message, 'success');
        document.getElementById('createItemsBody').innerHTML = '';
        document.getElementById('createNotes').value = '';
        document.getElementById('createSupplierNote').value = '';
        smSelectedSupplierId = null;
        smResubmitTargetId = null;
        document.getElementById('submitRequisitionBtn').innerHTML = '<i class="bi bi-send"></i> Submit for Budget Check';
        addItemRow();
        minePage = 1;
        loadMineList();
        new bootstrap.Tab(document.getElementById('mine-tab')).show();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

/** Finance Head sent this requisition back -- pre-fill the create form from
 *  its existing items/supplier/notes so the Store Manager can adjust and
 *  resubmit, rather than starting over from scratch. */
async function reviseRequisition(id) {
    try {
        const req = await prFetchJson(`?page=api_get_requisition&id=${id}`);
        smResubmitTargetId = id;
        document.getElementById('createItemsBody').innerHTML = '';
        (req.items || []).forEach(it => addItemRow(it.store_product_id, it.quantity));
        document.getElementById('createNeededBy').value = req.needed_by_date || '';
        document.getElementById('createNotes').value = req.notes || '';
        document.getElementById('createSupplierNote').value = '';
        document.getElementById('submitRequisitionBtn').innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Resubmit for Budget Check';
        const revisionEvent = (req.events || []).slice().reverse().find(e => e.event_type === 'revision_requested');
        if (revisionEvent) {
            Swal.fire('Finance Head requested a revision', revisionEvent.description, 'info');
        }
        new bootstrap.Tab(document.getElementById('create-tab')).show();
        setTimeout(() => {
            const supplierRadio = document.querySelector(`#eligibleSuppliersPanel input[value="${req.preferred_supplier_id}"]`);
            if (supplierRadio) { supplierRadio.checked = true; selectSupplier(parseInt(req.preferred_supplier_id)); }
        }, 600);
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    }
}

async function loadMineList() {
    const tbody = document.getElementById('mineTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Loading...</td></tr>';
    const status = document.getElementById('mineStatusFilter').value;
    try {
        const params = new URLSearchParams({ page_num: minePage, limit: 10 });
        if (status) params.set('status', status);
        const data = await prFetchJson(`?page=api_sm_list_requisitions&${params}`);
        const rows = data.requisitions || [];
        tbody.innerHTML = rows.length ? rows.map(r => `
            <tr>
                <td>${prEscapeHtml(r.requisition_number)}</td>
                <td>${prEscapeHtml(r.supplier_name)}</td>
                <td>${prEscapeHtml(r.department_name)}</td>
                <td>${prCurrency(r.subtotal)}</td>
                <td>${prStatusBadge(r.status)}</td>
                <td>${prFormatDate(r.created_at)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="openRequisitionDetail(${r.id})"><i class="bi bi-eye"></i> View</button>
                    ${r.rejected_reason ? `<span class="text-danger small" title="${prEscapeHtml(r.rejected_reason)}"><i class="bi bi-info-circle"></i></span>` : ''}
                    ${(r.status === 'revision_requested' || r.status === 'budget_rejected') ? `<button class="btn btn-sm btn-outline-warning" onclick="reviseRequisition(${r.id})"><i class="bi bi-arrow-counterclockwise"></i> Revise</button>` : ''}
                </td>
            </tr>
        `).join('') : prEmptyRow(7, 'No requisitions found.');
        prRenderPagination(document.getElementById('minePagination'), document.getElementById('minePageInfo'), data.pagination, 'requisitions', (p) => { minePage = p; loadMineList(); });
    } catch (e) {
        tbody.innerHTML = prErrorRow(7, e.message);
    }
}

async function openRequisitionDetail(reqId) {
    const modal = new bootstrap.Modal(document.getElementById('reqDetailModal'));
    const body = document.getElementById('reqDetailBody');
    body.innerHTML = 'Loading...';
    modal.show();
    try {
        const r = await prFetchJson(`?page=api_get_requisition&id=${reqId}`);
        const itemsHtml = r.items.map(i => `
            <tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.estimated_unit_price)}</td><td>${prCurrency(i.estimated_total)}</td></tr>
        `).join('');
        const budgetHtml = r.budget_status ? `
            <p><strong>Budget Available:</strong> ${prCurrency(r.budget_status.available)} ${r.budget_status.exceeded ? '<span class="badge bg-danger">Exceeded</span>' : ''}</p>
        ` : '';
        body.innerHTML = `
            <p><strong>Supplier:</strong> ${prEscapeHtml(r.supplier_name)} &nbsp; <strong>Department:</strong> ${prEscapeHtml(r.department_name)} &nbsp; <strong>Status:</strong> ${prStatusBadge(r.status)}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(r.subtotal)}</p>
            ${budgetHtml}
            ${r.notes ? `<p><strong>Notes:</strong> ${prEscapeHtml(r.notes)}</p>` : ''}
            ${r.purchase_order ? `<p><strong>Purchase Order:</strong> ${prEscapeHtml(r.purchase_order.po_number)} (${prStatusBadge(r.purchase_order.status)})</p>` : ''}
            <hr><h6>History</h6>
            ${prRenderTimeline(r.events)}
        `;
    } catch (e) {
        body.innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

async function loadPoList() {
    const tbody = document.getElementById('poTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_sm_list_pos&limit=20');
        const rows = data.purchase_orders || [];
        prSetTabBadge('po-tab', rows.length);
        tbody.innerHTML = rows.length ? rows.map(po => `
            <tr>
                <td>${prEscapeHtml(po.po_number)}</td>
                <td>${prEscapeHtml(po.supplier_name)}</td>
                <td>${prCurrency(po.total)}</td>
                <td>${prStatusBadge(po.status)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="openPoDetail(${po.id})"><i class="bi bi-eye"></i> View</button>
                    ${po.status === 'paid' ? `<button class="btn btn-sm btn-outline-success" onclick="openRateSupplier(${po.id})"><i class="bi bi-star"></i> Rate Supplier</button>` : ''}
                </td>
            </tr>
        `).join('') : prEmptyRow(5, 'No purchase orders need attention right now.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}

function setDiscrepancyFilter(type) {
    smDiscrepancyType = type;
    document.getElementById('discFilterAll').classList.toggle('active', type === '');
    document.getElementById('discFilterBackorder').classList.toggle('active', type === 'missing');
    document.getElementById('discFilterRma').classList.toggle('active', type === 'damaged');
    loadDiscrepancies();
}

async function loadDiscrepancies() {
    const tbody = document.getElementById('discTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">Loading...</td></tr>';
    try {
        const params = new URLSearchParams({ limit: 50 });
        if (smDiscrepancyType) params.set('type', smDiscrepancyType);
        const data = await prFetchJson(`?page=api_sm_list_discrepancies&${params}`);
        const rows = data.discrepancies || [];
        tbody.innerHTML = rows.length ? rows.map(d => `
            <tr>
                <td>${prEscapeHtml(d.po_number)}</td>
                <td>${prEscapeHtml(d.supplier_name)}</td>
                <td>${prEscapeHtml(d.store_product_name)}</td>
                <td>${d.type === 'missing' ? 'Missing (Backorder)' : 'Damaged (RMA)'}</td>
                <td>${d.quantity}</td>
                <td>${prStatusBadge(d.status === 'open' ? 'discrepancy_logged' : d.status)}</td>
                <td class="small text-muted">${d.type === 'missing'
                    ? (d.new_delivery_date ? `New delivery ${prFormatDate(d.new_delivery_date)}` : 'Awaiting supplier action')
                    : (d.rma_number ? `${prEscapeHtml(d.rma_number)}, pickup ${prFormatDate(d.pickup_date)}${d.status === 'resolved' ? ' — replacement received' : ''}` : 'Awaiting supplier action')}
                </td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="openPoDetail(${d.po_id})"><i class="bi bi-eye"></i> View PO</button></td>
            </tr>
        `).join('') : prEmptyRow(8, 'No backorders or RMAs on file.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(8, e.message);
    }
}

async function openPoDetail(poId) {
    const modalEl = document.getElementById('poDetailModal');
    const modal = new bootstrap.Modal(modalEl);
    document.getElementById('poDetailBody').innerHTML = 'Loading...';
    modal.show();
    try {
        const po = await prFetchJson(`?page=api_get_purchase_order&id=${poId}`);
        let itemsHtml = po.items.map(i => `
            <tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.unit_price)}</td><td>${prCurrency(i.total)}</td><td>${i.received_quantity_total}</td></tr>
        `).join('');

        let counterHtml = '';
        if (po.pending_counter_proposal) {
            const p = po.pending_counter_proposal;
            counterHtml = `
                <div class="alert alert-warning mt-3">
                    <strong>Supplier Counter-Proposal</strong> ${p.reason ? '— ' + prEscapeHtml(p.reason) : ''}
                    <p class="small text-muted mb-1">Price is fixed at the PO's contracted rate — only quantity can be proposed.</p>
                    <table class="table table-sm mt-2"><thead><tr><th>Product</th><th>Current Qty</th><th>Proposed Qty</th><th>Price</th></tr></thead><tbody>
                    ${p.items.map(pi => `<tr><td>${prEscapeHtml(pi.store_product_name)}</td><td>${pi.current_quantity}</td><td>${pi.proposed_quantity ?? '—'}</td><td>${prCurrency(pi.current_unit_price)}</td></tr>`).join('')}
                    </tbody></table>
                    <button class="btn btn-sm btn-success" onclick="respondToCounter(${p.id}, 'accept', this)">Accept</button>
                    <button class="btn btn-sm btn-danger" onclick="respondToCounter(${p.id}, 'reject', this)">Reject &amp; Cancel PO</button>
                </div>
            `;
        }

        const discrepancies = po.discrepancies || [];
        let discrepancyHtml = '';
        if (discrepancies.length) {
            discrepancyHtml = `
                <hr><h6>Discrepancies</h6>
                <table class="table table-sm"><thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>Status</th><th>Detail</th></tr></thead><tbody>
                ${discrepancies.map(d => `
                    <tr>
                        <td>${prEscapeHtml(d.store_product_name)}</td>
                        <td>${d.type === 'missing' ? 'Missing' : 'Damaged'}</td>
                        <td>${d.quantity}</td>
                        <td>${prStatusBadge(d.status === 'open' ? 'discrepancy_logged' : d.status)}</td>
                        <td class="small text-muted">${d.type === 'missing'
                            ? (d.new_delivery_date ? `Backordered, new delivery ${prFormatDate(d.new_delivery_date)}` : 'Awaiting supplier action')
                            : (d.rma_number ? `${prEscapeHtml(d.rma_number)}, pickup ${prFormatDate(d.pickup_date)}${d.status === 'resolved' ? ' — replacement received' : ''}` : 'Awaiting supplier action')}
                        </td>
                    </tr>
                `).join('')}
                </tbody></table>
            `;
        }

        let grHtml = '';
        if (['shipped', 'partially_received', 'discrepancy_logged'].includes(po.status)) {
            grHtml = `
                <hr><h6>Log Goods Receipt</h6>
                <p class="text-muted small">Enter how many of each remaining unit arrived in each condition. Splitting across Good/Damaged/Missing is logged in one submission.</p>
                <table class="table table-sm" id="grItemsTable"><thead><tr><th>Product</th><th>Ordered</th><th>Received So Far</th><th style="width:90px">Good</th><th style="width:90px">Damaged</th><th style="width:90px">Missing</th><th style="width:70px">Remaining</th></tr></thead>
                <tbody>
                ${po.items.map(i => {
                    const outstanding = i.outstanding_discrepancy_quantity || 0;
                    const remaining = Math.max(i.quantity - i.received_quantity_total, 0) + outstanding;
                    // A line with an open RMA/Backorder already on file can only
                    // take the replacement as Good here -- Damaged/Missing are
                    // tracked by that existing record, not re-raised on top of it.
                    const lockBadCondition = outstanding > 0;
                    return `
                    <tr data-po-item-id="${i.id}" data-remaining="${remaining}">
                        <td>${prEscapeHtml(i.store_product_name)}</td>
                        <td>${i.quantity}</td>
                        <td>${i.received_quantity_total}</td>
                        <td><input type="number" class="form-control form-control-sm gr-qty-good" min="0" max="${remaining}" value="0"></td>
                        <td><input type="number" class="form-control form-control-sm gr-qty-damaged" min="0" max="${remaining}" value="0" ${lockBadCondition ? 'disabled title="Already tracked via RMA -- log the replacement as Good"' : ''}></td>
                        <td><input type="number" class="form-control form-control-sm gr-qty-missing" min="0" max="${remaining}" value="0" ${lockBadCondition ? 'disabled title="Already tracked via Backorder -- log the arrival as Good"' : ''}></td>
                        <td class="gr-remaining-display text-muted">${remaining}</td>
                    </tr>
                `;
                }).join('')}
                </tbody></table>
                <button class="btn btn-yellow-primary btn-sm" onclick="submitGoodsReceipt(${po.id}, this)"><i class="bi bi-box-seam"></i> Log Receipt</button>
            `;
        }

        document.getElementById('poDetailBody').innerHTML = `
            <p><strong>Supplier:</strong> ${prEscapeHtml(po.supplier_name)} &nbsp; <strong>Status:</strong> ${prStatusBadge(po.status)}</p>
            <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Received</th></tr></thead><tbody>${itemsHtml}</tbody></table>
            <p class="fw-bold text-end">Total: ${prCurrency(po.total)}</p>
            ${counterHtml}
            ${discrepancyHtml}
            ${grHtml}
            <hr><h6>History</h6>
            ${prRenderTimeline(po.events)}
        `;
        wireGoodsReceiptRows();
    } catch (e) {
        document.getElementById('poDetailBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

function wireGoodsReceiptRows() {
    document.querySelectorAll('#grItemsTable tr[data-po-item-id]').forEach(tr => {
        const inputs = tr.querySelectorAll('.gr-qty-good, .gr-qty-damaged, .gr-qty-missing');
        const remaining = parseInt(tr.dataset.remaining) || 0;
        const display = tr.querySelector('.gr-remaining-display');
        const recalc = () => {
            const used = Array.from(inputs).reduce((sum, el) => sum + (parseInt(el.value) || 0), 0);
            display.textContent = remaining - used;
            display.classList.toggle('text-danger', used > remaining);
        };
        inputs.forEach(el => el.addEventListener('input', recalc));
    });
}

async function respondToCounter(proposalId, action, btn) {
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_sm_respond_to_counter', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ proposal_id: proposalId, action }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Done', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('poDetailModal'))?.hide();
        loadPoList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function submitGoodsReceipt(poId, btn) {
    const items = [];
    let overRemaining = false;
    document.querySelectorAll('#grItemsTable tr[data-po-item-id]').forEach(tr => {
        const poItemId = parseInt(tr.dataset.poItemId);
        const remaining = parseInt(tr.dataset.remaining) || 0;
        const conditions = [
            ['good', tr.querySelector('.gr-qty-good')],
            ['damaged', tr.querySelector('.gr-qty-damaged')],
            ['missing', tr.querySelector('.gr-qty-missing')],
        ];
        let rowTotal = 0;
        conditions.forEach(([condition, el]) => {
            const qty = parseInt(el.value) || 0;
            rowTotal += qty;
            if (qty > 0) {
                items.push({ po_item_id: poItemId, quantity_received: qty, condition });
            }
        });
        if (rowTotal > remaining) overRemaining = true;
    });
    if (items.length === 0) {
        Swal.fire('Nothing to receive', 'Enter a quantity for at least one item.', 'warning');
        return;
    }
    if (overRemaining) {
        Swal.fire('Too many', 'Good + Damaged + Missing can\'t exceed the remaining quantity for an item.', 'warning');
        return;
    }
    const unlock = prLockButton(btn);
    try {
        const res = await fetch('?page=api_sm_create_goods_receipt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ po_id: poId, items }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Received', data.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('poDetailModal'))?.hide();
        loadPoList();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}
