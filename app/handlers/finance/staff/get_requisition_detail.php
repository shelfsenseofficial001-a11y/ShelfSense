<?php
// app/handlers/finance/staff/get_requisition_detail.php
// ✅ Also used by Finance Head

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

// ✅ Allow both Finance Staff AND Finance Head
if (!Auth::isFinanceStaff() && !Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance role required.');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    Response::error('Invalid requisition ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $requisitionModel = new StoreRequisition();
    $requisition = $requisitionModel->getWithGoodsReceipt($id);

    if (!$requisition) {
        Response::notFound('Requisition not found');
    }

    // The schema has no supplier_invoice_items table — invoices don't carry their own
    // line items, they're generated 1:1 from the requisition's items (see
    // supplier/create_invoice.php). Use the real requisition items (already loaded by
    // getWithGoodsReceipt() above) as the authoritative "invoice items" for display.
    if (isset($requisition['invoice']) && $requisition['invoice']) {
        $invoiceItems = $requisition['items'] ?? [];
        $requisition['invoice']['items'] = $invoiceItems;
        $requisition['invoice']['total_quantity'] = array_sum(array_column($invoiceItems, 'quantity'));
    }

    // Payment request tied to this requisition, if any (for status/history display)
    $stmt = $db->prepare("
        SELECT pr.*, u.first_name as approved_first, u.last_name as approved_last
        FROM payment_requests pr
        LEFT JOIN users u ON pr.approved_by = u.user_id
        WHERE pr.requisition_id = ?
        ORDER BY pr.requested_at DESC
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $requisition['payment_request'] = $stmt->fetch() ?: null;

    // Live, authoritative budget status for this requisition's department/period —
    // reused by both Finance Staff (creating a request) and Finance Head (reviewing
    // one), so the numbers shown are always freshly computed, never fabricated.
    $budgetModel = new Budget();
    $requisition['budget_status'] = $budgetModel->getBudgetStatus(
        $requisition['department'] ?: 'store',
        $requisition['budget_month_year'] ?: date('Y-m'),
        (float)$requisition['total']
    );

    Response::success([
        'requisition' => $requisition
    ], 'Requisition details fetched');

} catch (Exception $e) {
    error_log('get_requisition_detail.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}