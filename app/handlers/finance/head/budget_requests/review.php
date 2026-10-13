<?php
// app/handlers/finance/head/budget_requests/review.php
// Finance Head approves (applies the requested amount via the same
// Budget::adjustAllocation() the direct "Set Allocation" action uses) or
// rejects a department's budget request.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Budget.php';
require_once __DIR__ . '/../../../../models/BudgetRequest.php';
require_once __DIR__ . '/../../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Budget;
use App\Models\BudgetRequest;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$requestId = isset($input['request_id']) ? intval($input['request_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$rejectionReason = isset($input['rejection_reason']) ? trim($input['rejection_reason']) : '';

if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    Response::error('Invalid request', 400);
}
if ($action === 'reject' && $rejectionReason === '') {
    Response::error('A reason is required to reject a budget request.', 400);
}

try {
    $requestModel = new BudgetRequest();
    $request = $requestModel->getById($requestId);
    if (!$request) {
        Response::notFound('Budget request not found');
    }
    if ($request['status'] !== 'pending') {
        Response::error('This request has already been reviewed. Current status: ' . $request['status'], 400);
    }

    if ($action === 'approve') {
        (new Budget())->adjustAllocation(
            $request['department_id'],
            $request['period_key'],
            $request['requested_amount'],
            Auth::userId(),
            "Approved budget request: {$request['reason']}"
        );
        $requestModel->markApproved($requestId, Auth::userId());
        $message = 'Budget request approved and allocation updated.';
        $notifMsg = "Your budget request for {$request['department_name']} (₱" . number_format($request['requested_amount'], 2) . ") was approved.";
    } else {
        $requestModel->markRejected($requestId, Auth::userId(), $rejectionReason);
        $message = 'Budget request rejected.';
        $notifMsg = "Your budget request for {$request['department_name']} was rejected. Reason: $rejectionReason";
    }

    createNotification($request['requested_by'], 'budget_request_reviewed', $notifMsg, "?page=dashboard");

    Response::success(['request_id' => $requestId, 'status' => $action === 'approve' ? 'approved' : 'rejected'], $message);
} catch (Exception $e) {
    error_log('finance/head/budget_requests/review.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
