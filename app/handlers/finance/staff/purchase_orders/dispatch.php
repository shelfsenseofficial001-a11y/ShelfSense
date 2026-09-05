<?php
// app/handlers/finance/staff/purchase_orders/dispatch.php
// Emails the PO to the supplier (from the procurement mailbox) and notifies
// their portal account, moving the PO to pending_confirmation.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../core/Mailer.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../models/PoEvent.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\Mailer;
use App\Models\PurchaseOrder;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$poId = isset($input['po_id']) ? intval($input['po_id']) : 0;
if ($poId <= 0) {
    Response::error('Invalid purchase order id', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$poId]);
    $po = $stmt->fetch();
    if (!$po) {
        Response::notFound('Purchase order not found');
    }
    if ($po['status'] !== 'pending_dispatch') {
        Response::error('This purchase order is not pending dispatch. Current status: ' . $po['status'], 400);
    }

    $poModel = new PurchaseOrder();
    $poWithItems = $poModel->getWithItems($poId);

    $stmt = $db->prepare("SELECT company_name, email FROM suppliers WHERE id = ?");
    $stmt->execute([$po['supplier_id']]);
    $supplier = $stmt->fetch();

    $itemsHtml = '';
    foreach ($poWithItems['items'] as $item) {
        $itemsHtml .= "<tr>
            <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$item['store_product_name']}</td>
            <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;'>{$item['quantity']}</td>
            <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;'>₱" . number_format($item['unit_price'], 2) . "</td>
            <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;'>₱" . number_format($item['total'], 2) . "</td>
        </tr>";
    }

    $body = "
        <div style='text-align:center;padding:20px;background:#facc15;border-radius:8px 8px 0 0;'>
            <h2 style='margin:0;color:#1a1a1a;'>New Purchase Order {$po['po_number']}</h2>
        </div>
        <div style='padding:20px;background:#ffffff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;font-family:Arial,sans-serif;'>
            <p>Dear <strong>{$supplier['company_name']}</strong>,</p>
            <p>ShelfSense has issued a new Purchase Order. Please review and confirm via the supplier portal.</p>
            <p><strong>Order Date:</strong> {$po['order_date']}</p>
            <p><strong>Expected Delivery:</strong> " . ($po['expected_delivery_date'] ?: 'Not specified') . "</p>
            <p><strong>Terms:</strong> {$po['terms']}</p>
            <table style='width:100%;border-collapse:collapse;margin-top:12px;'>
                <thead><tr>
                    <th style='padding:8px 12px;text-align:left;background:#f3f4f6;'>Item</th>
                    <th style='padding:8px 12px;text-align:center;background:#f3f4f6;'>Qty</th>
                    <th style='padding:8px 12px;text-align:right;background:#f3f4f6;'>Unit Price</th>
                    <th style='padding:8px 12px;text-align:right;background:#f3f4f6;'>Total</th>
                </tr></thead>
                <tbody>{$itemsHtml}</tbody>
                <tfoot><tr>
                    <td colspan='3' style='padding:12px;text-align:right;font-weight:bold;'>Total</td>
                    <td style='padding:12px;text-align:right;font-weight:bold;'>₱" . number_format($po['total'], 2) . "</td>
                </tr></tfoot>
            </table>
            <p style='margin-top:16px;font-size:12px;color:#6b7280;'>Please log in to the ShelfSense Supplier Portal to accept or propose changes to this order.</p>
        </div>
    ";

    if (!empty($supplier['email'])) {
        $mailer = new Mailer('procurement');
        $mailer->send($supplier['email'], "New Purchase Order {$po['po_number']} - ShelfSense", $body);
    }

    $poModel->markDispatched($poId, !empty($supplier['email']) ? 'email' : 'portal');

    (new PoEvent())->log($poId, 'po_dispatched', 'Purchase Order dispatched to supplier by Finance Staff.', 'all', Auth::userId());

    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$supplier['email']]);
    $supplierUser = $stmt->fetch();
    if ($supplierUser) {
        createNotification(
            $supplierUser['user_id'],
            'po_received',
            "New Purchase Order {$po['po_number']} has been sent to you. Please review and confirm.",
            "?page=supplier_requisitions"
        );
    }

    Response::success(['po_id' => $poId, 'status' => 'pending_confirmation'], 'Purchase order dispatched to supplier.');
} catch (Exception $e) {
    error_log('purchase_orders/dispatch.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
