<?php
// app/handlers/shared/product_proposals/get_list.php
// Store Manager/Owner see every proposal; a Supplier only ever sees the
// ones addressed to them.

require_once __DIR__ . '/../../../models/ProductProposal.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\ProductProposal;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$isStoreOrOwner = Auth::isStoreManager() || Auth::isOwner() || Auth::isSuperAdmin();
if (!$isStoreOrOwner && !Auth::isSupplier()) {
    Response::forbidden('Access denied.');
}

$filters = [];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

try {
    if (Auth::isSupplier() && !Auth::isSuperAdmin()) {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
        $stmt->execute([Auth::userId()]);
        $supplier = $stmt->fetch();
        if (!$supplier) {
            Response::success(['proposals' => []], 'Proposals fetched successfully');
        }
        $filters['supplier_id'] = $supplier['id'];
    }

    $proposalModel = new ProductProposal();
    Response::success(['proposals' => $proposalModel->getAll($filters)], 'Proposals fetched successfully');
} catch (Exception $e) {
    error_log('product_proposals/get_list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
