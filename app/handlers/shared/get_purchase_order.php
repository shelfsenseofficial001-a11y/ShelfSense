<?php
// app/handlers/shared/get_purchase_order.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../models/GoodsReceipt.php';
require_once __DIR__ . '/../../models/Invoice.php';
require_once __DIR__ . '/../../models/PoCounterProposal.php';
require_once __DIR__ . '/../../models/PoPaymentRequest.php';
require_once __DIR__ . '/../../models/PoEvent.php';
require_once __DIR__ . '/../../models/PoDiscrepancy.php';
require_once __DIR__ . '/../../models/PoExcessItem.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\PoCounterProposal;
use App\Models\PoPaymentRequest;
use App\Models\PoEvent;
use App\Models\PoDiscrepancy;
use App\Models\PoExcessItem;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isFinanceStaff() && !Auth::isFinanceHead() && !Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    Response::error('Invalid purchase order id', 400);
}

try {
    $poModel = new PurchaseOrder();
    $po = $poModel->getWithItems($id);
    if (!$po) {
        Response::notFound('Purchase order not found');
    }

    if (Auth::isSupplier() && !Auth::isSuperAdmin()) {
        $supplierId = Auth::supplierId();
        if (!$supplierId || $supplierId !== (int)$po['supplier_id']) {
            Response::forbidden('This purchase order does not belong to you.');
        }
    }

    // Goods Receipt detail is restricted from the Supplier -- they ship
    // against the PO and get paid against their own Invoice; what was
    // physically received/inspected in-store is store-side/finance-side
    // information only.
    $canSeeGoodsReceipt = !Auth::isSupplier() || Auth::isSuperAdmin();
    if ($canSeeGoodsReceipt) {
        $received = $poModel->getReceivedQuantities($id);
        foreach ($po['items'] as &$item) {
            $item['received_quantity_total'] = $received[(int)$item['id']] ?? 0;
        }
        unset($item);

        $grModel = new GoodsReceipt();
        $goodsReceipts = $grModel->getByPoId($id);
        foreach ($goodsReceipts as &$gr) {
            $gr['items'] = $grModel->getItems($gr['id']);
        }
        unset($gr);
        $po['goods_receipts'] = $goodsReceipts;

        $excessModel = new PoExcessItem();
        $po['excess_items'] = $excessModel->getByPo($id);
    }

    // Discrepancies (and the good-condition quantity ceiling they drive) are
    // supplier-actionable by design, unlike the raw Goods Receipt detail
    // above -- the Supplier needs to see and resolve these to unblock
    // invoicing, so they're visible to every role permitted on this endpoint.
    $discModel = new PoDiscrepancy();
    $goodReceived = $poModel->getGoodReceivedQuantities($id);
    $outstanding = $discModel->getOutstandingQuantities($id);
    foreach ($po['items'] as &$item) {
        $item['good_received_quantity'] = $goodReceived[(int)$item['id']] ?? 0;
        $item['outstanding_discrepancy_quantity'] = $outstanding[(int)$item['id']] ?? 0;
    }
    unset($item);
    $po['discrepancies'] = $discModel->getByPo($id);

    // Supplier Invoice detail is restricted from the Store Manager -- billing
    // and payment status is a Finance/Supplier concern; the Store Manager's
    // job ends at logging the Goods Receipt.
    $canSeeInvoices = !Auth::isStoreManager() || Auth::isSuperAdmin();
    if ($canSeeInvoices) {
        $invoiceModel = new Invoice();
        $po['invoices'] = $invoiceModel->getByPoId($id);
    }

    $proposalModel = new PoCounterProposal();
    $latestProposal = $proposalModel->getLatestPendingForPo($id);
    $po['pending_counter_proposal'] = $latestProposal ? $proposalModel->getWithItems($latestProposal['id']) : null;

    $paymentRequestModel = new PoPaymentRequest();
    $po['pending_payment_request'] = $paymentRequestModel->getPendingForPo($id);

    $role = Auth::isSupplier() ? 'supplier' : (Auth::isStoreManager() ? 'store_manager' : null);
    $po['events'] = (new PoEvent())->getForPo($id, $role);

    Response::success($po, 'Purchase order fetched successfully');
} catch (Exception $e) {
    error_log('shared/get_purchase_order.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
