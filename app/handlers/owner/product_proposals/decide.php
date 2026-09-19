<?php
// app/handlers/owner/product_proposals/decide.php
// Owner's final sign-off: approving creates the real Inventory product
// (zero stock) and its linked supplier_products row in one transaction;
// rejecting is a dead end, logged with a reason.

require_once __DIR__ . '/../../../models/ProductProposal.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\ProductProposal;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isOwner() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Owner role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    Response::error('Missing or invalid request.', 400);
}

$data = [];
if ($action === 'reject') {
    $data['reason'] = isset($input['reason']) ? trim($input['reason']) : '';
    if (empty($data['reason'])) {
        Response::error('Please provide a reason for rejecting.', 400);
    }
}

try {
    $proposalModel = new ProductProposal();
    $proposal = $proposalModel->ownerDecide($id, $action, Auth::userId(), $data);

    // Notify whoever proposed it, and every active user at the supplier.
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$proposal['proposed_by']]);
    $proposerRole = $stmt->fetchColumn();
    $proposerLink = $proposerRole === 'owner' ? '?page=owner_product_proposals' : '?page=store_manager_product_proposals';

    $notificationType = $action === 'approve' ? 'product_proposal_approved' : 'product_proposal_rejected';
    $message = $action === 'approve'
        ? "\"{$proposal['proposed_name']}\" was approved and added to Inventory."
        : "\"{$proposal['proposed_name']}\" was rejected.";
    createNotification((int)$proposal['proposed_by'], $notificationType, $message, $proposerLink);

    $stmt = $db->prepare("
        SELECT u.user_id FROM users u
        JOIN suppliers s ON s.email = u.email
        WHERE s.id = ? AND u.is_active = 1
    ");
    $stmt->execute([$proposal['supplier_id']]);
    foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $supplierUserId) {
        createNotification((int)$supplierUserId, $notificationType, $message, '?page=supplier_product_proposals');
    }

    Response::success([], $action === 'approve' ? 'Approved -- product added to Inventory.' : 'Proposal rejected.');
} catch (Exception $e) {
    error_log('product_proposals/decide.php error: ' . $e->getMessage());
    Response::error($e->getMessage());
}
