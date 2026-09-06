<?php
$title = 'Invoices - Supplier';
$pageTitle = 'Invoices';
$activePage = 'invoices';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20260908150000"></script>'
    . '<script src="/ShelfSense/public/assets/js/supplier/invoices.js?v=20260908"></script>';

$content = <<<'EOT'
<div class="modern-card p-3">
    <ul class="nav nav-tabs sp-tabs mb-3" id="spInvTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" id="tabCreateInvoice" data-bs-toggle="tab" data-bs-target="#createInvTab" type="button"><i class="bi bi-send me-1"></i>Create Invoice</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#myInvTab" type="button"><i class="bi bi-receipt me-1"></i>My Invoices</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="createInvTab">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Purchase Order</label>
                    <select id="invPoSelect" class="form-select"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Invoice Date</label>
                    <input type="date" id="invDate" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" id="invDueDate" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Attach Invoice File (optional, PDF/JPG/PNG)</label>
                    <input type="file" id="invFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>

            <hr>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="invItemsTable">
                    <thead><tr><th>Product</th><th>PO Qty</th><th>PO Price</th><th style="width:120px">Billed Qty</th><th style="width:140px">Billed Price</th></tr></thead>
                    <tbody id="invItemsBody"><tr><td colspan="5" class="text-center text-muted">Select a purchase order.</td></tr></tbody>
                </table>
            </div>

            <label class="form-label">Notes (optional)</label>
            <textarea id="invNotes" class="form-control" rows="2"></textarea>

            <button class="btn btn-yellow-primary mt-3" id="submitInvoiceBtn"><i class="bi bi-send"></i> Submit Invoice</button>
        </div>

        <div class="tab-pane fade" id="myInvTab">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Invoice #</th><th>PO #</th><th>Total</th><th>Match Status</th><th>Created</th></tr></thead>
                    <tbody id="myInvTableBody"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
