<?php
namespace App\Models;

use App\Core\Database;

/**
 * The 3-way match: PO <-> Goods Receipt <-> Invoice. Runs as a post-payment
 * reconciliation check (payment already happened at PO-confirmation time,
 * before delivery) -- a clean match just closes the loop (`reconciled`);
 * a variance surfaces a discrepancy between what was paid for and what was
 * actually delivered/billed for Finance Staff to look into, it no longer
 * gates any money movement.
 * Quantity rule: billed <= received (else quantity_hold).
 * Price rule: billed price must be within EITHER the percent OR the flat
 * amount tolerance of the PO's contracted price (whichever is looser) --
 * else price_hold.
 */
class InvoiceMatcher
{
    private $db;
    private $invoiceModel;
    private $poModel;
    private $settingsModel;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->invoiceModel = new Invoice();
        $this->poModel = new PurchaseOrder();
        $this->settingsModel = new VarianceToleranceSettings();
    }

    /**
     * Runs the match for every line on $invoiceId, updates each
     * invoice_items row's variance data, and sets invoices.match_status.
     * Returns the resulting match_status.
     */
    public function match($invoiceId)
    {
        $invoice = $this->invoiceModel->getWithItems($invoiceId);
        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        $tolerance = $this->settingsModel->get();
        $pricePercent = (float)$tolerance['price_tolerance_percent'];
        $priceAmount = (float)$tolerance['price_tolerance_amount'];
        $qtyPercent = (float)$tolerance['quantity_tolerance_percent'];

        $received = $this->poModel->getReceivedQuantities($invoice['po_id']);

        $hasQuantityHold = false;
        $hasPriceHold = false;

        foreach ($invoice['items'] as $item) {
            $poItemId = (int)$item['po_item_id'];
            $receivedQty = $received[$poItemId] ?? 0;
            $billedQty = (int)$item['billed_quantity'];
            $poUnitPrice = (float)$item['po_unit_price'];
            $billedUnitPrice = (float)$item['billed_unit_price'];

            $quantityVariance = $billedQty - $receivedQty;
            $allowedQtyOver = $qtyPercent > 0 ? (int)ceil($receivedQty * $qtyPercent / 100) : 0;
            $quantityOk = $billedQty <= ($receivedQty + $allowedQtyOver);

            $priceVariance = round($billedUnitPrice - $poUnitPrice, 2);
            $priceVarianceAbs = abs($priceVariance);
            $allowedPriceByPercent = $poUnitPrice * ($pricePercent / 100);
            $priceOk = $priceVarianceAbs <= max($allowedPriceByPercent, $priceAmount);

            $flag = 'ok';
            if (!$quantityOk) {
                $flag = 'quantity_variance';
                $hasQuantityHold = true;
            } elseif (!$priceOk) {
                $flag = 'price_variance';
                $hasPriceHold = true;
            }

            $this->invoiceModel->updateItemVariance($item['id'], $quantityVariance, $priceVariance, $flag);
        }

        if ($hasQuantityHold) {
            $status = 'quantity_hold';
        } elseif ($hasPriceHold) {
            $status = 'price_hold';
        } else {
            $status = 'reconciled';
        }

        $this->invoiceModel->setMatchStatus($invoiceId, $status);
        return $status;
    }
}
