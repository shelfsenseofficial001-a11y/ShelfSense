<?php
// app/handlers/store_manager/purchase_orders/respond_to_counter.php
// Store Manager accepts a supplier's counter-proposal (applies the proposed
// quantity to the PO lines -- price never changes here, per the supplier
// having no ability to propose one) or rejects it (PO -> cancelled, reserved
// budget released, requisition -> cancelled so it can be re-sourced).

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../models/PoCounterProposal.php';
require_once __DIR__ . '/../../../models/Requisition.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../models/PoEvent.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PurchaseOrder;
use App\Models\PoCounterProposal;
use App\Models\Requisition;
use App\Models\Budget;
use App\Models\PoEvent;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$proposalId = isset($input['proposal_id']) ? intval($input['proposal_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';

if ($proposalId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    Response::error('Invalid request', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $proposalModel = new PoCounterProposal();
    $proposal = $proposalModel->getWithItems($proposalId);
    if (!$proposal) {
        $db->rollBack();
        Response::notFound('Counter-proposal not found');
    }
    if ($proposal['status'] !== 'pending') {
        $db->rollBack();
        Response::error('This proposal has already been ' . $proposal['status'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$proposal['po_id']]);
    $po = $stmt->fetch();

    $poModel = new PurchaseOrder();
    $reqModel = new Requisition();
    $requisition = $reqModel->getById($po['requisition_id']);

    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = (SELECT email FROM suppliers WHERE id = ?) AND is_active = 1");
    $stmt->execute([$po['supplier_id']]);
    $supplierUser = $stmt->fetch();

    if ($action === 'accept') {
        foreach ($proposal['items'] as $item) {
            $newQty = $item['proposed_quantity'] !== null ? (int)$item['proposed_quantity'] : (int)$item['current_quantity'];
            $poModel->updateItemQuantityPrice($item['po_item_id'], $newQty, (float)$item['current_unit_price']);
        }
        $newSubtotal = $poModel->recalculateTotals($po['id']);
        $poModel->updateStatus($po['id'], 'confirmed');
        $proposalModel->respond($proposalId, 'accepted', Auth::userId());
        (new PoEvent())->log($po['id'], 'counter_accepted', 'Store Manager accepted the supplier\'s quantity change.', 'all', Auth::userId());

        // Reconcile the budget reservation to the (possibly changed) new total.
        // Must use the SAME reference (reference_type='requisition', this
        // requisition's id) as the original reservation from approval --
        // otherwise getOpenReservation() can't see this adjustment later and
        // releases the wrong amount when payment is approved.
        $budgetModel = new Budget();
        $delta = round($newSubtotal - (float)$requisition['subtotal'], 2);
        if (abs($delta) > 0.001) {
            $budgetModel->postTransaction(
                $requisition['department_id'], $requisition['period_key'], 'reservation', $delta,
                'requisition', $requisition['id'], Auth::userId(), 'Adjusted for accepted supplier counter-proposal'
            );
        }

        if ($supplierUser) {
            createNotification($supplierUser['user_id'], 'po_counter_accepted', "Your counter-proposal for PO {$po['po_number']} was accepted.", "?page=supplier_requisitions");
        }

        $db->commit();
        Response::success(['po_id' => $po['id'], 'status' => 'confirmed'], 'Counter-proposal accepted. Purchase order confirmed.');
    } else {
        $poModel->updateStatus($po['id'], 'cancelled');
        $proposalModel->respond($proposalId, 'rejected', Auth::userId());
        $reqModel->updateStatus($requisition['id'], 'cancelled', ['rejected_reason' => 'Supplier counter-proposal rejected; please re-source.']);
        (new PoEvent())->log($po['id'], 'counter_rejected', 'Store Manager rejected the supplier\'s quantity change. Purchase order cancelled.', 'all', Auth::userId());

        $budgetModel = new Budget();
        $openReservation = $budgetModel->getOpenReservation('requisition', $requisition['id']);
        if ($openReservation > 0) {
            $budgetModel->postTransaction(
                $requisition['department_id'], $requisition['period_key'], 'release', $openReservation,
                'requisition', $requisition['id'], Auth::userId(), 'Released: PO cancelled after rejecting supplier counter-proposal'
            );
        }

        if ($supplierUser) {
            createNotification($supplierUser['user_id'], 'po_counter_rejected', "Your counter-proposal for PO {$po['po_number']} was rejected. The order was cancelled.", "?page=supplier_requisitions");
        }

        $db->commit();
        Response::success(['po_id' => $po['id'], 'status' => 'cancelled'], 'Counter-proposal rejected. Purchase order cancelled.');
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('respond_to_counter.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
