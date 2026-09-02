// ============================================
// OWNER — POS ACCOUNTS
// ============================================

console.log('✅ owner/pos_accounts.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    load();

    document.getElementById('createPosForm').addEventListener('submit', handleCreate);

    document.getElementById('posPinCreate').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
    });
});

function escapeHtmlPos(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function load() {
    fetch('?page=api_owner_get_pos_accounts')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderStoreManagerSelect(data.data.available_store_managers);
                renderTable(data.data.registers);
            } else {
                document.getElementById('posAccountsTable').innerHTML = `<div class="text-danger small">${escapeHtmlPos(data.message)}</div>`;
            }
        })
        .catch(() => {
            document.getElementById('posAccountsTable').innerHTML = `<div class="text-danger small">Failed to load POS accounts</div>`;
        });
}

function renderStoreManagerSelect(storeManagers) {
    const select = document.getElementById('posStoreManager');
    const form = document.getElementById('createPosForm');

    if (!storeManagers || storeManagers.length === 0) {
        select.innerHTML = '<option value="">No store managers found</option>';
        select.disabled = true;
        form.querySelector('button[type=submit]').disabled = true;
        return;
    }

    select.disabled = false;
    form.querySelector('button[type=submit]').disabled = false;
    select.innerHTML = '<option value="">Select...</option>' + storeManagers.map(sm =>
        `<option value="${sm.user_id}">${escapeHtmlPos(sm.first_name)} ${escapeHtmlPos(sm.last_name)} (${escapeHtmlPos(sm.employee_number)})</option>`
    ).join('');
}

function renderTable(registers) {
    const container = document.getElementById('posAccountsTable');
    if (!registers || registers.length === 0) {
        container.innerHTML = `<div class="text-center text-muted small py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No registers yet.</div>`;
        return;
    }

    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead><tr><th>Store Manager</th><th>Register</th><th>POS ID</th><th>Status</th><th>Provisioned</th><th></th></tr></thead>
                <tbody>
                    ${registers.map(r => `
                        <tr>
                            <td>${escapeHtmlPos(r.first_name)} ${escapeHtmlPos(r.last_name)} <span class="text-muted small">(${escapeHtmlPos(r.employee_number)})</span></td>
                            <td>${escapeHtmlPos(r.name)}</td>
                            <td>${r.pos_id ? `<code>${escapeHtmlPos(r.pos_id)}</code>` : '<span class="text-muted small">Not provisioned</span>'}</td>
                            <td><span class="badge ${r.status === 'open' ? 'bg-success' : 'bg-secondary'}">${r.status === 'open' ? 'Open' : 'Closed'}</span></td>
                            <td class="small text-muted">${r.pos_created_at ? new Date(r.pos_created_at.replace(' ', 'T')).toLocaleDateString() : '—'}</td>
                            <td>${r.pos_id ? `<button type="button" class="btn btn-yellow-outline btn-sm reset-pin-btn" data-id="${r.id}" data-name="${escapeHtmlPos(r.first_name)} ${escapeHtmlPos(r.last_name)}"><i class="bi bi-key"></i> Reset PIN</button>` : ''}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    container.querySelectorAll('.reset-pin-btn').forEach(btn => {
        btn.addEventListener('click', () => handleResetPin(btn.dataset.id, btn.dataset.name));
    });
}

function handleCreate(e) {
    e.preventDefault();
    const storeManagerId = document.getElementById('posStoreManager').value;
    const pin = document.getElementById('posPinCreate').value;
    const msgEl = document.getElementById('createPosMessage');
    const btn = document.getElementById('createPosBtn');

    if (!storeManagerId) {
        msgEl.innerHTML = `<div class="text-danger small">Please select a store manager.</div>`;
        return;
    }
    if (!/^\d{4}$/.test(pin)) {
        msgEl.innerHTML = `<div class="text-danger small">PIN must be exactly 4 digits.</div>`;
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';

    fetch('?page=api_owner_create_pos_account', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ store_manager_id: storeManagerId, pin })
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-plus me-1"></i> Create POS Account';
            if (data.success) {
                document.getElementById('createPosForm').reset();
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'POS Account Created',
                        html: `POS ID: <strong>${escapeHtmlPos(data.data.register.pos_id)}</strong><br>Share this with the store along with the PIN you set.`,
                    });
                }
                msgEl.innerHTML = '';
                load();
            } else {
                msgEl.innerHTML = `<div class="text-danger small">${escapeHtmlPos(data.message)}</div>`;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-shield-plus me-1"></i> Create POS Account';
            msgEl.innerHTML = `<div class="text-danger small">Something went wrong.</div>`;
        });
}

function handleResetPin(registerId, name) {
    if (!window.Swal) return;
    Swal.fire({
        title: `Reset PIN for ${name}`,
        input: 'text',
        inputAttributes: { maxlength: 4, inputmode: 'numeric', pattern: '[0-9]*' },
        inputPlaceholder: 'New 4-digit PIN',
        showCancelButton: true,
        confirmButtonText: 'Reset PIN',
        confirmButtonColor: '#f45b35',
        preConfirm: (pin) => {
            if (!/^\d{4}$/.test(pin)) {
                Swal.showValidationMessage('PIN must be exactly 4 digits');
                return false;
            }
            return pin;
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('?page=api_owner_reset_pos_pin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ register_id: registerId, pin: result.value })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'PIN Reset', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message });
                }
            });
    });
}
