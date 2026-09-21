// ============================================
// STORE MANAGER - PRODUCT PROPOSALS
// ============================================

let ppProposals = [];

const PP_STATUS_LABELS = {
    pending_supplier: 'Awaiting Supplier',
    pending_owner: 'Awaiting Owner',
    approved: 'Approved',
    rejected: 'Rejected'
};
const PP_STATUS_CLASSES = {
    pending_supplier: 'bg-warning text-dark',
    pending_owner: 'bg-info text-dark',
    approved: 'bg-success',
    rejected: 'bg-danger'
};

function ppEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function ppCurrency(amount) {
    return '₱' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function ppDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function ppStatusBadge(status) {
    return `<span class="badge ${PP_STATUS_CLASSES[status] || 'bg-secondary'}">${PP_STATUS_LABELS[status] || status}</span>`;
}

function loadCategories() {
    fetch('?page=api_get_categories')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('ppCategory');
            const categories = (data.success ? data.data.categories : []) || [];
            select.innerHTML = '<option value="">No category</option>' + categories.map(c => `<option value="${c.id}">${ppEscapeHtml(c.name)}</option>`).join('');
            window.refreshSearchableSelect?.(select);
        });
}

function loadSuppliers() {
    fetch('?page=api_get_active_suppliers')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('ppSupplier');
            const suppliers = (data.success ? data.data.suppliers : []) || [];
            select.innerHTML = suppliers.map(s => `<option value="${s.id}">${ppEscapeHtml(s.company_name)}</option>`).join('');
            window.refreshSearchableSelect?.(select);
        });
}

function loadProposals() {
    const tbody = document.getElementById('proposalsTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

    fetch('?page=api_get_product_proposals')
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${ppEscapeHtml(data.message || 'Failed to load')}</td></tr>`;
                return;
            }
            ppProposals = data.data.proposals || [];
            renderProposals();
            renderStats();
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error loading proposals.</td></tr>`;
        });
}

function renderStats() {
    const counts = { pending_supplier: 0, pending_owner: 0, approved: 0, rejected: 0 };
    ppProposals.forEach(p => { if (counts[p.status] !== undefined) counts[p.status]++; });
    document.getElementById('statPendingSupplier').textContent = counts.pending_supplier;
    document.getElementById('statPendingOwner').textContent = counts.pending_owner;
    document.getElementById('statApproved').textContent = counts.approved;
    document.getElementById('statRejected').textContent = counts.rejected;
}

function renderProposals() {
    const tbody = document.getElementById('proposalsTableBody');
    if (!ppProposals.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No proposals yet.</td></tr>`;
        return;
    }
    tbody.innerHTML = ppProposals.map(p => `
        <tr class="pp-row" data-id="${p.id}" style="cursor:pointer;">
            <td><strong>${ppEscapeHtml(p.proposed_name)}</strong><br><small class="text-muted">${ppEscapeHtml(p.proposed_barcode)}</small></td>
            <td>${ppEscapeHtml(p.supplier_name)}</td>
            <td>${ppCurrency(p.proposed_price)}</td>
            <td>${ppStatusBadge(p.status)}</td>
            <td>${ppDate(p.created_at)}</td>
            <td><i class="bi bi-chevron-right text-muted"></i></td>
        </tr>
    `).join('');

    tbody.querySelectorAll('.pp-row').forEach(row => {
        row.addEventListener('click', () => openProposalDetail(parseInt(row.dataset.id)));
    });
}

function openProposalDetail(id) {
    const p = ppProposals.find(x => x.id === id);
    if (!p) return;
    const body = document.getElementById('proposalDetailBody');

    let html = `
        <div class="mb-2"><strong>${ppEscapeHtml(p.proposed_name)}</strong> ${ppStatusBadge(p.status)}</div>
        <div class="small text-muted mb-3">${ppEscapeHtml(p.proposed_barcode)} &middot; Proposed by ${ppEscapeHtml(p.proposed_by_name)} on ${ppDate(p.created_at)}</div>
        <div class="row g-2 mb-3">
            <div class="col-6"><div class="text-muted small">Selling Price</div><div class="fw-semibold">${ppCurrency(p.proposed_price)}</div></div>
            <div class="col-6"><div class="text-muted small">Category</div><div class="fw-semibold">${ppEscapeHtml(p.category_name || 'None')}</div></div>
        </div>
        ${p.description ? `<p class="small">${ppEscapeHtml(p.description)}</p>` : ''}
        <div class="sm-section-divider"></div>
        <h6 class="sm-section-title"><i class="bi bi-truck"></i> Supplier: ${ppEscapeHtml(p.supplier_name)}</h6>
    `;

    if (p.status === 'pending_supplier') {
        html += `<p class="text-muted small mb-0">Waiting for the supplier to confirm their price and availability.</p>`;
    } else if (p.supplier_responded_at) {
        if (p.status === 'rejected' && !p.supplier_product_name) {
            html += `<p class="text-danger small mb-0">Declined: ${ppEscapeHtml(p.supplier_decline_reason || 'No reason given.')}</p>`;
        } else {
            html += `
                <div class="row g-2 mb-2">
                    <div class="col-6"><div class="text-muted small">Listed as</div><div class="fw-semibold">${ppEscapeHtml(p.supplier_product_name)}</div></div>
                    <div class="col-6"><div class="text-muted small">Cost</div><div class="fw-semibold">${ppCurrency(p.supplier_price)}</div></div>
                </div>
                <div class="text-muted small">${p.supplier_quantity} available</div>
            `;
        }
    }

    if (p.status === 'rejected' && p.owner_decided_at) {
        html += `<div class="sm-section-divider"></div><h6 class="sm-section-title"><i class="bi bi-person-check"></i> Owner</h6><p class="text-danger small mb-0">Rejected: ${ppEscapeHtml(p.owner_reject_reason || 'No reason given.')}</p>`;
    } else if (p.status === 'approved') {
        html += `<div class="sm-section-divider"></div><p class="text-success small mb-0"><i class="bi bi-check-circle me-1"></i>Approved by ${ppEscapeHtml(p.owner_decided_by_name)} on ${ppDate(p.owner_decided_at)} -- now in Inventory at zero stock.</p>`;
    }

    body.innerHTML = html;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('proposalDetailModal')).show();
}

function submitProposal() {
    const data = {
        proposed_name: document.getElementById('ppName').value.trim(),
        proposed_barcode: document.getElementById('ppBarcode').value.trim(),
        category_id: document.getElementById('ppCategory').value || null,
        proposed_price: parseFloat(document.getElementById('ppPrice').value) || 0,
        supplier_id: document.getElementById('ppSupplier').value || null,
        description: document.getElementById('ppDescription').value.trim()
    };

    if (!data.proposed_name || !data.proposed_barcode || !data.supplier_id || data.proposed_price <= 0) {
        Swal.fire('Missing information', 'Please fill in the product name, barcode, price, and supplier.', 'warning');
        return;
    }

    const btn = document.getElementById('submitProposalBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

    fetch('?page=api_create_product_proposal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Send to Supplier';
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('newProposalModal')).hide();
                document.getElementById('ppName').value = '';
                document.getElementById('ppBarcode').value = '';
                document.getElementById('ppPrice').value = '';
                document.getElementById('ppDescription').value = '';
                Swal.fire({ icon: 'success', title: 'Sent!', text: res.message, timer: 1500, showConfirmButton: false });
                loadProposals();
            } else {
                Swal.fire('Error', res.message || 'Failed to send proposal', 'error');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Send to Supplier';
            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    loadCategories();
    loadSuppliers();
    loadProposals();

    document.getElementById('newProposalBtn').addEventListener('click', () => {
        new bootstrap.Modal(document.getElementById('newProposalModal')).show();
    });
    document.getElementById('submitProposalBtn').addEventListener('click', submitProposal);
});
