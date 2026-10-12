<?php
// app/handlers/finance/head/requisitions/approve.php
// Finance Head's supplier-selection/negotiation gate (recommend-and-approve
// model): Store Manager already suggested one supplier at requisition time.
// Approving here posts the budget reservation and creates the Purchase
// Order itself from the requisition's items + suggested supplier, at that
// supplier's on-file price -- the PO starts at 'pending_dispatch' directly
// (its own budget-check/FH-approval gates already happened at the
// requisition level, see check_budget.php above this one).

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Requisition.php';
require_once __DIR__ . '/../../../../models/RequisitionEvent.php';
require_once __DIR__ . '/../../../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../../../models/PoEvent.php';
require_once __DIR__ . '/../../../../models/Budget.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Requisition;
use App\Models\RequisitionEvent;
use App\Models\PurchaseOrder;
use App\Models\PoEvent;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requisitionId = isset($input['requisition_id']) ? intval($input['requisition_id']) : 0;
$justification = isset($input['justification']) ? trim($input['justification']) : '';

if ($requisitionId <= 0) {
    Response::error('Invalid request', 400);
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT r.*, s.company_name as supplier_name FROM requisitions r JOIN suppliers s ON r.preferred_supplier_id = s.id WHERE r.id = ? FOR UPDATE");
    $stmt->execute([$requisitionId]);
    $req = $stmt->fetch();

    if (!$req) {
        $db->rollBack();
        Response::error('Requisition not found', 404);
    }
    if ($req['status'] !== 'pending_finance_head') {
        $db->rollBack();
        Response::error('This requisition is not awaiting Finance Head review. Current status: ' . $req['status'], 400);
    }

    $stmt = $db->prepare("
        SELECT ri.*, p.name as store_product_name
        FROM requisition_items ri
        JOIN products p ON ri.store_product_id = p.id
        WHERE ri.requisition_id = ?
    ");
    $stmt->execute([$requisitionId]);
    $items = $stmt->fetchAll();
    if (!$items) {
        $db->rollBack();
        Response::error('Requisition has no items.', 400);
    }

    $budgetModel = new Budget();
    $amount = (float)$req['subtotal'];
    $budgetStatus = $budgetModel->getBudgetStatus($req['department_id'], $req['period_key'], $amount, 'requisition', $requisitionId);

    if ($budgetStatus['exceeded'] && $justification === '') {
        $db->rollBack();
        Response::error(
            'This requisition exceeds the available budget (short by ₱' . number_format($budgetStatus['shortfall'], 2) . '). A justification is required to approve over budget.',
            400,
            ['justification' => 'Justification is required for over-budget approval.']
        );
    }

    $budgetModel->postTransaction(
        $req['department_id'],
        $req['period_key'],
        'reservation',
        $amount,
        'requisition',
        $requisitionId,
        Auth::userId(),
        $justification !== '' ? $justification : null
    );

    $reqModel = new Requisition();
    $poModel = new PurchaseOrder();
    $poNumber = $poModel->generateNumber();
    $poId = $poModel->create([
        'po_number' => $poNumber,
        'requisition_id' => $requisitionId,
        'supplier_id' => $req['preferred_supplier_id'],
        'status' => 'pending_dispatch',
        'order_date' => date('Y-m-d'),
        'expected_delivery_date' => $req['needed_by_date'],
        'created_by' => Auth::userId(),
    ]);
    foreach ($items as $line) {
        $poModel->addItem($poId, $line['store_product_id'], $line['supplier_product_id'], $line['quantity'], $line['estimated_unit_price']);
    }
    $poModel->recalculateTotals($poId);
    $poModel->updateStatus($poId, 'pending_dispatch', [
        'approved_by' => Auth::userId(),
        'approved_at' => date('Y-m-d H:i:s'),
    ]);

    $reqModel->updateStatus($requisitionId, 'converted_to_po');

    (new RequisitionEvent())->log($requisitionId, 'requisition_approved', "Approved by Finance Head. Purchase Order {$poNumber} created, supplier: {$req['supplier_name']}." . ($justification !== '' ? " Justification: $justification" : ''), Auth::userId(), 'finance_head');
    (new PoEvent())->log($poId, 'po_created', "Purchase Order {$poNumber} created from requisition {$req['requisition_number']} by Finance Head approval, supplier: {$req['supplier_name']}.", 'all', Auth::userId());

    foreach (getUsersByRole('finance_staff') as $u) {
        createNotification(
            $u['user_id'],
            'po_pending_dispatch',
            "Purchase Order {$poNumber} was created and is ready to dispatch to the supplier.",
            "?page=finance_staff_payment_requests"
        );
    }
    createNotification(
        $req['requested_by'],
        'requisition_approved',
        "Requisition {$req['requisition_number']} was approved by Finance Head. Purchase Order {$poNumber} created.",
        "?page=store_manager_requisitions"
    );

    $db->commit();

    Response::success([
        'requisition_id' => $requisitionId,
        'po_id' => $poId,
        'po_number' => $poNumber,
        'status' => 'converted_to_po',
        'budget_exceeded' => $budgetStatus['exceeded'],
    ], 'Requisition approved. Purchase Order created.');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('finance/head/requisitions/approve.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
