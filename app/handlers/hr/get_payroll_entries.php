<?php
// app/handlers/hr/get_payroll_entries.php

require_once __DIR__ . '/../../models/PayrollEntry.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollEntry;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$cycleId = isset($_GET['cycle_id']) ? intval($_GET['cycle_id']) : 0;

if ($cycleId <= 0) {
    Response::error('Invalid cycle ID', 400);
}

try {
    $entryModel = new PayrollEntry();
    $entries = $entryModel->getEntriesForCycle($cycleId);
    
    Response::success([
        'entries' => $entries,
        'cycle_id' => $cycleId
    ], 'Payroll entries fetched successfully');
} catch (Exception $e) {
    error_log('get_payroll_entries.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}