<?php
// app/handlers/finance/head/get_revenue_periods.php
// Same semi-monthly cutoff split used by payroll (1-15/16, 16-30/31,
// calendar-aware for February), reused here for revenue periods.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/RevenueSplit.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\RevenueSplit;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required.');
}

$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));

if ($month < 1 || $month > 12) {
    Response::error('Invalid month', 400);
}
if ($year < 2000 || $year > 2100) {
    Response::error('Invalid year', 400);
}

try {
    $model = new RevenueSplit();
    $halves = $model->getHalves($year, $month);

    foreach ($halves as &$half) {
        $draft = $model->getDraftForPeriod($half['start_date'], $half['end_date']);
        $applied = $model->getAppliedForPeriod($half['start_date'], $half['end_date']);
        $half['draft'] = $draft ?: null;
        $half['applied'] = $applied ?: null;
    }

    Response::success([
        'year' => $year,
        'month' => $month,
        'halves' => $halves
    ], 'Revenue periods fetched');
} catch (Exception $e) {
    error_log('get_revenue_periods.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
