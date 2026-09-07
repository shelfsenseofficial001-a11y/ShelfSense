// ============================================
// STORE MANAGER — REQUISITIONS & PURCHASE ORDERS
// ============================================

let smProducts = [];
let smEligibleSuppliers = [];
let smSelectedSupplierId = null;
let minePage = 1;

document.addEventListener('DOMContentLoaded', function () {
    loadProducts();
    loadDepartments();
    loadMineList();
    loadPoList();
    setDefaultOrderDate();
    activateTabFromQuery();

    document.getElementById('mineStatusFilter').addEventListener('change', () => { minePage = 1; loadMineList(); });
    document.getElementById('addItemRowBtn').addEventListener('click', () => addItemRow());
    document.getElementById('submitRequisitionBtn').addEventListener('click', submitRequisition);

    document.getElementById('mineCreateFab').addEventListener('click', function (e) {
        e.preventDefault();
        bootstrap.Tab.getOrCreateInstance(document.getElementById('create-tab')).show();
    });
});

function activateTabFromQuery() {
    const tabKey = new URLSearchParams(window.location.search).get('tab');
    if (!tabKey) return;
    const tabButton = document.querySelector(`#requisitionTabs [data-tab-key="${tabKey}"]`);
    if (tabButton) bootstrap.Tab.getOrCreateInstance(tabButton).show();
}

function setDefaultOrderDate() {
    const input = document.getElementById('createOrderDate');
    if (input) input.value = new Date().toISOString().split('T')[0];
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

async function loadDepartments() {
    try {
        const data = await prFetchJson('?page=api_departments');
        const sel = document.getElementById('createDepartment');
        sel.innerHTML = (data.departments || []).filter(d => d.is_active).map(d => `<option value="${d.id}">${prEscapeHtml(d.name)}</option>`).join('');
    } catch (e) {
        console.error(e);
    }
}

function addItemRow() {
    const list = document.getElementById('createItemsBody');
    const rowId = 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const options = smProducts.map(p => `<option value="${p.store_product_id}">${prEscapeHtml(p.name)}</option>`).join('');
    const row = document.createElement('div');
    row.className = 'sm-item-row';
    row.id = rowId;
    row.innerHTML = `
        <select class="form-select form-select-sm item-product">${options}</select>
        <input type="number" class="form-control form-control-sm item-qty" min="1" max="999" value="1">
        <button type="button" class="sm-item-remove" onclick="document.getElementById('${rowId}').remove(); refreshEligibleSuppliers();" title="Remove item"><i class="bi bi-trash"></i></button>
    `;
    list.appendChild(row);

    row.querySelector('.item-product').addEventListener('change', refreshEligibleSuppliers);
    row.querySelector('.item-qty').addEventListener('input', refreshEligibleSuppliers);
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
            panel.innerHTML = '<div class="alert alert-warning small mb-0">No single supplier carries all of these products in the requested quantities.</div>';
            return;
        }
        panel.innerHTML = smEligibleSuppliers.map(s => `
            <label class="sm-supplier-option" for="supplier_${s.supplier_id}">
                <input class="form-check-input" type="radio" name="supplierChoice" id="supplier_${s.supplier_id}" value="${s.supplier_id}" onchange="selectSupplier(${s.supplier_id})">
                <span class="sm-supplier-name">${prEscapeHtml(s.supplier_name)}</span>
                <span class="sm-supplier-price">${prCurrency(s.total)}</span>
            </label>
        `).join('');
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
        department_id: parseInt(document.getElementById('createDepartment').value) || null,
        order_date: document.getElementById('createOrderDate').value,
        needed_by_date: document.getElementById('createNeededBy').value,
        notes: document.getElementById('createNotes').value,
        items,
    };

    const unlock = prLockButton(document.getElementById('submitRequisitionBtn'));
    try {
        const res = await fetch('?page=api_sm_create_requisition', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Submitted', data.message, 'success');
        document.getElementById('createItemsBody').innerHTML = '';
        document.getElementById('createNotes').value = '';
        smSelectedSupplierId = null;
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
                <td>${r.rejected_reason ? `<span class="text-danger small" title="${prEscapeHtml(r.rejected_reason)}"><i class="bi bi-info-circle"></i></span>` : ''}</td>
            </tr>
        `).join('') : prEmptyRow(7, 'No requisitions found.');
        prRenderPagination(document.getElementById('minePagination'), document.getElementById('minePageInfo'), data.pagination, 'requisitions', (p) => { minePage = p; loadMineList(); });
    } catch (e) {
        tbody.innerHTML = prErrorRow(7, e.message);
    }
}

async function loadPoList() {
    const tbody = document.getElementById('poTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_sm_list_pos&limit=20');
        const rows = data.purchase_orders || [];
        prSetTabBadge('poSectionHeading', rows.length);
        tbody.innerHTML = rows.length ? rows.map(po => `
            <tr>
                <td>${prEscapeHtml(po.po_number)}</td>
                <td>${prEscapeHtml(po.supplier_name)}</td>
                <td>${prCurrency(po.total)}</td>
                <td>${prStatusBadge(po.status)}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="openPoDetail(${po.id})"><i class="bi bi-eye"></i> View</button></td>
            </tr>
        `).join('') : prEmptyRow(5, 'No purchase orders need attention right now.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
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

        let grHtml = '';
        if (['shipped', 'partially_received'].includes(po.status)) {
            grHtml = `
                <hr><h6>Log Goods Receipt</h6>
                <p class="text-muted small">Enter how many of each remaining unit arrived in each condition. Splitting across Good/Damaged/Missing is logged in one submission.</p>
                <table class="table table-sm" id="grItemsTable"><thead><tr><th>Product</th><th>Ordered</th><th>Received So Far</th><th style="width:90px">Good</th><th style="width:90px">Damaged</th><th style="width:90px">Missing</th><th style="width:70px">Remaining</th></tr></thead>
                <tbody>
                ${po.items.map(i => {
                    const remaining = i.quantity - i.received_quantity_total;
                    return `
                    <tr data-po-item-id="${i.id}" data-remaining="${remaining}">
                        <td>${prEscapeHtml(i.store_product_name)}</td>
                        <td>${i.quantity}</td>
                        <td>${i.received_quantity_total}</td>
                        <td><input type="number" class="form-control form-control-sm gr-qty-good" min="0" max="${remaining}" value="0"></td>
                        <td><input type="number" class="form-control form-control-sm gr-qty-damaged" min="0" max="${remaining}" value="0"></td>
                        <td><input type="number" class="form-control form-control-sm gr-qty-missing" min="0" max="${remaining}" value="0"></td>
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
