// ============================================
// STORE MANAGER - INVENTORY (VIEW-ONLY, card grid)
// ============================================

console.log('✅ store_manager/inventory.js loaded');

let currentPage = 1;
let sortBy = 'name';
let sortDir = 'asc';

document.addEventListener('DOMContentLoaded', function () {
    loadInventory();
    loadCategories();
    setupEventListeners();
    setupInventoryViewToggle();

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
    if (!select) return;

    fetch('?page=api_get_categories')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                (data.data.categories || []).forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    select.appendChild(option);
                });
                // Options were added after the searchable-select widget already
                // initialized on DOMContentLoaded — make it pick them up.
                window.refreshSearchableSelect?.(select);
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
                renderProducts(data.data.products);
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
        const price = smCurrency(product.price);
        const stock = parseInt(product.stock_quantity) || 0;
        const reorder = parseInt(product.reorder_level) || 0;
        return `
            <div class="sm-product-card">
                <div class="sm-product-image">
                    ${product.image_path
                        ? `<img src="/ShelfSense/public/${product.image_path}" alt="${escapeHtmlSM(product.name)}">`
                        : `<i class="bi bi-box-seam"></i>`
                    }
                </div>
                <div class="sm-product-body">
                    <div class="sm-product-category">${escapeHtmlSM(product.category_name || 'Uncategorized')}</div>
                    <div class="sm-product-name" title="${escapeHtmlSM(product.name)}">${escapeHtmlSM(product.name)}</div>
                    <div class="sm-product-price">${price}</div>
                    <div class="sm-product-stock-row">
                        <span>Stock: <strong>${stock}</strong> (Reorder: ${reorder})</span>
                    </div>
                    <div class="mt-1 sm-product-badge-wrap">${smStockBadge(stock, reorder)}</div>
                </div>
            </div>
        `;
    }).join('');
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
