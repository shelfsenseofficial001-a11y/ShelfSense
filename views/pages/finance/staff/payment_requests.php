<?php
$title = 'Purchase Orders - Finance Staff';
$pageTitle = 'Purchase Orders & Payments';
$activePage = 'staff_payment_requests';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20261013000002"></script>'
    . '<script src="/ShelfSense/public/assets/js/finance/staff/payment_requests.js?v=20261013000003"></script>';

$content = <<<'EOT'
<ul class="nav nav-tabs mb-3" id="fsTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" id="tabDispatch" data-bs-toggle="tab" data-bs-target="#dispatchTab" type="button">Pending Dispatch</button></li>
    <li class="nav-item"><button class="nav-link" id="tabAwaitingSupplier" data-bs-toggle="tab" data-bs-target="#awaitingSupplierTab" type="button">Awaiting Supplier</button></li>
    <li class="nav-item"><button class="nav-link" id="tabRequestPayment" data-bs-toggle="tab" data-bs-target="#requestPaymentTab" type="button">Request Payment</button></li>
    <li class="nav-item"><button class="nav-link" id="tabHolds" data-bs-toggle="tab" data-bs-target="#holdsTab" type="button">Reconciliation Holds</button></li>
    <li class="nav-item"><button class="nav-link" id="tabMyPayments" data-bs-toggle="tab" data-bs-target="#myPaymentsTab" type="button">My Payment Requests</button></li>
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

    <div class="tab-pane fade" id="awaitingSupplierTab">
        <p class="text-muted">Dispatched Purchase Orders waiting on the supplier to confirm, or that came back with a counter-proposal. View-only here — the supplier accepts/responds from their portal, and the Store Manager decides on any counter-proposal.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody id="awaitingSupplierTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
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

    <div class="tab-pane fade" id="myPaymentsTab">
        <p class="text-muted">Every payment request you've submitted, and what actually happened to it.</p>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>PO #</th><th>Supplier</th><th>Amount</th><th>Status</th><th>Method / Reference</th><th>Decided By</th><th></th></tr></thead>
                <tbody id="myPaymentsTableBody"><tr><td colspan="7" class="text-center py-4">Loading...</td></tr></tbody>
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

<div class="modal fade" id="fsPoDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Purchase Order Detail</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="fsPoDetailBody">Loading...</div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
