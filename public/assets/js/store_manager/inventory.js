// ============================================
// STORE MANAGER - INVENTORY (VIEW-ONLY, card grid)
// ============================================

console.log('✅ store_manager/inventory.js loaded');

let currentPage = 1;
let sortBy = 'name';
let sortDir = 'asc';
let allProducts = [];
let allCategories = [];

document.addEventListener('DOMContentLoaded', function () {
    if (window.__INITIAL_DATA__) {
        const data = window.__INITIAL_DATA__;
        allProducts = data.products || [];
        allCategories = data.categories || [];
        renderProducts(allProducts);
        renderPagination(data.pagination);
        renderStats(data.stats);
        const select = document.getElementById('categoryFilter');
        if (select) {
            allCategories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                select.appendChild(option);
            });
            window.refreshSearchableSelect?.(select);
        }
        populateEditCategorySelect();
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        loadInventory();
        loadCategories();
    }
    setupEventListeners();
    setupInventoryViewToggle();
    setupEditProductModal();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'category', type: 'select', elementId: 'categoryFilter', defaultValue: '0' },
            { key: 'stock', type: 'select', elementId: 'stockStatusFilter' },
            { key: 'search', type: 'search', elementId: 'searchInput' },
        ]);
    }
});

// ============================================
// GRID / ROWS VIEW TOGGLE (Modrinth-style, same mechanic as the
// Requisitions page -- see store_manager/requisitions.js)
// ============================================

const SM_INVENTORY_VIEW_KEY = 'sm_inventory_view';

function setupInventoryViewToggle() {
    const btn = document.getElementById('inventoryViewToggle');
    if (!btn) return;

    applyInventoryView(localStorage.getItem(SM_INVENTORY_VIEW_KEY) === 'rows' ? 'rows' : 'grid');

    btn.addEventListener('click', () => {
        const isRows = document.getElementById('sm-product-grid').classList.contains('sm-view-rows');
        applyInventoryView(isRows ? 'grid' : 'rows');
    });
}

function applyInventoryView(mode) {
    const isRows = mode === 'rows';
    const grid = document.getElementById('sm-product-grid');
    const btn = document.getElementById('inventoryViewToggle');
    if (grid) grid.classList.toggle('sm-view-rows', isRows);
    if (btn) {
        btn.classList.toggle('active', isRows);
        btn.innerHTML = `<i class="bi ${isRows ? 'bi-list-ul' : 'bi-grid-3x3-gap-fill'}"></i>`;
        btn.title = isRows ? 'Switch to grid view' : 'Switch to row view';
    }
    localStorage.setItem(SM_INVENTORY_VIEW_KEY, mode);
}

function setupEventListeners() {
    document.getElementById('searchInput')?.addEventListener('input', debounce(() => {
        currentPage = 1;
        loadInventory();
    }, 400));

    document.getElementById('categoryFilter')?.addEventListener('change', () => {
        currentPage = 1;
        loadInventory();
    });

    document.getElementById('stockStatusFilter')?.addEventListener('change', () => {
        currentPage = 1;
        loadInventory();
    });

    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        document.getElementById('searchInput').value = '';
        document.getElementById('categoryFilter').value = '0';
        document.getElementById('stockStatusFilter').value = '';
        sortBy = 'name';
        sortDir = 'asc';
        updateSortIndicators();
        currentPage = 1;
        loadInventory();
    });

    document.getElementById('sortByField')?.addEventListener('change', function () {
        sortBy = this.value;
        currentPage = 1;
        loadInventory();
    });

    document.getElementById('sortByDir')?.addEventListener('change', function () {
        sortDir = this.value;
        currentPage = 1;
        loadInventory();
    });

    updateSortIndicators();
}

function updateSortIndicators() {
    const fieldSelect = document.getElementById('sortByField');
    const dirSelect = document.getElementById('sortByDir');
    if (fieldSelect) {
        fieldSelect.value = sortBy;
        if (window.refreshSearchableSelect) window.refreshSearchableSelect(fieldSelect);
    }
    if (dirSelect) {
        dirSelect.value = sortDir;
        if (window.refreshSearchableSelect) window.refreshSearchableSelect(dirSelect);
    }
}

function debounce(fn, wait) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function loadCategories() {
    const select = document.getElementById('categoryFilter');

    fetch('?page=api_get_categories')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allCategories = data.data.categories || [];
                if (select) {
                    allCategories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.name;
                        select.appendChild(option);
                    });
                    // Options were added after the searchable-select widget already
                    // initialized on DOMContentLoaded — make it pick them up.
                    window.refreshSearchableSelect?.(select);
                }
                populateEditCategorySelect();
            }
        })
        .catch(err => console.error('Error loading categories:', err));
}

function loadInventory(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const category = document.getElementById('categoryFilter').value;
    const stockStatus = document.getElementById('stockStatusFilter').value;

    const params = new URLSearchParams({ p: page, limit: 30, sort_by: sortBy, sort_dir: sortDir });
    if (search) params.append('search', search);
    if (category && category !== '0') params.append('category', category);
    if (stockStatus) params.append('stock_status', stockStatus);

    const grid = document.getElementById('sm-product-grid');
    grid.innerHTML = `
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading inventory...</p>
        </div>
    `;

    fetch(`?page=api_store_manager_inventory&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allProducts = data.data.products || [];
                renderProducts(allProducts);
                renderPagination(data.data.pagination);
                renderStats(data.data.stats);
            } else {
                grid.innerHTML = `<div style="grid-column:1/-1;">${smErrorState(data.message || 'Failed to load inventory')}</div>`;
            }
        })
        .catch(() => {
            grid.innerHTML = `<div style="grid-column:1/-1;">${smErrorState()}</div>`;
        });
}

function renderProducts(products) {
    const grid = document.getElementById('sm-product-grid');
    if (!products || products.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1;">${smEmptyState('No products found matching your filters.', 'bi-inbox')}</div>`;
        return;
    }

    grid.innerHTML = products.map(product => {
        const discountValue = parseFloat(product.discount_value) || 0;
        const discountType = product.discount_type === 'fixed' ? 'fixed' : 'percent';
        const discountedPrice = discountType === 'fixed'
            ? Math.max(0, product.price - discountValue)
            : product.price * (1 - discountValue / 100);
        const price = discountValue > 0
            ? `<span class="sm-price-original">${smCurrency(product.price)}</span> ${smCurrency(discountedPrice)}`
            : smCurrency(product.price);
        const badgeText = discountType === 'fixed' ? `-${smCurrency(discountValue)}` : `-${discountValue}%`;
        const stock = parseInt(product.stock_quantity) || 0;
        const reorder = parseInt(product.reorder_level) || 0;
        return `
            <div class="sm-product-card" data-id="${product.id}">
                <div class="sm-product-image">
                    ${product.image_path
                        ? `<img src="/ShelfSense/public/${product.image_path}" alt="${escapeHtmlSM(product.name)}">`
                        : `<i class="bi bi-box-seam"></i>`
                    }
                    ${discountValue > 0 ? `<span class="sm-discount-badge">${badgeText}</span>` : ''}
                </div>
                <div class="sm-product-body">
                    <div class="sm-product-category">${escapeHtmlSM(product.category_name || 'Uncategorized')}</div>
                    <div class="sm-product-name" title="${escapeHtmlSM(product.name)}">${escapeHtmlSM(product.name)}</div>
                    <div class="sm-product-price">${price}</div>
                    <div class="sm-product-stock-row">
                        <span>Stock: <strong>${stock}</strong> (Reorder: ${reorder})</span>
                    </div>
                    <div class="mt-1 sm-product-badge-wrap">${smStockBadge(stock, reorder)}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 edit-product-btn" data-id="${product.id}"><i class="bi bi-pencil"></i> Edit</button>
                </div>
            </div>
        `;
    }).join('');

    grid.querySelectorAll('.edit-product-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditProductModal(btn.dataset.id));
    });
}

function renderPagination(pagination) {
    smRenderPagination(
        document.getElementById('paginationContainer'),
        document.getElementById('tableInfo'),
        pagination,
        'products',
        (page) => loadInventory(page)
    );
}

function renderStats(stats) {
    document.getElementById('statTotal').textContent = stats.total_products || 0;
    document.getElementById('statInStock').textContent = stats.in_stock_count || 0;
    document.getElementById('statLowStock').textContent = stats.low_stock_count || 0;
    document.getElementById('statOutOfStock').textContent = stats.out_of_stock_count || 0;
}

// ============================================
// EDIT PRODUCT MODAL (price / category / image / discount)
// ============================================

let editProductSelectedFile = null;

function populateEditCategorySelect() {
    const select = document.getElementById('editProductCategory');
    if (!select) return;
    select.querySelectorAll('option:not(:first-child)').forEach(opt => opt.remove());
    allCategories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        select.appendChild(option);
    });
    window.refreshSearchableSelect?.(select);
}

function openEditProductModal(id) {
    const product = allProducts.find(p => String(p.id) === String(id));
    if (!product) return;

    editProductSelectedFile = null;
    document.getElementById('editProductId').value = product.id;
    document.getElementById('editProductName').value = product.name || '';
    document.getElementById('editProductDescription').value = product.description || '';
    document.getElementById('editProductPrice').value = product.price;
    document.getElementById('editProductCost').value = product.cost ?? '';
    document.getElementById('editProductDiscount').value = parseFloat(product.discount_value) || 0;
    document.getElementById('editProductDiscountType').value = product.discount_type === 'fixed' ? 'fixed' : 'percent';
    document.getElementById('editProductStock').value = product.stock_quantity ?? 0;
    document.getElementById('editProductReorder').value = product.reorder_level ?? 5;
    document.getElementById('editProductStatus').value = product.is_active == 0 ? '0' : '1';
    document.getElementById('editProductImage').value = '';

    const categorySelect = document.getElementById('editProductCategory');
    categorySelect.value = product.category_id || '';
    window.refreshSearchableSelect?.(categorySelect);

    const preview = document.getElementById('editProductImagePreview');
    const placeholder = document.getElementById('editProductImagePlaceholder');
    if (product.image_path) {
        preview.src = `/ShelfSense/public/${product.image_path}`;
        preview.style.display = 'inline-block';
        placeholder.style.display = 'none';
    } else {
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }

    updateEditDiscountPreview();
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function updateEditDiscountPreview() {
    const price = parseFloat(document.getElementById('editProductPrice').value) || 0;
    const discount = parseFloat(document.getElementById('editProductDiscount').value) || 0;
    const type = document.getElementById('editProductDiscountType').value;
    const preview = document.getElementById('editProductDiscountPreview');
    if (!preview) return;
    if (discount > 0 && price > 0) {
        const discounted = type === 'fixed' ? Math.max(0, price - discount) : price * (1 - discount / 100);
        preview.textContent = `Customers will pay ${smCurrency(discounted)} (was ${smCurrency(price)}).`;
    } else {
        preview.textContent = '';
    }
}

function setupEditProductModal() {
    document.getElementById('editProductPrice')?.addEventListener('input', updateEditDiscountPreview);
    document.getElementById('editProductDiscount')?.addEventListener('input', updateEditDiscountPreview);
    document.getElementById('editProductDiscountType')?.addEventListener('change', updateEditDiscountPreview);

    document.getElementById('editProductImage')?.addEventListener('change', function () {
        editProductSelectedFile = this.files && this.files[0] ? this.files[0] : null;
        if (editProductSelectedFile) {
            const preview = document.getElementById('editProductImagePreview');
            const placeholder = document.getElementById('editProductImagePlaceholder');
            preview.src = URL.createObjectURL(editProductSelectedFile);
            preview.style.display = 'inline-block';
            placeholder.style.display = 'none';
        }
    });

    document.getElementById('editProductForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        saveEditedProduct();
    });
}

function saveEditedProduct() {
    const id = parseInt(document.getElementById('editProductId').value);
    const data = {
        id,
        name: document.getElementById('editProductName').value.trim(),
        description: document.getElementById('editProductDescription').value.trim(),
        category_id: document.getElementById('editProductCategory').value ? parseInt(document.getElementById('editProductCategory').value) : null,
        price: parseFloat(document.getElementById('editProductPrice').value),
        cost: document.getElementById('editProductCost').value !== '' ? parseFloat(document.getElementById('editProductCost').value) : null,
        discount_value: parseFloat(document.getElementById('editProductDiscount').value) || 0,
        discount_type: document.getElementById('editProductDiscountType').value === 'fixed' ? 'fixed' : 'percent',
        stock_quantity: parseInt(document.getElementById('editProductStock').value),
        reorder_level: parseInt(document.getElementById('editProductReorder').value) || 0,
        is_active: parseInt(document.getElementById('editProductStatus').value)
    };

    if (!data.name || !(data.price > 0)) {
        Swal.fire({ icon: 'warning', title: 'Required', text: 'Name and a price greater than zero are required.' });
        return;
    }
    if (data.discount_value < 0) {
        Swal.fire({ icon: 'warning', title: 'Invalid discount', text: 'Discount cannot be negative.' });
        return;
    }
    if (data.discount_type === 'percent' && data.discount_value > 100) {
        Swal.fire({ icon: 'warning', title: 'Invalid discount', text: 'Percentage discount cannot exceed 100.' });
        return;
    }
    if (data.discount_type === 'fixed' && data.discount_value > data.price) {
        Swal.fire({ icon: 'warning', title: 'Invalid discount', text: 'Fixed discount cannot exceed the price.' });
        return;
    }
    if (!(data.stock_quantity >= 0)) {
        Swal.fire({ icon: 'warning', title: 'Required', text: 'Stock quantity is required.' });
        return;
    }

    const submitBtn = document.querySelector('#editProductForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('?page=api_store_manager_update_product', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (!result.success) {
            throw new Error(result.message || 'Failed to update product.');
        }
        if (editProductSelectedFile) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('image', editProductSelectedFile);
            return fetch('?page=api_store_manager_upload_product_image', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(imgResult => {
                    if (!imgResult.success) {
                        throw new Error(imgResult.message || 'Product saved, but the image failed to upload.');
                    }
                });
        }
    })
    .then(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Product';
        Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false });
        bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
        loadInventory(currentPage);
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Product';
        Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Something went wrong.' });
    });
}
