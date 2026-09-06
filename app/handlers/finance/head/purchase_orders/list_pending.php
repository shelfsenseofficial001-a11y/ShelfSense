<?php
// app/handlers/finance/head/purchase_orders/list_pending.php
// Lists POs awaiting the explicit Finance Head PO-approval gate.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

try {
    $db = Database::getInstance()->getConnection();

    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'pending_fh_approval'");
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];

    $stmt = $db->prepare("
        SELECT po.*, s.company_name as supplier_name,
               r.requisition_number, r.department_id, r.period_key, r.requested_by,
               d.name as department_name,
               CONCAT(u.first_name, ' ', u.last_name) as requested_by_name
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        JOIN requisitions r ON po.requisition_id = r.id
        JOIN departments d ON r.department_id = d.id
        JOIN users u ON r.requested_by = u.user_id
        WHERE po.status = 'pending_fh_approval'
        ORDER BY po.created_at ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $purchaseOrders = $stmt->fetchAll();

    $budgetModel = new Budget();
    foreach ($purchaseOrders as &$po) {
        $po['budget_status'] = $budgetModel->getBudgetStatus(
            $po['department_id'],
            $po['period_key'],
            (float)$po['total'],
            'requisition',
            (int)$po['requisition_id']
        );
    }
    unset($po);

    Response::success([
        'purchase_orders' => $purchaseOrders,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => (int)ceil($total / $limit),
        ],
    ], 'Pending Purchase Orders fetched successfully');
} catch (Exception $e) {
    error_log('finance/head/purchase_orders/list_pending.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
