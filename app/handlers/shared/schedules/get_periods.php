<?php
// app/handlers/shared/schedules/get_periods.php
// Populates the H1/H2 cutoff-period picker on both HR's and Store
// Manager's Schedules pages, reusing Budget's CutoffPeriod so period
// boundaries never drift between modules.

require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\CutoffPeriod;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

Response::success([
    'periods' => CutoffPeriod::getRecentHalves(2, 1),
    'current_key' => CutoffPeriod::getCurrentKey()
], 'Periods fetched successfully');
