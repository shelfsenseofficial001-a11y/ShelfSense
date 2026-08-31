// ============================================
// POS - ORDER HISTORY
// ============================================

console.log('✅ orders.js loaded');

document.addEventListener("DOMContentLoaded", function() {
    loadOrders();
    const todayStr = new Date().toISOString().split("T")[0];
    document.getElementById("filterDate").value = todayStr;

    // Deep link from the Checkout page's "View More" on a Recent Order card
    // (?page=pos_orders&view=<order_id>) -- opens that order's detail drawer
    // immediately instead of making the cashier hunt for it in the list.
    const deepLinkParams = new URLSearchParams(window.location.search);
    const viewOrderId = deepLinkParams.get('view');
    if (viewOrderId) {
        viewOrder(viewOrderId);
        deepLinkParams.delete('view');
        window.history.replaceState(null, '', window.location.pathname + '?' + deepLinkParams.toString());
    }

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'search', type: 'search', elementId: 'searchOrderInput' },
            { key: 'status', type: 'select', elementId: 'filterStatus' },
            { key: 'date', type: 'date', elementId: 'filterDate', defaultValue: todayStr, labelPrefix: 'Date' },
        ], { applyButtonId: 'applyFiltersBtn' });
    }

    document.getElementById("applyFiltersBtn").addEventListener("click", loadOrders);
    document.getElementById("refreshOrdersBtn").addEventListener("click", function() {
        document.getElementById("searchOrderInput").value = "";
        document.getElementById("filterStatus").value = "";
        document.getElementById("filterDate").value = new Date().toISOString().split("T")[0];
        loadOrders();
    });
});

let currentPage = 1;

function loadOrders(page = 1) {
    currentPage = page;
    const search = document.getElementById("searchOrderInput").value.trim();
    const status = document.getElementById("filterStatus").value;
    const date = document.getElementById("filterDate").value;
    
    const params = new URLSearchParams({
        p: page,
        limit: 20
    });
    if (search) params.append("search", search);
    if (status) params.append("status", status);
    if (date) params.append("date", date);
    
    const tbody = document.getElementById("ordersTableBody");
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading orders...</p>
            </td>
        </tr>
    `;
    
    fetch(`?page=api_get_orders&${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderOrders(data.data.orders);
                renderPagination(data.data.pagination);
                renderStats(data.data.orders);
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            ${data.message || "Failed to load orders"}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error("Error loading orders:", error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-4">
                        An error occurred. Please try again.
                    </td>
                </tr>
            `;
        });
}

function renderOrders(orders) {
    const tbody = document.getElementById("ordersTableBody");
    if (!orders || orders.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No orders found
                </td>
            </tr>
        `;
        return;
    }
    
    let html = "";
    orders.forEach(order => {
        const statusBadge = order.status === "completed" 
            ? '<span class="badge bg-success">Completed</span>'
            : '<span class="badge bg-danger">Voided</span>';
        const date = new Date(order.created_at).toLocaleString();
        
        html += `
            <tr class="order-row" data-id="${order.id}">
                <td><strong>${order.order_number}</strong></td>
                <td>${date}</td>
                <td>${order.item_count || 0}</td>
                <td class="fw-bold">₱${parseFloat(order.total).toFixed(2)}</td>
                <td><span class="badge bg-info">${order.payment_method}</span></td>
                <td>${statusBadge}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-order-btn" data-id="${order.id}">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${order.status === "completed" ? `
                        <button class="btn btn-sm btn-outline-danger void-order-btn" data-id="${order.id}">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ` : ""}
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    document.querySelectorAll(".view-order-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.stopPropagation();
            viewOrder(this.dataset.id);
        });
    });
    
    document.querySelectorAll(".void-order-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.stopPropagation();
            voidOrder(this.dataset.id);
        });
    });
    
    document.querySelectorAll(".order-row").forEach(row => {
        row.addEventListener("click", function() {
            viewOrder(this.dataset.id);
        });
    });
}

function renderPagination(pagination) {
    const container = document.getElementById("paginationContainer");
    const info = document.getElementById("tableInfo");
    
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = `<li class="page-item disabled"><span class="page-link">1</span></li>`;
        info.textContent = `Showing ${pagination?.totalRecords || 0} orders`;
        return;
    }
    
    info.textContent = `Showing page ${pagination.currentPage} of ${pagination.totalPages} (${pagination.totalRecords} total)`;
    
    let html = "";
    if (pagination.currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
    }
    
    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 2);
    
    if (start > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let i = start; i <= end; i++) {
        if (i === pagination.currentPage) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
    }
    
    if (end < pagination.totalPages) {
        if (end < pagination.totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
    }
    
    if (pagination.currentPage < pagination.totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.currentPage + 1}">»</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">»</span></li>`;
    }
    
    container.innerHTML = html;
    
    container.querySelectorAll(".page-link[data-page]").forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            loadOrders(page);
        });
    });
}

function renderStats(orders) {
    const total = orders.length;
    const completed = orders.filter(o => o.status === "completed").length;
    const voided = orders.filter(o => o.status === "voided").length;
    const totalSales = orders
        .filter(o => o.status === "completed")
        .reduce((sum, o) => sum + parseFloat(o.total), 0);
    
    document.getElementById("statTotal").textContent = total;
    document.getElementById("statCompleted").textContent = completed;
    document.getElementById("statVoided").textContent = voided;
    document.getElementById("statTotalSales").textContent = "₱" + totalSales.toFixed(2);
}

function viewOrder(orderId) {
    const modal = document.getElementById("orderDetailModal");
    const body = document.getElementById("orderDetailBody");
    const reprintBtn = document.getElementById("reprintReceiptBtn");
    const voidBtn = document.getElementById("voidOrderBtn");
    
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;
    reprintBtn.style.display = "none";
    voidBtn.style.display = "none";
    
    bootstrap.Offcanvas.getOrCreateInstance(modal).show();

    fetch(`?page=api_get_order&id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderOrderDetail(data.data.order);
                if (data.data.order.status === "completed") {
                    reprintBtn.style.display = "inline-block";
                    voidBtn.style.display = "inline-block";
                    voidBtn.dataset.id = orderId;
                }
            } else {
                body.innerHTML = `
                    <div class="text-center text-danger py-4">
                        ${data.message || "Failed to load order"}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error("Error loading order details:", error);
            body.innerHTML = `
                <div class="text-center text-danger py-4">
                    An error occurred. Please try again.
                </div>
            `;
        });
}

function renderOrderDetail(order) {
    const body = document.getElementById("orderDetailBody");
    const date = new Date(order.created_at).toLocaleString();
    const statusBadge = order.status === "completed"
        ? '<span class="badge bg-success">Completed</span>'
        : '<span class="badge bg-danger">Voided</span>';
    
    let itemsHtml = "";
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    <td class="fw-bold">₱${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
            `;
        });
    }
    
    body.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Order #:</strong> ${order.order_number}</p>
                <p><strong>Date:</strong> ${date}</p>
                <p><strong>Cashier:</strong> ${order.first_name} ${order.last_name}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Payment Method:</strong> <span class="badge bg-info">${order.payment_method}</span></p>
                <p><strong>Status:</strong> ${statusBadge}</p>
                ${order.payment_reference ? `<p><strong>Reference:</strong> ${order.payment_reference}</p>` : ""}
                ${order.void_reason ? `<p><strong>Void Reason:</strong> ${order.void_reason}</p>` : ""}
            </div>
        </div>
        
        <h6 class="mt-3">Items</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                        <td>₱${parseFloat(order.subtotal).toFixed(2)}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total:</td>
                        <td class="fw-bold text-success">₱${parseFloat(order.total).toFixed(2)}</td>
                    </tr>
                    ${order.amount_paid > 0 ? `
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Amount Paid:</td>
                            <td>₱${parseFloat(order.amount_paid).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Change:</td>
                            <td class="text-danger">₱${parseFloat(order.change_amount).toFixed(2)}</td>
                        </tr>
                    ` : ""}
                </tfoot>
            </table>
        </div>
        ${order.notes ? `<p><strong>Notes:</strong> ${order.notes}</p>` : ""}
    `;
}

function voidOrder(orderId) {
    Swal.fire({
        title: "Void Order?",
        text: "This will cancel the order and refund the stock. This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        confirmButtonText: "Yes, Void Order",
        cancelButtonText: "Cancel",
        input: "textarea",
        inputPlaceholder: "Reason for voiding...",
        inputAttributes: { rows: 3, maxlength: 255 }
    }).then(result => {
        if (result.isConfirmed && result.value) {
            const reason = result.value.trim();
            if (!reason) {
                Swal.fire({ icon: "warning", title: "Reason Required", text: "Please provide a reason for voiding." });
                return;
            }
            
            fetch("?page=api_void_order", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ order_id: orderId, reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Order Voided",
                        text: "The order has been voided and stock has been refunded.",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    bootstrap.Offcanvas.getInstance(document.getElementById("orderDetailModal")).hide();
                    loadOrders(currentPage);
                } else {
                    Swal.fire({ icon: "error", title: "Void Failed", text: data.message || "Please try again." });
                }
            })
            .catch(error => {
                Swal.fire({ icon: "error", title: "Error", text: "Something went wrong." });
            });
        }
    });
}

document.getElementById("reprintReceiptBtn").addEventListener("click", function() {
    Swal.fire({
        icon: "info",
        title: "Reprint Receipt",
        text: "Receipt reprint functionality will be available soon.",
        confirmButtonText: "OK"
    });
});

document.getElementById("voidOrderBtn").addEventListener("click", function() {
    voidOrder(this.dataset.id);
});