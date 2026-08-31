<?php
// app/handlers/finance/head/get_pending_payment_requests.php
// Serves all 4 Finance Head tabs: pending / approved / rejected / all.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

try {
    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
    $tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'pending';
    if (!in_array($tab, ['pending', 'approved', 'rejected', 'all'], true)) {
        $tab = 'pending';
    }
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $budgetStatusFilter = isset($_GET['budget_status']) ? trim($_GET['budget_status']) : '';
    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

    $db = Database::getInstance()->getConnection();

    $where = "1=1";
    $params = [];
    if ($tab !== 'all') {
        $where .= " AND pr.status = ?";
        $params[] = $tab;
    }
    if ($search !== '') {
        $where .= " AND (r.requisition_number LIKE ? OR s.company_name LIKE ? OR si.invoice_number LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($budgetStatusFilter === 'exceeded') {
        $where .= " AND pr.budget_exceeded = 1";
    } elseif ($budgetStatusFilter === 'within_budget') {
        $where .= " AND pr.budget_exceeded = 0";
    }
    if ($dateFrom !== '') {
        $where .= " AND DATE(pr.requested_at) >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo !== '') {
        $where .= " AND DATE(pr.requested_at) <= ?";
        $params[] = $dateTo;
    }

    $countSql = "
        SELECT COUNT(*) as total
        FROM payment_requests pr
        JOIN store_requisitions r ON pr.requisition_id = r.id
        JOIN suppliers s ON r.supplier_id = s.id
        JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
        WHERE $where
    ";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    $offset = ($page - 1) * $limit;
    $sql = "
        SELECT pr.*,
               r.requisition_number, r.total as requisition_total, r.order_date,
               r.department, r.budget_month_year, r.status as requisition_status,
               s.company_name,
               u1.first_name as requested_first, u1.last_name as requested_last,
               u2.first_name as approved_first, u2.last_name as approved_last,
               si.invoice_number, si.total as invoice_total, si.due_date, si.notes as supplier_notes
        FROM payment_requests pr
        JOIN store_requisitions r ON pr.requisition_id = r.id
        JOIN suppliers s ON r.supplier_id = s.id
        JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
        JOIN users u1 ON pr.requested_by = u1.user_id
        LEFT JOIN users u2 ON pr.approved_by = u2.user_id
        WHERE $where
        ORDER BY " . ($tab === 'pending' ? "pr.requested_at ASC" : "pr.updated_at DESC") . "
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    // Live budget status only for rows still actually pending — historical
    // (approved/rejected) rows keep the budget_exceeded/reason recorded at the time.
    $budgetModel = new Budget();
    foreach ($requests as &$req) {
        if ($req['status'] === 'pending') {
            // Exclude this row's own requisition from "reserved" — it's the same
            // pending request being displayed, not a competing reservation, so it
            // must not be subtracted from availability twice.
            $req['budget_status'] = $budgetModel->getBudgetStatus(
                $req['department'] ?: 'store',
                $req['budget_month_year'] ?: CutoffPeriod::getCurrentKey(),
                (float)$req['requisition_total'],
                $req['requisition_id']
            );
        } else {
            $req['budget_status'] = null;
        }
    }
    unset($req);

    // Tab counts (unaffected by the current tab filter, but respect search/date/budget filters
    // so the badges match what the user is currently looking for).
    $countWhereBase = "1=1";
    $countParamsBase = [];
    if ($search !== '') {
        $countWhereBase .= " AND (r.requisition_number LIKE ? OR s.company_name LIKE ? OR si.invoice_number LIKE ?)";
        $like = "%{$search}%";
        $countParamsBase[] = $like;
        $countParamsBase[] = $like;
        $countParamsBase[] = $like;
    }
    $tabCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'all' => 0];
    foreach (['pending', 'approved', 'rejected'] as $s) {
        $sql2 = "
            SELECT COUNT(*) as c
            FROM payment_requests pr
            JOIN store_requisitions r ON pr.requisition_id = r.id
            JOIN suppliers s ON r.supplier_id = s.id
            JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
            WHERE $countWhereBase AND pr.status = ?
        ";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute(array_merge($countParamsBase, [$s]));
        $tabCounts[$s] = (int)$stmt2->fetch()['c'];
    }
    $tabCounts['all'] = $tabCounts['pending'] + $tabCounts['approved'] + $tabCounts['rejected'];

    Response::success([
        'payment_requests' => $requests,
        'tab_counts' => $tabCounts,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => $total,
            'totalPages' => (int)ceil($total / $limit)
        ]
    ], 'Payment requests fetched');

} catch (Exception $e) {
    error_log('finance/head/get_pending_payment_requests.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
