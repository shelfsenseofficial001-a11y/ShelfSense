<?php
// app/handlers/hr/sync_schedule_from_contract.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Allow HR, SuperAdmin, and HR trainees
$targetRole = Auth::getNormalizedTargetRole();
$isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);

if (!Auth::canAccessModule('hr_head') && !$isHrTrainee) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;

if ($userId <= 0) {
    Response::error('Invalid user ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT c.shift, c.rest_days 
        FROM contracts c
        WHERE c.user_id = ? AND c.status = 'accepted'
        ORDER BY c.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $contract = $stmt->fetch();

    if (!$contract) {
        Response::error('No active contract found for this employee.', 404);
    }

    $shift = $contract['shift'];
    $restDaysStr = $contract['rest_days'] ?? '';
    $restDaysArray = !empty($restDaysStr) ? explode(',', $restDaysStr) : [];

    $shiftHours = [
        'opening' => ['08:00:00', '17:00:00'],
        'closing' => ['14:00:00', '22:00:00'],
        'midshift' => ['10:00:00', '18:00:00']
    ];
    $hours = $shiftHours[$shift] ?? ['08:00:00', '17:00:00'];

    $stmt = $db->prepare("DELETE FROM schedules WHERE user_id = ?");
    $stmt->execute([$userId]);

    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    foreach ($days as $day) {
        $isRestDay = in_array($day, $restDaysArray) ? 1 : 0;
        $timeIn = $isRestDay ? '00:00:00' : $hours[0];
        $timeOut = $isRestDay ? '00:00:00' : $hours[1];
        $stmt = $db->prepare("
            INSERT INTO schedules (user_id, day_of_week, time_in, time_out, is_rest_day) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $day, $timeIn, $timeOut, $isRestDay]);
    }

    Response::success([
        'user_id' => $userId,
        'shift' => $shift,
        'rest_days' => $restDaysStr,
        'message' => 'Schedule synced from contract successfully.'
    ], 'Schedule synced from contract.');

} catch (Exception $e) {
    error_log('sync_schedule_from_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}