<?php
// app/handlers/shared/product_proposals/create.php
// Store Manager or Owner proposes carrying a new product from a given
// supplier -- the first of three gates before anything reaches Inventory
// (supplier confirms next, then Owner gives final sign-off).

require_once __DIR__ . '/../../../models/ProductProposal.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\ProductProposal;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isStoreManager() && !Auth::isOwner() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$input = json_decode(file_get_contents('php://input'), true);
$name = isset($input['proposed_name']) ? trim($input['proposed_name']) : '';
$barcode = isset($input['proposed_barcode']) ? trim($input['proposed_barcode']) : '';
$supplierId = isset($input['supplier_id']) ? intval($input['supplier_id']) : 0;
$categoryId = isset($input['category_id']) && intval($input['category_id']) > 0 ? intval($input['category_id']) : null;
$price = isset($input['proposed_price']) ? floatval($input['proposed_price']) : 0;
$description = isset($input['description']) ? trim($input['description']) : null;

if (empty($name) || strlen($name) > 100) {
    Response::error('A product name (up to 100 characters) is required.', 400);
}
if (empty($barcode) || strlen($barcode) > 50) {
    Response::error('A barcode (up to 50 characters) is required.', 400);
}
if ($supplierId <= 0) {
    Response::error('Please pick a supplier.', 400);
}
if ($price <= 0) {
    Response::error('A selling price is required.', 400);
}

try {
    $proposalModel = new ProductProposal();

    if ($proposalModel->barcodeExists($barcode)) {
        Response::error('A product with this barcode already exists.', 400);
    }

    $id = $proposalModel->create([
        'proposed_by' => Auth::userId(),
        'supplier_id' => $supplierId,
        'proposed_name' => $name,
        'proposed_barcode' => $barcode,
        'category_id' => $categoryId,
        'proposed_price' => $price,
        'description' => $description,
    ]);

    // Notify every active user at this supplier company.
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT u.user_id FROM users u
        JOIN suppliers s ON s.email = u.email
        WHERE s.id = ? AND u.is_active = 1
    ");
    $stmt->execute([$supplierId]);
    foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $supplierUserId) {
        createNotification(
            $supplierUserId,
            'product_proposal_received',
            "A new product has been proposed for you to carry: \"{$name}\".",
            '?page=supplier_product_proposals'
        );
    }

    Response::success(['id' => $id], 'Product proposal sent to the supplier.');
} catch (Exception $e) {
    error_log('product_proposals/create.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
