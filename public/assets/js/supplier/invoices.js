// ============================================
// SUPPLIER — INVOICES
// ============================================

let spInvoiceablePos = [];

document.addEventListener('DOMContentLoaded', function () {
    loadInvoiceablePos();
    loadMyInvoices();
    document.getElementById('invPoSelect').addEventListener('change', renderInvoiceItems);
    document.getElementById('submitInvoiceBtn').addEventListener('click', submitInvoice);
    document.getElementById('invDate').value = new Date().toISOString().split('T')[0];
    const due = new Date();
    due.setDate(due.getDate() + 30);
    document.getElementById('invDueDate').value = due.toISOString().split('T')[0];
});

async function loadInvoiceablePos() {
    try {
        const data = await prFetchJson('?page=api_supplier_list_pos&limit=100');
        // Supplier is only paid after delivery -- a PO only becomes invoiceable
        // once the Store Manager has logged at least a partial Goods Receipt.
        spInvoiceablePos = (data.purchase_orders || []).filter(po => ['partially_received', 'received'].includes(po.status));
        prSetTabBadge('tabCreateInvoice', spInvoiceablePos.length);
        const sel = document.getElementById('invPoSelect');
        sel.innerHTML = spInvoiceablePos.length
            ? spInvoiceablePos.map(po => `<option value="${po.id}">${prEscapeHtml(po.po_number)} — ${prCurrency(po.total)}</option>`).join('')
            : '<option value="">No purchase orders ready for invoicing</option>';
        renderInvoiceItems();
    } catch (e) {
        console.error(e);
    }
}

async function renderInvoiceItems() {
    const poId = document.getElementById('invPoSelect').value;
    const tbody = document.getElementById('invItemsBody');
    if (!poId) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No purchase order selected.</td></tr>';
        return;
    }
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3">Loading...</td></tr>';
    try {
        const po = await prFetchJson(`?page=api_get_purchase_order&id=${poId}`);
        tbody.innerHTML = po.items.map(i => `
            <tr data-po-item-id="${i.id}">
                <td>${prEscapeHtml(i.store_product_name)}</td>
                <td>${i.quantity}</td>
                <td>${prCurrency(i.unit_price)}</td>
                <td><input type="number" class="form-control form-control-sm inv-qty" min="0" value="${i.quantity}"></td>
                <td><input type="number" class="form-control form-control-sm inv-price" min="0" step="0.01" value="${i.unit_price}"></td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-danger text-center">${prEscapeHtml(e.message)}</td></tr>`;
    }
}

async function submitInvoice() {
    const poId = document.getElementById('invPoSelect').value;
    if (!poId) { Swal.fire('Select a PO', '', 'warning'); return; }

    const items = [];
    document.querySelectorAll('#invItemsBody tr[data-po-item-id]').forEach(tr => {
        items.push({
            po_item_id: parseInt(tr.dataset.poItemId),
            billed_quantity: parseInt(tr.querySelector('.inv-qty').value) || 0,
            billed_unit_price: parseFloat(tr.querySelector('.inv-price').value) || 0,
        });
    });

    const formData = new FormData();
    formData.append('po_id', poId);
    formData.append('invoice_date', document.getElementById('invDate').value);
    formData.append('due_date', document.getElementById('invDueDate').value);
    formData.append('notes', document.getElementById('invNotes').value);
    formData.append('items', JSON.stringify(items));
    const fileInput = document.getElementById('invFile');
    if (fileInput.files[0]) {
        formData.append('file', fileInput.files[0]);
    }

    const unlock = prLockButton(document.getElementById('submitInvoiceBtn'));
    try {
        const res = await fetch('?page=api_supplier_create_invoice', { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        Swal.fire('Submitted', data.message, 'success');
        loadInvoiceablePos();
        loadMyInvoices();
        document.getElementById('invNotes').value = '';
        fileInput.value = '';
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}

async function loadMyInvoices() {
    const tbody = document.getElementById('myInvTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading...</td></tr>';
    try {
        const data = await prFetchJson('?page=api_supplier_list_invoices&limit=20');
        const rows = data.invoices || [];
        tbody.innerHTML = rows.length ? rows.map(inv => `
            <tr>
                <td>${prEscapeHtml(inv.invoice_number)}</td>
                <td>${prEscapeHtml(inv.po_number)}</td>
                <td>${prCurrency(inv.total)}</td>
                <td>${prStatusBadge(inv.match_status)}</td>
                <td>${prFormatDate(inv.created_at)}</td>
            </tr>
        `).join('') : prEmptyRow(5, 'No invoices submitted yet.');
    } catch (e) {
        tbody.innerHTML = prErrorRow(5, e.message);
    }
}
