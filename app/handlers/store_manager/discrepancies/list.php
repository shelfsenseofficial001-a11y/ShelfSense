<?php
// app/handlers/store_manager/discrepancies/list.php
// Every damaged ('rma') or missing ('backorder') line raised across every
// PO, for the Store Manager's Backorders & RMA tab. ?type=missing|damaged,
// ?status=open|action_taken|resolved filter; otherwise everything.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/PoDiscrepancy.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PoDiscrepancy;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Store Manager role required.');
}

$page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;

$filters = [];
if (!empty($_GET['type']) && in_array($_GET['type'], ['missing', 'damaged'], true)) {
    $filters['type'] = $_GET['type'];
}
if (!empty($_GET['status']) && in_array($_GET['status'], ['open', 'action_taken', 'resolved'], true)) {
    $filters['status'] = $_GET['status'];
}

try {
    $discModel = new PoDiscrepancy();
    $result = $discModel->getAll($page, $limit, $filters);
    Response::success($result, 'Discrepancies fetched successfully');
} catch (Exception $e) {
    error_log('store_manager/discrepancies/list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
