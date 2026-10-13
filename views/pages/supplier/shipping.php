<?php
$title = 'Shipping - Supplier';
$pageTitle = 'Shipping';
$activePage = 'shipping';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20261013000002"></script>'
    . '<script src="/ShelfSense/public/assets/js/supplier/shipping.js?v=20261013000001"></script>';

$content = <<<'EOT'
<div class="modern-card p-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-truck text-yellow me-2"></i>Shipping</h6>
    <ul class="nav nav-tabs sm-tabs mb-3" id="spShipTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-tab-key="awaiting" type="button" role="tab" onclick="spSwitchShipTab('awaiting')">
                <i class="bi bi-hourglass-split"></i> Awaiting Shipment
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-tab-key="history" type="button" role="tab" onclick="spSwitchShipTab('history')">
                <i class="bi bi-clock-history"></i> History
            </button>
        </li>
    </ul>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-3">
            <thead><tr><th>PO #</th><th>Order Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody id="spShipTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <small id="spShipPageInfo" class="text-muted"></small>
        <ul class="pagination pagination-sm mb-0" id="spShipPagination"></ul>
    </div>
</div>

<div class="modal fade" id="spShipDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Purchase Order Detail</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="spShipDetailBody">Loading...</div>
        </div>
    </div>
</div>

<div class="modal fade" id="spShipDiscrepancyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="spShipDiscrepancyModalTitle">Resolve Discrepancy</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small" id="spShipDiscrepancyModalDesc"></p>
                <div class="mb-2" id="spShipDiscrepancyBackorderField">
                    <label class="form-label">New Delivery Date</label>
                    <input type="date" id="spShipDiscrepancyNewDeliveryDate" class="form-control">
                </div>
                <div class="mb-2 d-none" id="spShipDiscrepancyPickupField">
                    <label class="form-label">Pickup Date</label>
                    <input type="date" id="spShipDiscrepancyPickupDate" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-yellow-primary btn-sm" id="spShipSubmitDiscrepancyBtn">Submit</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
