// ============================================
// POS — CASHIER BUDGET / CASH-OUT
// ============================================

console.log('✅ pos/budget.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    loadBudgetStatus();
});

function loadBudgetStatus() {
    const container = document.getElementById('posBudgetContent');

    fetch('?page=api_pos_get_budget_status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderBudget(data.data);
            } else {
                container.innerHTML = `<div class="text-danger text-center py-5">${escapeHtml(data.message || 'Failed to load budget')}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading budget:', error);
            container.innerHTML = `<div class="text-danger text-center py-5">An error occurred. Please refresh the page.</div>`;
        });
}

function renderBudget(data) {
    const container = document.getElementById('posBudgetContent');
    const allocation = data.allocation;
    const liveSales = data.live_sales;

    if (!allocation) {
        container.innerHTML = `
            <div class="modern-card p-4 text-center">
                <i class="bi bi-cash fs-1 text-muted d-block mb-3"></i>
                <h6 class="fw-bold mb-2">No Budget Allocated</h6>
                <p class="text-muted mb-0">Your Store Manager hasn't allocated a register budget to you yet.<br>Please see them before ringing up sales.</p>
            </div>
        `;
        return;
    }

    const cashSales = liveSales ? liveSales.cash_sales : 0;
    const onlineSales = liveSales ? liveSales.online_sales : 0;
    const orderCount = liveSales ? liveSales.order_count : 0;
    const expectedPull = (parseFloat(allocation.initial_budget) + cashSales).toFixed(2);

    container.innerHTML = `
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="modern-card p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-cash-stack text-yellow me-2"></i>Current Budget Session</h6>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>Initial Budget</span>
                        <strong>${currency(allocation.initial_budget)}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>Cash Sales <small class="text-muted">(${orderCount} order${orderCount === 1 ? '' : 's'})</small></span>
                        <strong>${currency(cashSales)}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>Online Sales <small class="text-muted">card / gcash / paymaya</small></span>
                        <strong class="text-muted">${currency(onlineSales)}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3">
                        <span class="fw-semibold">Cash to Pull Out on Cash-Out</span>
                        <strong class="text-success fs-5">${currency(expectedPull)}</strong>
                    </div>
                    <div class="text-muted small mt-2">Opened ${formatDate(allocation.opened_at)}</div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="modern-card p-4 text-center">
                    <i class="bi bi-box-arrow-right fs-1 text-yellow d-block mb-3"></i>
                    <h6 class="fw-bold mb-2">Cash Out</h6>
                    <p class="text-muted small">Pull the initial budget and all cash sales from the drawer. This closes your register session and is required before logging out.</p>
                    <button class="btn btn-yellow-primary w-100" id="posCashOutBtn">
                        <i class="bi bi-check-circle me-1"></i> Cash Out Now
                    </button>
                </div>
            </div>
        </div>
    `;

    document.getElementById('posCashOutBtn').addEventListener('click', handleCashOut);
}

function handleCashOut() {
    const btn = document.getElementById('posCashOutBtn');

    const confirmAndCashOut = () => {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Cashing out...';

        fetch('?page=api_pos_cash_out', { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const a = data.data.allocation;
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cashed Out',
                            html: `Total pulled: <strong>${currency(a.total_pulled)}</strong>`,
                        }).then(() => loadBudgetStatus());
                    } else {
                        alert(`Cashed out. Total pulled: ${currency(a.total_pulled)}`);
                        loadBudgetStatus();
                    }
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Cash Out Now';
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Could not cash out' });
                    } else {
                        alert(data.message || 'Could not cash out');
                    }
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Cash Out Now';
            });
    };

    if (window.Swal) {
        Swal.fire({
            icon: 'question',
            title: 'Cash out now?',
            text: 'This will close your current register session.',
            showCancelButton: true,
            confirmButtonText: 'Yes, cash out',
            confirmButtonColor: '#eeab1a'
        }).then(result => {
            if (result.isConfirmed) confirmAndCashOut();
        });
    } else if (confirm('Cash out now? This will close your current register session.')) {
        confirmAndCashOut();
    }
}

function currency(amount) {
    return '₱' + (parseFloat(amount) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return escapeHtml(dateStr);
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}
