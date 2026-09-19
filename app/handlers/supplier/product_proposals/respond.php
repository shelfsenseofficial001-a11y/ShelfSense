<?php
// app/handlers/supplier/product_proposals/respond.php
// Supplier confirms their own product name/price/quantity for a proposed
// item (moving it to Owner for final sign-off) or declines it outright
// (a dead end, logged with a reason).

require_once __DIR__ . '/../../../models/ProductProposal.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\ProductProposal;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Supplier role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';

if ($id <= 0 || !in_array($action, ['confirm', 'decline'], true)) {
    Response::error('Missing or invalid request.', 400);
}

$data = [];
if ($action === 'confirm') {
    $data['supplier_product_name'] = isset($input['supplier_product_name']) ? trim($input['supplier_product_name']) : '';
    $data['supplier_price'] = isset($input['supplier_price']) ? floatval($input['supplier_price']) : 0;
    $data['supplier_quantity'] = isset($input['supplier_quantity']) ? intval($input['supplier_quantity']) : 0;

    if (empty($data['supplier_product_name']) || strlen($data['supplier_product_name']) > 100) {
        Response::error('Your product name (up to 100 characters) is required.', 400);
    }
    if ($data['supplier_price'] <= 0) {
        Response::error('A price is required.', 400);
    }
    if ($data['supplier_quantity'] < 0) {
        Response::error('Quantity cannot be negative.', 400);
    }
} else {
    $data['reason'] = isset($input['reason']) ? trim($input['reason']) : '';
    if (empty($data['reason'])) {
        Response::error('Please provide a reason for declining.', 400);
    }
}

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM suppliers WHERE email = (SELECT email FROM users WHERE user_id = ?)");
    $stmt->execute([Auth::userId()]);
    $supplier = $stmt->fetch();
    if (!$supplier) {
        Response::forbidden('No supplier profile linked to this account.');
    }

    $proposalModel = new ProductProposal();
    $proposal = $proposalModel->supplierRespond($id, $supplier['id'], $action, Auth::userId(), $data);

    // Notify whoever proposed it (their own tracking page, by role) and
    // every active Owner (their approval queue).
    $stmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id = ?");
    $stmt->execute([$proposal['proposed_by']]);
    $proposer = $stmt->fetch();
    $proposerLink = ($proposer && $proposer['role'] === 'owner') ? '?page=owner_product_proposals' : '?page=store_manager_product_proposals';

    $confirmMsg = "A supplier confirmed \"{$proposal['proposed_name']}\" -- ready for final approval.";
    $declineMsg = "A supplier declined \"{$proposal['proposed_name']}\".";
    $message = $action === 'confirm' ? $confirmMsg : $declineMsg;

    if ($proposer) {
        createNotification($proposer['user_id'], $action === 'confirm' ? 'product_proposal_confirmed' : 'product_proposal_declined', $message, $proposerLink);
    }
    if ($action === 'confirm') {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'owner' AND is_active = 1 AND user_id != ?");
        $stmt->execute([$proposal['proposed_by']]);
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $ownerId) {
            createNotification((int)$ownerId, 'product_proposal_confirmed', $confirmMsg, '?page=owner_product_proposals');
        }
    }

    Response::success([], $action === 'confirm' ? 'Confirmed -- sent to Owner for approval.' : 'Proposal declined.');
} catch (Exception $e) {
    error_log('product_proposals/respond.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
