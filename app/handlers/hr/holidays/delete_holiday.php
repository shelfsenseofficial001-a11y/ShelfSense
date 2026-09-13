<?php
// app/handlers/hr/holidays/delete_holiday.php

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

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    Response::error('Missing holiday id.', 400);
}

try {
    $holidayModel = new Holiday();
    $holidayModel->delete($id);
    Response::success([], 'Holiday removed');
} catch (Exception $e) {
    error_log('delete_holiday.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
