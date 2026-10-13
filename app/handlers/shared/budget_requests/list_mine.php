<?php
// app/handlers/shared/budget_requests/list_mine.php
// A requester's own budget-request history, any status.

require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/BudgetRequest.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\BudgetRequest;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

try {
    $requests = (new BudgetRequest())->getAll(['requested_by' => Auth::userId()]);
    Response::success(['requests' => $requests], 'Budget requests fetched successfully');
} catch (Exception $e) {
    error_log('budget_requests/list_mine.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
