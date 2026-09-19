// ============================================
// SUPPLIER - PRODUCT PROPOSALS
// ============================================

let ppProposals = [];
let ppActingId = null;

const PP_STATUS_LABELS = {
    pending_supplier: 'Needs Your Response',
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

function loadProposals() {
    const tbody = document.getElementById('proposalsTableBody');
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

    fetch('?page=api_get_product_proposals')
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${ppEscapeHtml(data.message || 'Failed to load')}</td></tr>`;
                return;
            }
            ppProposals = data.data.proposals || [];
            renderProposals();
            renderStats();
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error loading proposals.</td></tr>`;
        });
}

function renderStats() {
    const counts = { pending_supplier: 0, pending_owner: 0, approved: 0 };
    ppProposals.forEach(p => { if (counts[p.status] !== undefined) counts[p.status]++; });
    document.getElementById('statPendingSupplier').textContent = counts.pending_supplier;
    document.getElementById('statPendingOwner').textContent = counts.pending_owner;
    document.getElementById('statApproved').textContent = counts.approved;
}

function renderProposals() {
    const tbody = document.getElementById('proposalsTableBody');
    if (!ppProposals.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No proposals yet.</td></tr>`;
        return;
    }
    const sorted = [...ppProposals].sort((a, b) => {
        if (a.status === 'pending_supplier' && b.status !== 'pending_supplier') return -1;
        if (b.status === 'pending_supplier' && a.status !== 'pending_supplier') return 1;
        return new Date(b.created_at) - new Date(a.created_at);
    });

    tbody.innerHTML = sorted.map(p => `
        <tr class="pp-row" data-id="${p.id}" style="cursor:pointer;">
            <td><strong>${ppEscapeHtml(p.proposed_name)}</strong><br><small class="text-muted">${ppEscapeHtml(p.proposed_barcode)}</small></td>
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
        <div class="small text-muted mb-3">${ppEscapeHtml(p.proposed_barcode)} &middot; Requested ${ppDate(p.created_at)}</div>
        <div class="row g-2 mb-3">
            <div class="col-6"><div class="text-muted small">Store's Selling Price</div><div class="fw-semibold">${ppCurrency(p.proposed_price)}</div></div>
            <div class="col-6"><div class="text-muted small">Category</div><div class="fw-semibold">${ppEscapeHtml(p.category_name || 'None')}</div></div>
        </div>
        ${p.description ? `<p class="small">${ppEscapeHtml(p.description)}</p>` : ''}
    `;

    if (p.status !== 'pending_supplier' && p.supplier_responded_at) {
        html += `
            <div class="sm-section-divider"></div>
            <h6 class="sm-section-title"><i class="bi bi-check2"></i> Your Response</h6>
        `;
        if (p.status === 'rejected' && !p.supplier_product_name) {
            html += `<p class="text-danger small mb-0">You declined: ${ppEscapeHtml(p.supplier_decline_reason || '')}</p>`;
        } else {
            html += `
                <div class="row g-2">
                    <div class="col-6"><div class="text-muted small">Listed as</div><div class="fw-semibold">${ppEscapeHtml(p.supplier_product_name)}</div></div>
                    <div class="col-6"><div class="text-muted small">Your Price</div><div class="fw-semibold">${ppCurrency(p.supplier_price)}</div></div>
                </div>
                <div class="text-muted small mt-1">${p.supplier_quantity} available</div>
            `;
        }
    }

    if (p.status === 'rejected' && p.owner_decided_at) {
        html += `<div class="sm-section-divider"></div><h6 class="sm-section-title"><i class="bi bi-person-x"></i> Owner Decision</h6><p class="text-danger small mb-0">Rejected: ${ppEscapeHtml(p.owner_reject_reason || 'No reason given.')}</p>`;
    } else if (p.status === 'approved') {
        html += `<div class="sm-section-divider"></div><p class="text-success small mb-0"><i class="bi bi-check-circle me-1"></i>Approved -- this is now an active product with the store.</p>`;
    }

    if (p.status === 'pending_supplier') {
        html += `
            <div class="sm-section-divider"></div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm flex-fill" id="openConfirmBtn"><i class="bi bi-check-circle"></i> Confirm</button>
                <button type="button" class="btn btn-outline-danger btn-sm flex-fill" id="openDeclineBtn"><i class="bi bi-x-circle"></i> Decline</button>
            </div>
        `;
    }

    body.innerHTML = html;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('proposalDetailModal')).show();

    document.getElementById('openConfirmBtn')?.addEventListener('click', () => {
        ppActingId = p.id;
        document.getElementById('confirmName').value = p.proposed_name;
        document.getElementById('confirmPrice').value = '';
        document.getElementById('confirmQuantity').value = '';
        bootstrap.Modal.getInstance(document.getElementById('proposalDetailModal'))?.hide();
        new bootstrap.Modal(document.getElementById('confirmFormModal')).show();
    });
    document.getElementById('openDeclineBtn')?.addEventListener('click', () => {
        ppActingId = p.id;
        document.getElementById('declineReasonInput').value = '';
        bootstrap.Modal.getInstance(document.getElementById('proposalDetailModal'))?.hide();
        new bootstrap.Modal(document.getElementById('declineReasonModal')).show();
    });
}

function respondToProposal(action, data) {
    fetch('?page=api_supplier_respond_product_proposal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: ppActingId, action, ...data })
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.querySelectorAll('.modal.show').forEach(m => bootstrap.Modal.getInstance(m)?.hide());
                Swal.fire({ icon: 'success', title: action === 'confirm' ? 'Confirmed!' : 'Declined', text: res.message, timer: 1800, showConfirmButton: false });
                loadProposals();
            } else {
                Swal.fire('Error', res.message || 'Failed to save', 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Something went wrong. Please try again.', 'error'));
}

document.addEventListener('DOMContentLoaded', function () {
    loadProposals();

    document.getElementById('submitConfirmBtn').addEventListener('click', function () {
        const name = document.getElementById('confirmName').value.trim();
        const price = parseFloat(document.getElementById('confirmPrice').value) || 0;
        const quantity = parseInt(document.getElementById('confirmQuantity').value);

        if (!name || price <= 0 || isNaN(quantity) || quantity < 0) {
            Swal.fire('Missing information', 'Please fill in your product name, price, and available quantity.', 'warning');
            return;
        }
        respondToProposal('confirm', { supplier_product_name: name, supplier_price: price, supplier_quantity: quantity });
    });

    document.getElementById('confirmDeclineBtn').addEventListener('click', function () {
        const reason = document.getElementById('declineReasonInput').value.trim();
        if (!reason) {
            Swal.fire('Reason required', 'Please explain why you can\'t carry this.', 'warning');
            return;
        }
        respondToProposal('decline', { reason });
    });
});
