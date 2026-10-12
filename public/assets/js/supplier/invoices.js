// ============================================
// SUPPLIER — INVOICES
// ============================================

let spRawPos = [];
let spInvoiceablePos = [];
let spMyInvoices = [];
let spSelectedPoId = null;
let spActiveInvTab = 'to_invoice';
let spInvoiceDateIso = '';
let spInvoiceDueDateIso = '';

document.addEventListener('DOMContentLoaded', function () {
    const today = new Date();
    spInvoiceDateIso = today.toISOString().split('T')[0];
    const due = new Date();
    due.setDate(due.getDate() + 30);
    spInvoiceDueDateIso = due.toISOString().split('T')[0];

    loadInvoiceablePos();
    loadMyInvoices();
});

function switchInvoiceListTab(tab) {
    spActiveInvTab = tab;
    document.getElementById('invTabToInvoice').classList.toggle('active', tab === 'to_invoice');
    document.getElementById('invTabSubmitted').classList.toggle('active', tab === 'submitted');
    renderInvoiceListPanel();
}

function renderInvoiceListPanel() {
    const panel = document.getElementById('invListPanel');
    if (spActiveInvTab === 'to_invoice') {
        const rejectedPoIds = new Set(spMyInvoices.filter(inv => inv.match_status === 'rejected').map(inv => inv.po_id));
        panel.innerHTML = spInvoiceablePos.length ? spInvoiceablePos.map(po => `
            <button type="button" class="list-group-item list-group-item-action ${po.id === spSelectedPoId ? 'active' : ''}" onclick="selectInvoicePo(${po.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <strong>${prEscapeHtml(po.po_number)}</strong>
                    ${rejectedPoIds.has(po.id) ? '<span class="badge bg-danger">Needs Revision</span>' : `<span>${prCurrency(po.total)}</span>`}
                </div>
            </button>
        `).join('') : '<div class="text-center text-muted py-4">No purchase orders ready for invoicing.</div>';
    } else {
        panel.innerHTML = spMyInvoices.length ? spMyInvoices.map(inv => `
            <button type="button" class="list-group-item list-group-item-action" onclick="prOpenMatchDetail(${inv.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <strong>${prEscapeHtml(inv.invoice_number)}</strong>
                    ${prStatusBadge(inv.match_status)}
                </div>
                <div class="small text-muted">${prEscapeHtml(inv.po_number)} &middot; ${prCurrency(inv.total)}</div>
            </button>
        `).join('') : '<div class="text-center text-muted py-4">No invoices submitted yet.</div>';
    }
}

async function loadInvoiceablePos() {
    try {
        const data = await prFetchJson('?page=api_supplier_list_pos&limit=100');
        spRawPos = data.purchase_orders || [];
        recomputeInvoiceablePos();
    } catch (e) {
        console.error(e);
    }
}

async function loadMyInvoices() {
    try {
        const data = await prFetchJson('?page=api_supplier_list_invoices&limit=20');
        spMyInvoices = data.invoices || [];
        recomputeInvoiceablePos();
    } catch (e) {
        console.error(e);
    }
}

// One invoice per PO (1:1), and only once it's fully, cleanly received --
// not partially shipped, nothing still open on an RMA/Backorder. Also
// excludes any PO that already has an invoice -- UNLESS that invoice was
// 'rejected' (Returned to Supplier by Finance), in which case it belongs
// back here so it can be corrected and resubmitted in place.
function recomputeInvoiceablePos() {
    const blockingPoIds = new Set(spMyInvoices.filter(inv => inv.match_status !== 'rejected').map(inv => inv.po_id));
    spInvoiceablePos = spRawPos.filter(po => po.status === 'received' && !blockingPoIds.has(po.id));
    if (spSelectedPoId && !spInvoiceablePos.some(po => po.id === spSelectedPoId)) {
        clearCreatePanel();
    }
    renderInvoiceListPanel();
}

function clearCreatePanel() {
    spSelectedPoId = null;
    document.getElementById('invCreatePanel').innerHTML = `
        <div class="text-center text-muted py-5">
            <i class="bi bi-arrow-left-circle fs-1 d-block mb-2"></i>
            Select a purchase order from the list to create an invoice.
        </div>
    `;
}

async function selectInvoicePo(poId) {
    spSelectedPoId = poId;
    renderInvoiceListPanel();

    const panel = document.getElementById('invCreatePanel');
    panel.innerHTML = '<div class="text-center text-muted py-5">Loading...</div>';
    try {
        const po = await prFetchJson(`?page=api_get_purchase_order&id=${poId}`);
        const itemsHtml = po.items.map(i => {
            const billable = i.good_received_quantity ?? i.quantity;
            return `
            <tr data-po-item-id="${i.id}">
                <td>${prEscapeHtml(i.store_product_name)}</td>
                <td>${i.quantity}</td>
                <td>${prCurrency(i.unit_price)}</td>
                <td>
                    <input type="number" class="form-control form-control-sm inv-qty" min="0" max="${billable}" value="${billable}">
                    <div class="form-text">Up to ${billable} received in good condition</div>
                </td>
                <td><input type="number" class="form-control form-control-sm inv-price" min="0" step="0.01" value="${i.unit_price}"></td>
            </tr>
        `;
        }).join('');

        const rejectedInvoice = (po.invoices || []).find(inv => inv.match_status === 'rejected');
        const rejectionBanner = rejectedInvoice ? `
            <div class="alert alert-danger">
                <strong><i class="bi bi-arrow-return-left"></i> Returned by Finance:</strong> ${prEscapeHtml(rejectedInvoice.rejection_reason || '')}
                <div class="small text-muted mb-0">Submitting below replaces invoice ${prEscapeHtml(rejectedInvoice.invoice_number)}.</div>
            </div>
        ` : '';

        panel.innerHTML = `
            ${rejectionBanner}
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <p class="text-muted small mb-1">Purchase Order</p>
                    <p class="fw-semibold mb-0">${prEscapeHtml(po.po_number)}</p>
                </div>
                <div class="col-md-3">
                    <p class="text-muted small mb-1">Invoice Date</p>
                    <p class="fw-semibold mb-0">${prFormatDate(spInvoiceDateIso)}</p>
                </div>
                <div class="col-md-3">
                    <p class="text-muted small mb-1">Due Date</p>
                    <p class="fw-semibold mb-0">${prFormatDate(spInvoiceDueDateIso)}</p>
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="invItemsTable">
                    <thead><tr><th>Product</th><th>PO Qty</th><th>PO Price</th><th style="width:120px">Billed Qty</th><th style="width:140px">Billed Price</th></tr></thead>
                    <tbody id="invItemsBody">${itemsHtml}</tbody>
                </table>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-8">
                    <label class="form-label">Notes (optional)</label>
                    <textarea id="invNotes" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Attach Invoice File (optional, PDF/JPG/PNG)</label>
                    <input type="file" id="invFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
            <div class="text-end mt-3">
                <button class="btn btn-yellow-primary" id="submitInvoiceBtn"><i class="bi bi-send"></i> Submit Invoice</button>
            </div>
        `;
        document.getElementById('submitInvoiceBtn').addEventListener('click', submitInvoice);
    } catch (e) {
        panel.innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}

async function submitInvoice() {
    if (!spSelectedPoId) { Swal.fire('Select a PO', '', 'warning'); return; }

    const items = [];
    document.querySelectorAll('#invItemsBody tr[data-po-item-id]').forEach(tr => {
        items.push({
            po_item_id: parseInt(tr.dataset.poItemId),
            billed_quantity: parseInt(tr.querySelector('.inv-qty').value) || 0,
            billed_unit_price: parseFloat(tr.querySelector('.inv-price').value) || 0,
        });
    });

    const formData = new FormData();
    formData.append('po_id', spSelectedPoId);
    formData.append('invoice_date', spInvoiceDateIso);
    formData.append('due_date', spInvoiceDueDateIso);
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
        clearCreatePanel();
        loadInvoiceablePos();
        loadMyInvoices();
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    } finally {
        unlock();
    }
}
