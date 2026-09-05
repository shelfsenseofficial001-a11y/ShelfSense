<?php
// app/handlers/finance/head/settings/variance_tolerance.php
// GET: fetch current tolerance settings. POST: update them (Finance Head only).

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/VarianceToleranceSettings.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\VarianceToleranceSettings;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$model = new VarianceToleranceSettings();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        Response::success($model->get(), 'Variance tolerance settings fetched successfully');
    } catch (Exception $e) {
        error_log('variance_tolerance.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
    exit;
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$pricePercent = isset($input['price_tolerance_percent']) ? floatval($input['price_tolerance_percent']) : -1;
$priceAmount = isset($input['price_tolerance_amount']) ? floatval($input['price_tolerance_amount']) : -1;
$qtyPercent = isset($input['quantity_tolerance_percent']) ? floatval($input['quantity_tolerance_percent']) : -1;

if ($pricePercent < 0 || $priceAmount < 0 || $qtyPercent < 0) {
    Response::error('All tolerance values must be zero or greater', 400);
}

try {
    $model->update($pricePercent, $priceAmount, $qtyPercent, Auth::userId());
    Response::success($model->get(), 'Variance tolerance settings updated successfully');
} catch (Exception $e) {
    error_log('variance_tolerance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
