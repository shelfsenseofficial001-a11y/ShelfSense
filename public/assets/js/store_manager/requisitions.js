// ============================================
// STORE MANAGER — REQUISITIONS & PURCHASE ORDERS
// ============================================

let smProducts = [];
let smSuppliers = [];
let smCartRows = [];
let minePage = 1;

document.addEventListener('DOMContentLoaded', function () {
    loadSuppliersAndProducts();
    loadDepartments();
    loadMineList();
    loadPoList();
    setDefaultOrderDate();

    document.getElementById('mineStatusFilter').addEventListener('change', () => { minePage = 1; loadMineList(); });
    document.getElementById('addItemRowBtn').addEventListener('click', () => addItemRow());
    document.getElementById('submitRequisitionBtn').addEventListener('click', submitRequisition);
    document.getElementById('createSupplier').addEventListener('change', onSupplierChange);
});

function setDefaultOrderDate() {
    const input = document.getElementById('createOrderDate');
    if (input) input.value = new Date().toISOString().split('T')[0];
}

async function loadSuppliersAndProducts() {
    try {
        const data = await prFetchJson('?page=api_get_products_for_requisition');
        smProducts = data.products || [];
        smSuppliers = data.suppliers || [];
        const sel = document.getElementById('createSupplier');
        sel.innerHTML = smSuppliers.map(s => `<option value="${s.id}">${prEscapeHtml(s.company_name)}</option>`).join('');
        if (data.supplier) sel.value = data.supplier.id;
        addItemRow();
    } catch (e) {
        console.error(e);
    }
}

async function onSupplierChange() {
    const supplierId = document.getElementById('createSupplier').value;
    try {
        const data = await prFetchJson(`?page=api_get_products_for_requisition&supplier_id=${supplierId}`);
        smProducts = data.products || [];
        document.getElementById('createItemsBody').innerHTML = '';
        smCartRows = [];
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
    const tbody = document.getElementById('createItemsBody');
    const rowId = 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const options = smProducts.filter(p => p.supplier_product_id).map(p =>
        `<option value="${p.store_product_id}" data-supplier-product="${p.supplier_product_id}" data-price="${p.supplier_price}">${prEscapeHtml(p.name)} (₱${parseFloat(p.supplier_price).toFixed(2)})</option>`
    ).join('');
    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.innerHTML = `
        <td><select class="form-select form-select-sm item-product">${options}</select></td>
        <td><input type="number" class="form-control form-control-sm item-qty" min="1" max="999" value="1"></td>
        <td><input type="number" class="form-control form-control-sm item-price" min="0.01" step="0.01" value="0"></td>
        <td class="item-total">₱0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('${rowId}').remove(); updateSubtotal();"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);

    const productSelect = tr.querySelector('.item-product');
    const priceInput = tr.querySelector('.item-price');
    const syncPrice = () => {
        const opt = productSelect.selectedOptions[0];
        priceInput.value = opt ? opt.dataset.price : 0;
        updateRowTotal(tr);
    };
    productSelect.addEventListener('change', syncPrice);
    tr.querySelector('.item-qty').addEventListener('input', () => updateRowTotal(tr));
    priceInput.addEventListener('input', () => updateRowTotal(tr));
    syncPrice();
}

function updateRowTotal(tr) {
    const qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
    const price = parseFloat(tr.querySelector('.item-price').value) || 0;
    tr.querySelector('.item-total').textContent = prCurrency(qty * price);
    updateSubtotal();
}

function updateSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('#createItemsBody tr').forEach(tr => {
        const qty = parseFloat(tr.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(tr.querySelector('.item-price')?.value) || 0;
        subtotal += qty * price;
    });
    document.getElementById('createSubtotal').textContent = prCurrency(subtotal);
}

async function submitRequisition() {
    const items = [];
    document.querySelectorAll('#createItemsBody tr').forEach(tr => {
        const productSelect = tr.querySelector('.item-product');
        const opt = productSelect.selectedOptions[0];
        if (!opt) return;
        items.push({
            store_product_id: parseInt(productSelect.value),
            supplier_product_id: parseInt(opt.dataset.supplierProduct),
            quantity: parseInt(tr.querySelector('.item-qty').value) || 0,
            unit_price: parseFloat(tr.querySelector('.item-price').value) || 0,
        });
    });

    if (items.length === 0) {
        Swal.fire('No items', 'Add at least one item.', 'warning');
        return;
    }

    const payload = {
        supplier_id: parseInt(document.getElementById('createSupplier').value),
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
                <table class="table table-sm" id="grItemsTable"><thead><tr><th>Product</th><th>Ordered</th><th>Received So Far</th><th style="width:110px">Receiving Now</th><th style="width:140px">Condition</th></tr></thead>
                <tbody>
                ${po.items.map(i => `
                    <tr data-po-item-id="${i.id}">
                        <td>${prEscapeHtml(i.store_product_name)}</td>
                        <td>${i.quantity}</td>
                        <td>${i.received_quantity_total}</td>
                        <td><input type="number" class="form-control form-control-sm gr-qty" min="0" max="${i.quantity - i.received_quantity_total}" value="0"></td>
                        <td><select class="form-select form-select-sm gr-condition"><option value="good">Good</option><option value="damaged">Damaged</option><option value="missing">Missing</option></select></td>
                    </tr>
                `).join('')}
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
    } catch (e) {
        document.getElementById('poDetailBody').innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
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
    document.querySelectorAll('#grItemsTable tr[data-po-item-id]').forEach(tr => {
        const qty = parseInt(tr.querySelector('.gr-qty').value) || 0;
        if (qty > 0) {
            items.push({
                po_item_id: parseInt(tr.dataset.poItemId),
                quantity_received: qty,
                condition: tr.querySelector('.gr-condition').value,
            });
        }
    });
    if (items.length === 0) {
        Swal.fire('Nothing to receive', 'Enter a quantity for at least one item.', 'warning');
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
