<?php
$title = 'Inventory - Store Manager';
$pageTitle = 'Inventory Management';
$activePage = 'inventory';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/inventory.js"></script>';

$content = <<<'EOT'
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
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name or barcode...">
        </div>
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
    <div class="col-md-2 text-end">
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn">
            <i class="bi bi-arrow-clockwise"></i> Reset
        </button>
    </div>
</div>

<!-- Sort control -->
<div class="d-flex align-items-center gap-3 mb-3">
    <span class="text-muted small">Sort by:</span>
    <span class="sm-sort-toggle" data-sort="name">Name <i class="bi bi-arrow-down-up"></i></span>
    <span class="sm-sort-toggle" data-sort="category">Category <i class="bi bi-arrow-down-up"></i></span>
    <span class="sm-sort-toggle" data-sort="price">Price <i class="bi bi-arrow-down-up"></i></span>
    <span class="sm-sort-toggle" data-sort="stock">Stock <i class="bi bi-arrow-down-up"></i></span>
</div>

<!-- Product Grid (View-Only) -->
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
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
