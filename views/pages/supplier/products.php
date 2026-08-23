<?php
$title = 'Supplier Products - ShelfSense';
$pageTitle = 'My Product Catalog';
$activePage = 'products';
$additional_js = '<script src="/ShelfSense/public/assets/js/supplier/products.js"></script>';

$content = <<<'EOT'
<div class="sp-stats-grid">
    <div class="sp-stat-card">
        <div class="sp-stat-label">Total Products</div>
        <div class="sp-stat-number primary" id="statTotal">0</div>
    </div>
    <div class="sp-stat-card">
        <div class="sp-stat-label">Active</div>
        <div class="sp-stat-number success" id="statActive">0</div>
    </div>
    <div class="sp-stat-card">
        <div class="sp-stat-label">Inactive</div>
        <div class="sp-stat-number danger" id="statInactive">0</div>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search products...">
        </div>
    </div>
    <div class="col-md-3">
        <select id="statusFilter" class="form-select searchable-select" data-placeholder="Filter by status...">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    <div class="col-md-5 text-end">
        <button class="btn btn-yellow-primary btn-sm" id="addProductBtn"><i class="bi bi-plus-circle"></i> Add Product</button>
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
</div>

<div id="sp-product-grid" class="sp-product-grid">
    <div class="text-center py-4" style="grid-column:1/-1;">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading products...</p>
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

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm">
                <input type="hidden" id="productId" name="id" value="">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Product Name *</label>
                        <input type="text" name="name" id="productName" class="form-control" required maxlength="100">
                        <div class="sp-mapping-warning mt-1">
                            <i class="bi bi-exclamation-triangle"></i>
                            This name must exactly match the Store product name for automatic mapping during requisition creation. The project currently matches store and supplier products by name only — there is no dedicated linking field yet.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="productDescription" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Price *</label>
                        <input type="number" name="price" id="productPrice" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="is_active" id="productStatus" class="form-select">
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

<!-- Delete Product Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
