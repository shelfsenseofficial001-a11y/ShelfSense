<?php
use App\Core\Database;

define('SHELFSENSE_INTERNAL_INCLUDE', true);
require_once __DIR__ . '/../../../app/handlers/store_manager/get_inventory.php';
$initialData = sm_inventory_build_data(Database::getInstance()->getConnection(), 1, 30, '', 0, 0, '', 'name', 'asc');
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$title = 'Inventory - Store Manager';
$pageTitle = 'Inventory Management';
$activePage = 'inventory';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/inventory.js?v=20260908600000"></script>';

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>' . <<<'EOT'
<!-- Stats -->
<div class="sm-stats-grid">
    <div class="sm-stat-card">
        <div class="sm-stat-label">Total Products</div>
        <div class="sm-stat-number primary" id="statTotal">0</div>
    </div>
    <div class="sm-stat-card">
        <div class="sm-stat-label">In Stock</div>
        <div class="sm-stat-number success" id="statInStock">0</div>
    </div>
    <div class="sm-stat-card">
        <div class="sm-stat-label">Low Stock</div>
        <div class="sm-stat-number warning" id="statLowStock">0</div>
    </div>
    <div class="sm-stat-card">
        <div class="sm-stat-label">Out of Stock</div>
        <div class="sm-stat-number danger" id="statOutOfStock">0</div>
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
        <select id="stockStatusFilter" class="form-select searchable-select" data-placeholder="Filter by stock level...">
            <option value="">All Stock Levels</option>
            <option value="in">In Stock</option>
            <option value="low">Low Stock</option>
            <option value="out">Out of Stock</option>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="button" class="sm-view-toggle-btn" id="inventoryViewToggle" title="Switch to row view">
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
            <option value="stock">Stock</option>
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
    <div id="sm-product-grid" class="sm-product-grid">
        <div class="text-center py-4" style="grid-column:1/-1;">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading inventory...</p>
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

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm">
                <input type="hidden" id="editProductId" name="id" value="">
                <div class="modal-body">
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
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Stock Quantity *</label>
                            <input type="number" id="editProductStock" class="form-control" min="0" step="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Reorder Level</label>
                            <input type="number" id="editProductReorder" class="form-control" min="0" step="1">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="editProductStatus" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
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

<a href="?page=store_manager_requisitions&tab=create" class="sm-fab" title="Create Requisition">
    <span class="sm-fab-icon"><i class="bi bi-plus-lg"></i></span>
    <span class="sm-fab-label">Create Requisition</span>
</a>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
