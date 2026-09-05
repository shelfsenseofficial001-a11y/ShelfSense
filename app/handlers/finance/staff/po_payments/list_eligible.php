<?php
// app/handlers/finance/staff/po_payments/list_eligible.php
// POs the supplier has confirmed and that don't already have a pending
// payment request -- ready for Finance Staff to request payment on.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT po.id, po.po_number, po.total, s.company_name as supplier_name
        FROM purchase_orders po
        JOIN suppliers s ON po.supplier_id = s.id
        WHERE po.status = 'confirmed'
          AND NOT EXISTS (SELECT 1 FROM po_payment_requests ppr WHERE ppr.po_id = po.id AND ppr.status = 'pending')
        ORDER BY po.created_at ASC
    ");
    Response::success(['purchase_orders' => $stmt->fetchAll()], 'Eligible purchase orders fetched successfully');
} catch (Exception $e) {
    error_log('po_payments/list_eligible.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
