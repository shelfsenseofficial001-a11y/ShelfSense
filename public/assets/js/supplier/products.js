// ============================================
// SUPPLIER - PRODUCT CATALOG (supplier_products)
// ============================================

console.log('✅ supplier/products.js loaded');

let currentPage = 1;
let allProducts = [];

document.addEventListener('DOMContentLoaded', function () {
    loadProducts();
    setupEventListeners();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'search', type: 'search', elementId: 'searchInput' },
            { key: 'status', type: 'select', elementId: 'statusFilter' },
        ]);
    }
});

function setupEventListeners() {
    document.getElementById('searchInput')?.addEventListener('input', debounceSpp(() => {
        currentPage = 1;
        loadProducts();
    }, 400));

    document.getElementById('statusFilter')?.addEventListener('change', () => {
        currentPage = 1;
        loadProducts();
    });

    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        currentPage = 1;
        loadProducts();
    });

    document.getElementById('addProductBtn')?.addEventListener('click', function () {
        openProductModal();
    });

    document.getElementById('productForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        saveProduct();
    });

    document.getElementById('confirmDeleteBtn')?.addEventListener('click', function () {
        deleteProduct();
    });
}

function debounceSpp(fn, wait) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function loadProducts(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;

    const params = new URLSearchParams({ p: page, limit: 12 });
    if (search) params.append('search', search);
    if (status) params.append('status', status);

    const grid = document.getElementById('sp-product-grid');
    grid.innerHTML = `<div class="text-center py-4" style="grid-column:1/-1;"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading products...</p></div>`;

    fetch(`?page=api_supplier_get_products&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allProducts = data.data.products || [];
                renderProducts(allProducts);
                spRenderPagination(
                    document.getElementById('paginationContainer'),
                    document.getElementById('tableInfo'),
                    data.data.pagination,
                    'products',
                    (p) => loadProducts(p)
                );
                renderStats(data.data.stats);
            } else {
                grid.innerHTML = spErrorState(data.message || 'Failed to load products');
            }
        })
        .catch(() => { grid.innerHTML = spErrorState(); });
}

function renderStats(stats) {
    if (!stats) return;
    document.getElementById('statTotal').textContent = stats.total ?? 0;
    document.getElementById('statActive').textContent = stats.active ?? 0;
    document.getElementById('statInactive').textContent = stats.inactive ?? 0;
}

function renderProducts(products) {
    const grid = document.getElementById('sp-product-grid');

    if (!products || products.length === 0) {
        grid.innerHTML = spEmptyState('No products in your catalog yet.', 'bi-box-seam');
        return;
    }

    grid.innerHTML = products.map(product => {
        const statusBadge = product.is_active
            ? '<span class="sp-status-badge status-verified">Active</span>'
            : '<span class="sp-status-badge status-rejected">Inactive</span>';
        return `
            <div class="sp-product-card" data-id="${product.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="sp-product-name">${escapeHtmlSP(product.name)}</div>
                    ${statusBadge}
                </div>
                <div class="sp-product-desc">${escapeHtmlSP(product.description || 'No description')}</div>
                <div class="sp-product-price">${spCurrency(product.price)}</div>
                <div class="sp-mapping-warning mt-2">
                    <i class="bi bi-exclamation-triangle"></i> Name must match the Store product name for automatic mapping.
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-primary edit-product-btn" data-id="${product.id}"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn btn-sm btn-outline-danger delete-product-btn" data-id="${product.id}" data-name="${escapeHtmlSP(product.name)}"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        `;
    }).join('');

    grid.querySelectorAll('.edit-product-btn').forEach(btn => {
        btn.addEventListener('click', () => openProductModal(btn.dataset.id));
    });
    grid.querySelectorAll('.delete-product-btn').forEach(btn => {
        btn.addEventListener('click', () => openDeleteModal(btn.dataset.id, btn.dataset.name));
    });
}

function openProductModal(id = null) {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const title = document.getElementById('productModalTitle');

    form.reset();
    document.getElementById('productId').value = '';
    document.getElementById('productStatus').value = '1';
    title.textContent = 'Add Product to Catalog';

    if (id) {
        title.textContent = 'Edit Product';
        const product = allProducts.find(p => p.id == id);
        if (product) {
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productStatus').value = product.is_active ? '1' : '0';
        }
    }

    new bootstrap.Modal(modal).show();
}

function saveProduct() {
    const id = document.getElementById('productId').value;
    const data = {
        name: document.getElementById('productName').value.trim(),
        description: document.getElementById('productDescription').value.trim(),
        price: parseFloat(document.getElementById('productPrice').value),
        is_active: parseInt(document.getElementById('productStatus').value)
    };

    if (!data.name || !(data.price > 0)) {
        Swal.fire({ icon: 'warning', title: 'Required', text: 'Name and a price greater than zero are required.' });
        return;
    }

    const url = id ? '?page=api_supplier_update_product' : '?page=api_supplier_create_product';
    if (id) data.id = parseInt(id);

    const submitBtn = document.querySelector('#productForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Product';

        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Success!', timer: 1500, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
            loadProducts(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to save product.' });
        }
    })
    .catch(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Product';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}

function openDeleteModal(id, name) {
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('confirmDeleteBtn').dataset.id = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function deleteProduct() {
    const id = document.getElementById('confirmDeleteBtn').dataset.id;
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch('?page=api_supplier_delete_product', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(id) })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = 'Delete';

        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Deleted!', timer: 1500, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            loadProducts(currentPage);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to delete product.' });
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = 'Delete';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}
