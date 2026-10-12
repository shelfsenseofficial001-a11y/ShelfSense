<?php
// app/handlers/finance/head/requisitions/list_pending.php
// Lists Requisitions awaiting Finance Head's supplier-approval/revision gate
// (already passed Finance Staff's budget check).

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

    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM requisitions WHERE status = 'pending_finance_head'");
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];

    $stmt = $db->prepare("
        SELECT r.*, s.company_name as supplier_name, s.email as supplier_email,
               d.name as department_name,
               CONCAT(u.first_name, ' ', u.last_name) as requested_by_name
        FROM requisitions r
        JOIN suppliers s ON r.preferred_supplier_id = s.id
        JOIN departments d ON r.department_id = d.id
        JOIN users u ON r.requested_by = u.user_id
        WHERE r.status = 'pending_finance_head'
        ORDER BY r.created_at ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requisitions = $stmt->fetchAll();

    $budgetModel = new Budget();
    foreach ($requisitions as &$r) {
        $r['budget_status'] = $budgetModel->getBudgetStatus(
            $r['department_id'],
            $r['period_key'],
            (float)$r['subtotal'],
            'requisition',
            (int)$r['id']
        );
    }
    unset($r);

    Response::success([
        'requisitions' => $requisitions,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => (int)ceil($total / $limit),
        ],
    ], 'Pending Finance Head requisitions fetched successfully');
} catch (Exception $e) {
    error_log('finance/head/requisitions/list_pending.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
