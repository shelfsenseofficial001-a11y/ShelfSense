<?php
// app/handlers/finance/staff/get_pending_requisitions.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/StoreRequisition.php';
require_once __DIR__ . '/../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\StoreRequisition;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

try {
    $db = Database::getInstance()->getConnection();
    $budgetModel = new Budget();
    $requisitionModel = new StoreRequisition();

    $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'to_review';

    if ($tab === 'awaiting_approval' || $tab === 'my_history') {
        // awaiting_approval: payment requests pending Finance Head decision (any Finance Staff).
        // my_history: every payment request THIS Finance Staff has created, any status.
        $where = $tab === 'my_history' ? "pr.requested_by = ?" : "pr.status = 'pending'";
        $params = $tab === 'my_history' ? [Auth::userId()] : [];
        if (!empty($search)) {
            $where .= " AND (r.requisition_number LIKE ? OR s.company_name LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        $countSql = "SELECT COUNT(*) as total FROM payment_requests pr JOIN store_requisitions r ON pr.requisition_id = r.id JOIN suppliers s ON r.supplier_id = s.id WHERE $where";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $offset = ($page - 1) * $limit;
        $orderBy = $tab === 'my_history' ? 'pr.requested_at DESC' : 'pr.requested_at ASC';
        $sql = "
            SELECT r.*, s.company_name, u.first_name, u.last_name,
                   pr.id as payment_request_id, pr.status as payment_request_status,
                   pr.requested_at, pr.approved_at, pr.rejection_reason,
                   pr.budget_exceeded, pr.budget_exceeded_reason,
                   si.invoice_number, si.due_date, si.total as invoice_total, si.notes as supplier_notes,
                   (SELECT COUNT(*) FROM store_requisition_items ri WHERE ri.requisition_id = r.id) as item_count
            FROM payment_requests pr
            JOIN store_requisitions r ON pr.requisition_id = r.id
            JOIN suppliers s ON r.supplier_id = s.id
            JOIN users u ON r.created_by = u.user_id
            LEFT JOIN supplier_invoices si ON pr.supplier_invoice_id = si.id
            WHERE $where
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $requisitions = $stmt->fetchAll();
    } else {
        // to_review / budget_exceeded: requisitions forwarded to Finance Staff, not yet
        // turned into a payment request.
        $filters = ['status' => 'awaiting_finance_staff'];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        // Fetch a generous batch so budget status can be computed and split into tabs
        // without a second round trip; still paginate within the resulting subset.
        $all = $requisitionModel->getAll(1, 200, $filters);

        foreach ($all['requisitions'] as &$req) {
            $stmt = $db->prepare("SELECT * FROM supplier_invoices WHERE requisition_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$req['id']]);
            $req['invoice'] = $stmt->fetch() ?: null;
            $stmt = $db->prepare("SELECT COUNT(*) FROM store_requisition_items WHERE requisition_id = ?");
            $stmt->execute([$req['id']]);
            $req['item_count'] = (int)$stmt->fetchColumn();

            $dept = $req['department'] ?: 'store';
            $monthYear = $req['budget_month_year'] ?: date('Y-m');
            $budgetStatus = $budgetModel->getBudgetStatus($dept, $monthYear, (float)$req['total']);
            $req['budget_status'] = $budgetStatus;
        }
        unset($req);

        $filtered = array_values(array_filter($all['requisitions'], function ($r) use ($tab) {
            return $tab === 'budget_exceeded' ? $r['budget_status']['exceeded'] : !$r['budget_status']['exceeded'];
        }));

        $total = count($filtered);
        $offset = ($page - 1) * $limit;
        $requisitions = array_slice($filtered, $offset, $limit);
    }

    // Tab counts (independent of the active tab/search) for the tab badges
    $stmt = $db->query("SELECT COUNT(*) FROM store_requisitions WHERE status = 'awaiting_finance_staff'");
    $toReviewTotal = (int)$stmt->fetchColumn();
    $stmt = $db->query("SELECT COUNT(*) FROM payment_requests WHERE status = 'pending'");
    $awaitingApprovalTotal = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM payment_requests WHERE requested_by = ?");
    $stmt->execute([Auth::userId()]);
    $myHistoryTotal = (int)$stmt->fetchColumn();

    // Budget-exceeded count requires evaluating each to-review requisition — reuse the
    // batch already computed above when on those two tabs; otherwise compute quickly.
    if (in_array($tab, ['to_review', 'budget_exceeded'], true)) {
        $exceededCount = count(array_filter($all['requisitions'], fn($r) => $r['budget_status']['exceeded']));
        $toReviewCount = count($all['requisitions']) - $exceededCount;
    } else {
        $stmt = $db->prepare("
            SELECT r.id, r.department, r.budget_month_year, r.total
            FROM store_requisitions r
            WHERE r.status = 'awaiting_finance_staff'
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $exceededCount = 0;
        foreach ($rows as $row) {
            $bs = $budgetModel->getBudgetStatus($row['department'] ?: 'store', $row['budget_month_year'] ?: date('Y-m'), (float)$row['total']);
            if ($bs['exceeded']) $exceededCount++;
        }
        $toReviewCount = count($rows) - $exceededCount;
    }

    Response::success([
        'requisitions' => $requisitions,
        'pagination' => [
            'currentPage' => (int)$page,
            'perPage' => (int)$limit,
            'totalRecords' => (int)$total,
            'totalPages' => ceil($total / $limit)
        ],
        'tab_counts' => [
            'to_review' => $toReviewCount,
            'budget_exceeded' => $exceededCount,
            'awaiting_approval' => $awaitingApprovalTotal,
            'my_history' => $myHistoryTotal
        ]
    ], 'Pending requisitions fetched');

} catch (Exception $e) {
    error_log('get_pending_requisitions.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
