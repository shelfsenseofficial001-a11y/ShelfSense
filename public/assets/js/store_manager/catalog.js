// ============================================
// STORE MANAGER - CATALOG & DEALS (product catalog + discount pricing)
// Same edit modal/mechanics as Inventory (see store_manager/inventory.js),
// but framed around pricing/deals rather than stock levels -- no stock
// stats or per-card stock line here.
// ============================================

console.log('✅ store_manager/catalog.js loaded');

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
        loadCatalog();
        loadCategories();
    }
    setupEventListeners();
    setupCatalogViewToggle();
    setupEditProductModal();
    setupCreateDealModal();
    loadDeals();

    if (window.ShelfSenseFilterChips) {
        window.ShelfSenseFilterChips.init('activeFilterChips', [
            { key: 'category', type: 'select', elementId: 'categoryFilter', defaultValue: '0' },
            { key: 'deal', type: 'select', elementId: 'dealStatusFilter' },
            { key: 'search', type: 'search', elementId: 'searchInput' },
        ]);
    }
});

// ============================================
// GRID / ROWS VIEW TOGGLE
// ============================================

const SM_CATALOG_VIEW_KEY = 'sm_catalog_view';

function setupCatalogViewToggle() {
    const btn = document.getElementById('catalogViewToggle');
    if (!btn) return;

    applyCatalogView(localStorage.getItem(SM_CATALOG_VIEW_KEY) === 'rows' ? 'rows' : 'grid');

    btn.addEventListener('click', () => {
        const isRows = document.getElementById('sm-catalog-grid').classList.contains('sm-view-rows');
        applyCatalogView(isRows ? 'grid' : 'rows');
    });
}

function applyCatalogView(mode) {
    const isRows = mode === 'rows';
    const grid = document.getElementById('sm-catalog-grid');
    const btn = document.getElementById('catalogViewToggle');
    if (grid) grid.classList.toggle('sm-view-rows', isRows);
    if (btn) {
        btn.classList.toggle('active', isRows);
        btn.innerHTML = `<i class="bi ${isRows ? 'bi-list-ul' : 'bi-grid-3x3-gap-fill'}"></i>`;
        btn.title = isRows ? 'Switch to grid view' : 'Switch to row view';
    }
    localStorage.setItem(SM_CATALOG_VIEW_KEY, mode);
}

function setupEventListeners() {
    document.getElementById('searchInput')?.addEventListener('input', debounce(() => {
        currentPage = 1;
        loadCatalog();
    }, 400));

    document.getElementById('categoryFilter')?.addEventListener('change', () => {
        currentPage = 1;
        loadCatalog();
    });

    document.getElementById('dealStatusFilter')?.addEventListener('change', () => {
        currentPage = 1;
        loadCatalog();
    });

    document.getElementById('refreshBtn')?.addEventListener('click', function () {
        document.getElementById('searchInput').value = '';
        document.getElementById('categoryFilter').value = '0';
        document.getElementById('dealStatusFilter').value = '';
        sortBy = 'name';
        sortDir = 'asc';
        updateSortIndicators();
        currentPage = 1;
        loadCatalog();
    });

    document.getElementById('sortByField')?.addEventListener('change', function () {
        sortBy = this.value;
        currentPage = 1;
        loadCatalog();
    });

    document.getElementById('sortByDir')?.addEventListener('change', function () {
        sortDir = this.value;
        currentPage = 1;
        loadCatalog();
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
                    window.refreshSearchableSelect?.(select);
                }
                populateEditCategorySelect();
            }
        })
        .catch(err => console.error('Error loading categories:', err));
}

function loadCatalog(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value.trim();
    const category = document.getElementById('categoryFilter').value;
    const dealStatus = document.getElementById('dealStatusFilter').value;

    const params = new URLSearchParams({ p: page, limit: 30, sort_by: sortBy, sort_dir: sortDir });
    if (search) params.append('search', search);
    if (category && category !== '0') params.append('category', category);
    if (dealStatus) params.append('deal_status', dealStatus);

    const grid = document.getElementById('sm-catalog-grid');
    grid.innerHTML = `
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading catalog...</p>
        </div>
    `;

    fetch(`?page=api_store_manager_catalog&${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allProducts = data.data.products || [];
                renderProducts(allProducts);
                renderPagination(data.data.pagination);
                renderStats(data.data.stats);
            } else {
                grid.innerHTML = `<div style="grid-column:1/-1;">${smErrorState(data.message || 'Failed to load catalog')}</div>`;
            }
        })
        .catch(() => {
            grid.innerHTML = `<div style="grid-column:1/-1;">${smErrorState()}</div>`;
        });
}

function renderProducts(products) {
    const grid = document.getElementById('sm-catalog-grid');
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
        (page) => loadCatalog(page)
    );
}

function renderStats(stats) {
    document.getElementById('statTotal').textContent = stats.total_products || 0;
    document.getElementById('statOnSale').textContent = stats.on_sale_count || 0;
    document.getElementById('statRegular').textContent = stats.regular_count || 0;
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
        discount_type: document.getElementById('editProductDiscountType').value === 'fixed' ? 'fixed' : 'percent'
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

    const submitBtn = document.querySelector('#editProductForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('?page=api_store_manager_update_catalog_product', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (!result.success) {
            throw new Error(result.message || 'Failed to update catalog listing.');
        }
        if (editProductSelectedFile) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('image', editProductSelectedFile);
            return fetch('?page=api_store_manager_upload_catalog_product_image', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(imgResult => {
                    if (!imgResult.success) {
                        throw new Error(imgResult.message || 'Saved, but the image failed to upload.');
                    }
                });
        }
    })
    .then(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Product';
        Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false });
        const modalInstance = bootstrap.Modal.getInstance(document.getElementById('editProductModal'));
        if (modalInstance) modalInstance.hide();
        loadCatalog(currentPage);
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Product';
        Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Something went wrong.' });
    });
}

// ============================================
// BUNDLE DEALS (separate from per-product discounts above -- a deal is
// its own sellable line item at POS, a flat price for a fixed group of
// products, and never touches products.discount_value/discount_type)
// ============================================

let allDeals = [];

function loadDeals() {
    const list = document.getElementById('sm-deals-list');
    if (!list) return;
    list.innerHTML = `<div class="text-center py-4" style="grid-column:1/-1;"><div class="spinner-border text-primary" role="status"></div></div>`;

    fetch('?page=api_store_manager_deals')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allDeals = data.data.deals || [];
                renderDeals(allDeals);
            } else {
                list.innerHTML = `<div style="grid-column:1/-1;">${smErrorState(data.message || 'Failed to load deals')}</div>`;
            }
        })
        .catch(() => {
            list.innerHTML = `<div style="grid-column:1/-1;">${smErrorState()}</div>`;
        });
}

function renderDeals(deals) {
    const list = document.getElementById('sm-deals-list');
    if (!list) return;

    if (!deals || deals.length === 0) {
        list.innerHTML = `<div style="grid-column:1/-1;">${smEmptyState('No bundle deals yet. Click "Create Deal" to make one.', 'bi-tags')}</div>`;
        return;
    }

    list.innerHTML = deals.map(deal => {
        const componentsLabel = (deal.items || []).map(i => `${i.quantity}x ${escapeHtmlSM(i.name)}`).join(', ');
        return `
            <div class="sm-product-card" data-id="${deal.id}">
                <div class="sm-product-image">
                    ${deal.image_path
                        ? `<img src="/ShelfSense/public/${deal.image_path}" alt="${escapeHtmlSM(deal.name)}">`
                        : `<i class="bi bi-tags-fill"></i>`
                    }
                    ${!deal.is_active ? `<span class="sm-discount-badge" style="background:#6c757d;">Paused</span>` : ''}
                </div>
                <div class="sm-product-body">
                    <div class="sm-product-category" title="${componentsLabel}">${componentsLabel}</div>
                    <div class="sm-product-name" title="${escapeHtmlSM(deal.name)}">${escapeHtmlSM(deal.name)}</div>
                    <div class="sm-product-price">${smCurrency(deal.price)}</div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary toggle-deal-btn" data-id="${deal.id}" data-active="${deal.is_active ? 1 : 0}">
                            <i class="bi ${deal.is_active ? 'bi-pause-fill' : 'bi-play-fill'}"></i> ${deal.is_active ? 'Pause' : 'Resume'}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-deal-btn" data-id="${deal.id}" data-name="${escapeHtmlSM(deal.name)}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    list.querySelectorAll('.toggle-deal-btn').forEach(btn => {
        btn.addEventListener('click', () => toggleDeal(btn.dataset.id, btn.dataset.active !== '1'));
    });
    list.querySelectorAll('.delete-deal-btn').forEach(btn => {
        btn.addEventListener('click', () => deleteDeal(btn.dataset.id, btn.dataset.name));
    });
}

function toggleDeal(id, makeActive) {
    fetch('?page=api_store_manager_toggle_deal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(id), is_active: makeActive ? 1 : 0 })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            loadDeals();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Something went wrong.' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' }));
}

function deleteDeal(id, name) {
    Swal.fire({
        title: 'Remove this deal?',
        text: `"${name}" will no longer be sellable at POS. Past receipts are unaffected.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Remove',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('?page=api_store_manager_delete_deal', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                loadDeals();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Something went wrong.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' }));
    });
}

// ---- Create Deal modal ----

function setupCreateDealModal() {
    const openBtn = document.getElementById('createDealBtn');
    const addRowBtn = document.getElementById('addDealItemRowBtn');
    const form = document.getElementById('createDealForm');

    openBtn?.addEventListener('click', () => {
        form.reset();
        document.getElementById('dealItemsBody').innerHTML = '';
        addDealItemRow();
        addDealItemRow();
        new bootstrap.Modal(document.getElementById('createDealModal')).show();
    });

    addRowBtn?.addEventListener('click', () => addDealItemRow());

    form?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitCreateDeal();
    });
}

function addDealItemRow() {
    const body = document.getElementById('dealItemsBody');
    const rowId = 'deal_row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const options = allProducts.map(p => `<option value="${p.id}">${escapeHtmlSM(p.name)}</option>`).join('');
    const row = document.createElement('div');
    row.className = 'sm-item-row';
    row.id = rowId;
    row.innerHTML = `
        <select class="form-select form-select-sm deal-item-product">${options}</select>
        <input type="number" class="form-control form-control-sm deal-item-qty" min="1" max="99" value="1">
        <button type="button" class="sm-item-remove" onclick="document.getElementById('${rowId}').remove();" title="Remove"><i class="bi bi-trash"></i></button>
    `;
    body.appendChild(row);
}

function submitCreateDeal() {
    const name = document.getElementById('dealName').value.trim();
    const description = document.getElementById('dealDescription').value.trim();
    const price = parseFloat(document.getElementById('dealPrice').value);

    const items = [];
    const seenProductIds = new Set();
    let hasDuplicate = false;
    document.querySelectorAll('#dealItemsBody .sm-item-row').forEach(row => {
        const productId = parseInt(row.querySelector('.deal-item-product')?.value);
        const quantity = parseInt(row.querySelector('.deal-item-qty')?.value) || 0;
        if (productId && quantity > 0) {
            if (seenProductIds.has(productId)) hasDuplicate = true;
            seenProductIds.add(productId);
            items.push({ product_id: productId, quantity });
        }
    });

    if (!name) {
        Swal.fire({ icon: 'warning', title: 'Required', text: 'Deal name is required.' });
        return;
    }
    if (!(price > 0)) {
        Swal.fire({ icon: 'warning', title: 'Required', text: 'Bundle price must be greater than zero.' });
        return;
    }
    if (items.length < 2) {
        Swal.fire({ icon: 'warning', title: 'Required', text: 'A bundle needs at least 2 products.' });
        return;
    }
    if (hasDuplicate) {
        Swal.fire({ icon: 'warning', title: 'Duplicate product', text: 'Each product can only appear once in a bundle.' });
        return;
    }

    const submitBtn = document.querySelector('#createDealForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch('?page=api_store_manager_create_deal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, description, price, items })
    })
    .then(r => r.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Create Deal';
        if (result.success) {
            Swal.fire({ icon: 'success', title: 'Deal created!', timer: 1500, showConfirmButton: false });
            const modalInstance = bootstrap.Modal.getInstance(document.getElementById('createDealModal'));
            if (modalInstance) modalInstance.hide();
            loadDeals();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Failed to create deal.' });
        }
    })
    .catch(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Create Deal';
        Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
    });
}
