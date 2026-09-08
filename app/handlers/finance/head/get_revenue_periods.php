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

if (!function_exists('revenue_periods_build_data')) {
/**
 * Builds the semi-monthly period list (with draft/applied split status)
 * for a month. Shared by the API endpoint and the Revenue Split page's
 * first paint.
 */
function revenue_periods_build_data(RevenueSplit $model, int $year, int $month): array {
    $halves = $model->getHalves($year, $month);

    foreach ($halves as &$half) {
        $draft = $model->getDraftForPeriod($half['start_date'], $half['end_date']);
        $applied = $model->getAppliedForPeriod($half['start_date'], $half['end_date']);
        $half['draft'] = $draft ?: null;
        $half['applied'] = $applied ?: null;
    }

    return [
        'year' => $year,
        'month' => $month,
        'halves' => $halves
    ];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
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
        Response::success(revenue_periods_build_data(new RevenueSplit(), $year, $month), 'Revenue periods fetched');
    } catch (Exception $e) {
        error_log('get_revenue_periods.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
