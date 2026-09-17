<?php
use App\Core\Database;

define('SHELFSENSE_INTERNAL_INCLUDE', true);
require_once __DIR__ . '/../../../app/handlers/store_manager/get_catalog.php';
$initialData = sm_catalog_build_data(Database::getInstance()->getConnection(), 1, 30, '', 0, '', 'name', 'asc');
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$title = 'Catalog & Deals - Store Manager';
$pageTitle = 'Catalog & Deals';
$activePage = 'catalog';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/catalog.js?v=20260917300000"></script>';

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>' . <<<'EOT'
<!-- Stats -->
<div class="sm-stats-grid">
    <div class="sm-stat-card">
        <div class="sm-stat-label">Total Products</div>
        <div class="sm-stat-number primary" id="statTotal">0</div>
    </div>
    <div class="sm-stat-card">
        <div class="sm-stat-label">On Sale</div>
        <div class="sm-stat-number success" id="statOnSale">0</div>
    </div>
    <div class="sm-stat-card">
        <div class="sm-stat-label">Regular Price</div>
        <div class="sm-stat-number" id="statRegular">0</div>
    </div>
</div>

<!-- Filters -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by name or barcode...">
    </div>
    <div class="col-md-3">
        <select id="categoryFilter" class="form-select searchable-select" data-placeholder="Filter by category...">
            <option value="0">All Categories</option>
        </select>
    </div>
    <div class="col-md-3">
        <select id="dealStatusFilter" class="form-select searchable-select" data-placeholder="Filter by deal...">
            <option value="">All Products</option>
            <option value="on_sale">On Sale</option>
            <option value="regular">Regular Price</option>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="button" class="sm-view-toggle-btn" id="catalogViewToggle" title="Switch to row view">
            <i class="bi bi-grid-3x3-gap-fill"></i>
        </button>
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn">
            <i class="bi bi-arrow-clockwise"></i> Reset
        </button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<!-- Sort control -->
<div class="d-flex align-items-center gap-2 mb-3">
    <span class="text-muted small">Sort by:</span>
    <div style="width:160px;">
        <select id="sortByField" class="form-select form-select-sm searchable-select" data-placeholder="Sort by...">
            <option value="name">Name</option>
            <option value="category">Category</option>
            <option value="price">Price</option>
            <option value="discount">Discount</option>
        </select>
    </div>
    <div style="width:160px;">
        <select id="sortByDir" class="form-select form-select-sm searchable-select" data-placeholder="Order...">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
        </select>
    </div>
</div>

<!-- Product Grid -->
<div class="modern-card p-3 sm-fill-card">
    <div id="sm-catalog-grid" class="sm-product-grid">
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading catalog...</p>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-muted small" id="tableInfo">Loading...</span>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                <li class="page-item disabled"><span class="page-link">1</span></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Bundle Deals -->
<div class="modern-card p-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-tags-fill text-yellow me-2"></i>Bundle Deals</h6>
    </div>
    <div id="sm-deals-list" class="sm-product-grid">
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>

<!-- Create Deal Modal -->
<div class="modal fade" id="createDealModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Deal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createDealForm">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Deal Name *</label>
                        <input type="text" id="dealName" class="form-control" required maxlength="150" placeholder="e.g. Book + Pen Combo">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea id="dealDescription" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bundle Price *</label>
                        <input type="number" id="dealPrice" class="form-control" step="0.01" min="0.01" required>
                        <div class="form-text">This is the flat price charged for the whole bundle -- it doesn't factor in each product's own price or discount.</div>
                    </div>
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label fw-semibold mb-0">Products in this bundle *</label>
                        <button type="button" class="btn btn-sm btn-yellow-outline" id="addDealItemRowBtn"><i class="bi bi-plus-lg"></i> Add Product</button>
                    </div>
                    <div id="dealItemsBody"></div>
                    <div class="form-text">A bundle needs at least 2 different products.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Create Deal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<button type="button" class="sm-fab" id="createDealBtn" title="Create Deal">
    <span class="sm-fab-icon"><i class="bi bi-tags-fill"></i></span>
    <span class="sm-fab-label">Create Deal</span>
</button>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Catalog Listing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm">
                <input type="hidden" id="editProductId" name="id" value="">
                <div class="modal-body">
                    <div class="alert alert-secondary small py-2 mb-3">
                        <i class="bi bi-info-circle"></i>
                        This only changes how the product is listed and priced here -- Inventory's stock, reorder level, and status are untouched.
                    </div>
                    <div class="mb-3 text-center">
                        <img id="editProductImagePreview" src="" alt="" style="max-width:120px;max-height:120px;border-radius:8px;object-fit:cover;display:none;">
                        <div id="editProductImagePlaceholder" class="text-muted"><i class="bi bi-box-seam" style="font-size:2.5rem;"></i></div>
                        <div class="mt-2">
                            <input type="file" id="editProductImage" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG, or WEBP, up to 3MB.</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Product Name *</label>
                        <input type="text" id="editProductName" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea id="editProductDescription" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Category</label>
                        <select id="editProductCategory" class="form-select searchable-select" data-placeholder="Select category...">
                            <option value="">Uncategorized</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Price *</label>
                            <input type="number" id="editProductPrice" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Cost</label>
                            <input type="number" id="editProductCost" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Discount</label>
                        <div class="input-group">
                            <select id="editProductDiscountType" class="form-select" style="flex:0 0 135px;width:135px;">
                                <option value="percent" selected>Percent %</option>
                                <option value="fixed">Fixed ₱</option>
                            </select>
                            <input type="number" id="editProductDiscount" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-text" id="editProductDiscountPreview"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
