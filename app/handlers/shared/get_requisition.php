<?php
// app/handlers/shared/get_requisition.php
// Read access shared by every role that touches a requisition's lifecycle.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Requisition.php';
require_once __DIR__ . '/../../models/RequisitionEvent.php';
require_once __DIR__ . '/../../models/PurchaseOrder.php';
require_once __DIR__ . '/../../models/Budget.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Requisition;
use App\Models\RequisitionEvent;
use App\Models\PurchaseOrder;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isFinanceStaff() && !Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    Response::error('Invalid requisition id', 400);
}

try {
    $reqModel = new Requisition();
    $requisition = $reqModel->getWithItems($id);
    if (!$requisition) {
        Response::notFound('Requisition not found');
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, po_number, status FROM purchase_orders WHERE requisition_id = ?");
    $stmt->execute([$id]);
    $requisition['purchase_order'] = $stmt->fetch() ?: null;

    $requisition['events'] = (new RequisitionEvent())->getForRequisition($id);

    if (in_array($requisition['status'], ['pending_finance_head', 'pending_budget_check'], true)) {
        $budgetModel = new Budget();
        $requisition['budget_status'] = $budgetModel->getBudgetStatus(
            $requisition['department_id'],
            $requisition['period_key'],
            (float)$requisition['subtotal'],
            'requisition',
            $id
        );
    }

    Response::success($requisition, 'Requisition fetched successfully');
} catch (Exception $e) {
    error_log('shared/get_requisition.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
