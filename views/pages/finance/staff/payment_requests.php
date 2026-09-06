<?php
$title = 'Purchase Orders - Finance Staff';
$pageTitle = 'Purchase Orders & Payments';
$activePage = 'staff_payment_requests';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260905"></script>'
    . '<script src="/ShelfSense/public/assets/js/finance/staff/payment_requests.js?v=20260905"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs mb-3" id="fsTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dispatchTab" type="button">Pending Dispatch</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#requestPaymentTab" type="button">Request Payment</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#holdsTab" type="button">Reconciliation Holds</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="dispatchTab">
        <p class="text-muted">Purchase Orders approved by Finance Head. Review and send to the supplier.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Total</th><th></th></tr></thead>
                <tbody id="dispatchTableBody"><tr><td colspan="4" class="text-center py-4">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="requestPaymentTab">
        <p class="text-muted">Purchase Orders that have been delivered and have a reconciled invoice. Request payment here for Finance Head to approve.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Total</th><th></th></tr></thead>
                <tbody id="requestPaymentTableBody"><tr><td colspan="4" class="text-center py-4">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="holdsTab">
        <p class="text-muted">Invoices that failed the 3-way match (PO vs. Goods Receipt vs. Invoice). A payment request can't be created until these are resolved. Override with a justification, or correct the PO's recorded price and re-match.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Invoice #</th><th>PO #</th><th>Supplier</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody id="holdsTableBody"><tr><td colspan="6" class="text-center py-4">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="varianceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Resolve Invoice Variance</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="varianceModalBody">Loading...</div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
