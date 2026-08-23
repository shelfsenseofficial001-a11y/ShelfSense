// Dashboard JS - POS
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

function loadDashboardData() {
    fetch('?page=api_get_daily_sales')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const d = data.data;
                document.getElementById('todaySales').textContent = '₱' + d.today.total_sales.toFixed(2);
                document.getElementById('todayTransactions').textContent = d.today.transaction_count;
                
                if (d.top_product) {
                    document.getElementById('topProduct').textContent = d.top_product.name;
                    document.getElementById('topProductQty').textContent = d.top_product.quantity + ' sold';
                } else {
                    document.getElementById('topProduct').textContent = 'No sales yet';
                    document.getElementById('topProductQty').textContent = '';
                }
                
                renderRecentTransactions(d.recent_transactions);
            }
        })
        .catch(error => {
            console.error('Error loading dashboard:', error);
            document.getElementById('recentTransactions').innerHTML = `
                <div class="text-center text-danger py-4">
                    Failed to load dashboard data.
                </div>
            `;
        });
}

function renderRecentTransactions(transactions) {
    const container = document.getElementById('recentTransactions');
    if (!transactions || transactions.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                No transactions today
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    transactions.forEach(order => {
        const time = new Date(order.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        html += `
            <tr>
                <td><strong>${order.order_number}</strong></td>
                <td>${order.item_count || 0} items</td>
                <td class="fw-bold text-success">₱${parseFloat(order.total).toFixed(2)}</td>
                <td><span class="badge bg-info">${order.payment_method}</span></td>
                <td>${time}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-order-btn" data-id="${order.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
    
    document.querySelectorAll('.view-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            window.location.href = `?page=pos_orders&view=${this.dataset.id}`;
        });
    });
}