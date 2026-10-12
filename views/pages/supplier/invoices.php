<?php
$title = 'Invoices - Supplier';
$pageTitle = 'Invoices';
$activePage = 'invoices';
$additional_js = '<script src="/ShelfSense/public/assets/js/procurement/shared.js?v=20261013000002"></script>'
    . '<script src="/ShelfSense/public/assets/js/supplier/invoices.js?v=20261013000006"></script>';

$content = <<<'EOT'
<div class="row g-3">
    <div class="col-lg-4">
        <div class="modern-card p-3 h-100">
            <h6 class="fw-bold mb-2"><i class="bi bi-receipt text-yellow me-2"></i>Invoices</h6>
            <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                <button type="button" class="btn btn-outline-secondary active" id="invTabToInvoice" onclick="switchInvoiceListTab('to_invoice')">To Invoice</button>
                <button type="button" class="btn btn-outline-secondary" id="invTabSubmitted" onclick="switchInvoiceListTab('submitted')">Submitted</button>
            </div>
            <div id="invListPanel" class="list-group" style="max-height:65vh; overflow-y:auto;">
                <div class="text-center text-muted py-4">Loading...</div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-send text-yellow me-2"></i>Create Invoice</h6>
            <div id="invCreatePanel">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-arrow-left-circle fs-1 d-block mb-2"></i>
                    Select a purchase order from the list to create an invoice.
                </div>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/supplier.php';
