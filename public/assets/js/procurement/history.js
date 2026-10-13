// ============================================
// PROCUREMENT HISTORY — SEARCH & TRACKING
// (shared by Store Manager + Finance Staff/Head)
// ============================================

let phResults = [];

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('phSearchBtn').addEventListener('click', runHistorySearch);
    document.getElementById('phQuery').addEventListener('keydown', (e) => { if (e.key === 'Enter') runHistorySearch(); });
    runHistorySearch();
});

async function runHistorySearch() {
    const tbody = document.getElementById('phResultsBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Searching...</td></tr>';
    document.getElementById('phDetailBody').innerHTML = '<div class="text-center text-muted py-5">Select a result to view its detail and history.</div>';

    const params = new URLSearchParams({
        q: document.getElementById('phQuery').value.trim(),
        type: document.getElementById('phTypeFilter').value,
        from: document.getElementById('phFromDate').value,
        to: document.getElementById('phToDate').value,
        limit: 100,
    });

    try {
        const data = await prFetchJson(`?page=api_search_procurement_history&${params}`);
        phResults = data.results || [];
        if (phResults.length === 0) {
            tbody.innerHTML = prEmptyRow(6, 'No matching documents found.');
            return;
        }
        tbody.innerHTML = phResults.map((r, idx) => `
            <tr>
                <td>${phTypeLabel(r.type)}</td>
                <td>${prEscapeHtml(r.number)}${r.related_number ? ` <span class="text-muted small">(${prEscapeHtml(r.related_number)})</span>` : ''}</td>
                <td>${prEscapeHtml(r.party_name || '—')}</td>
                <td>${prFormatDate(r.date)}</td>
                <td>${r.status ? prStatusBadge(r.status) : '<span class="text-muted">—</span>'}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="openHistoryDetail(${idx})"><i class="bi bi-eye"></i> View</button></td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = prErrorRow(6, e.message);
    }
}

function phTypeLabel(type) {
    return { requisition: 'Requisition', po: 'Purchase Order', gr: 'Goods Receipt', invoice: 'Invoice' }[type] || type;
}

async function openHistoryDetail(idx) {
    const r = phResults[idx];
    const body = document.getElementById('phDetailBody');
    body.innerHTML = '<div class="text-center text-muted py-5">Loading...</div>';
    try {
        if (r.type === 'requisition') {
            const req = await prFetchJson(`?page=api_get_requisition&id=${r.id}`);
            const itemsHtml = req.items.map(i => `
                <tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.estimated_unit_price)}</td><td>${prCurrency(i.estimated_total)}</td></tr>
            `).join('');
            body.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">${prEscapeHtml(req.requisition_number)}</h5>
                    ${prStatusBadge(req.status)}
                </div>
                <p><strong>Supplier:</strong> ${prEscapeHtml(req.supplier_name || '—')} &nbsp; <strong>Department:</strong> ${prEscapeHtml(req.department_name)} &nbsp; <strong>Requested By:</strong> ${prEscapeHtml(req.requested_by_name || '')}</p>
                <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${itemsHtml}</tbody></table>
                <p class="fw-bold text-end">Total: ${prCurrency(req.subtotal)}</p>
                ${req.purchase_order ? `<p><strong>Purchase Order:</strong> ${prEscapeHtml(req.purchase_order.po_number)} (${prStatusBadge(req.purchase_order.status)})</p>` : ''}
                <hr><h6>History</h6>
                ${prRenderTimeline(req.events)}
            `;
        } else {
            const po = await prFetchJson(`?page=api_get_purchase_order&id=${r.id}`);
            const itemsHtml = po.items.map(i => `
                <tr><td>${prEscapeHtml(i.store_product_name)}</td><td>${i.quantity}</td><td>${prCurrency(i.unit_price)}</td><td>${prCurrency(i.total)}</td><td>${i.received_quantity_total ?? 0}</td></tr>
            `).join('');
            const invoicesHtml = (po.invoices || []).length ? `
                <hr><h6>Invoices</h6>
                <table class="table table-sm"><thead><tr><th>Invoice #</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
                ${po.invoices.map(inv => `<tr><td>${prEscapeHtml(inv.invoice_number)}</td><td>${prCurrency(inv.total)}</td><td>${prStatusBadge(inv.match_status)}</td><td><button class="btn btn-sm btn-outline-secondary" onclick="prOpenMatchDetail(${inv.id})"><i class="bi bi-arrow-left-right"></i> View Match</button></td></tr>`).join('')}
                </tbody></table>
            ` : '';
            const grHtml = po.goods_receipts ? `<hr><h6>Goods Receipts</h6>${prRenderGoodsReceipts(po.goods_receipts)}` : '';
            body.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">${prEscapeHtml(po.po_number)}</h5>
                    ${prStatusBadge(po.status)}
                </div>
                <p><strong>Supplier:</strong> ${prEscapeHtml(po.supplier_name)}</p>
                <table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Received</th></tr></thead><tbody>${itemsHtml}</tbody></table>
                <p class="fw-bold text-end">Total: ${prCurrency(po.total)}</p>
                ${invoicesHtml}
                ${grHtml}
                <hr><h6>History</h6>
                ${prRenderTimeline(po.events)}
            `;
        }
    } catch (e) {
        body.innerHTML = `<div class="alert alert-danger">${prEscapeHtml(e.message)}</div>`;
    }
}
