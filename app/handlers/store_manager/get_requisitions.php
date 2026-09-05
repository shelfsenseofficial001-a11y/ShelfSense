<?php
// app/handlers/store_manager/get_requisitions.php
// Compatibility endpoint for the dashboard's quick-preview widgets
// (?scope=mine|all&group=awaiting_finance|history). Full listing lives in
// requisitions/list.php; this just remaps to the old widget field shape
// (company_name, total) the dashboard JS already renders.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Requisition.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Requisition;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$scope = $_GET['scope'] ?? 'mine';
$group = $_GET['group'] ?? '';
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 5;

$filters = [];
if ($scope === 'mine') {
    $filters['requested_by'] = Auth::userId();
} elseif ($group === 'awaiting_finance') {
    $filters['statuses'] = ['pending_budget_check', 'pending_finance_head'];
} elseif ($group === 'history') {
    $filters['statuses'] = ['converted_to_po', 'rejected', 'budget_rejected', 'cancelled'];
}

try {
    $reqModel = new Requisition();
    $result = $reqModel->getAll(1, $limit, $filters);
    $requisitions = array_map(function ($r) {
        $r['company_name'] = $r['supplier_name'];
        $r['total'] = $r['subtotal'];
        return $r;
    }, $result['requisitions']);

    Response::success(['requisitions' => $requisitions], 'Requisitions fetched successfully');
} catch (Exception $e) {
    error_log('get_requisitions.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
