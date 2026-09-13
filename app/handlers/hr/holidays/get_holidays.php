<?php
// app/handlers/hr/holidays/get_holidays.php

require_once __DIR__ . '/../../../models/Holiday.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Holiday;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : null;

try {
    $holidayModel = new Holiday();
    Response::success(['holidays' => $holidayModel->getAll($year)], 'Holidays fetched successfully');
} catch (Exception $e) {
    error_log('get_holidays.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
