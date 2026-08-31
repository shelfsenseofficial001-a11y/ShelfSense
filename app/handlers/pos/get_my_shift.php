<?php
// app/handlers/pos/get_my_shift.php
// The logged-in cashier's schedule for today, shown in the POS top bar.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Schedule.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Schedule;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

try {
    $dayOfWeek = strtolower(date('l'));
    $scheduleModel = new Schedule();
    $today = $scheduleModel->getScheduleByDay(Auth::userId(), $dayOfWeek);

    Response::success([
        'day' => $dayOfWeek,
        'is_rest_day' => $today ? (bool)$today['is_rest_day'] : true,
        'time_in' => $today['time_in'] ?? null,
        'time_out' => $today['time_out'] ?? null
    ], 'Shift fetched successfully');

} catch (Exception $e) {
    error_log('get_my_shift.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
