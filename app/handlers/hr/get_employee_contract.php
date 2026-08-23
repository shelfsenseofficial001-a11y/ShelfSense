<?php
// app/handlers/hr/get_employee_contract.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($userId <= 0) {
    Response::error('Invalid user ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT c.*, a.target_role, a.first_name, a.last_name 
        FROM contracts c
        JOIN applicants a ON c.applicant_id = a.id
        WHERE c.user_id = ? AND c.status = 'accepted'
        ORDER BY c.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $contract = $stmt->fetch();

    if (!$contract) {
        Response::success([
            'has_contract' => false,
            'message' => 'No active contract found for this employee.'
        ], 'No contract found');
        exit;
    }

    $shiftHours = [
        'opening' => ['label' => 'Opening', 'time' => '6:00 AM - 2:00 PM'],
        'closing' => ['label' => 'Closing', 'time' => '2:00 PM - 10:00 PM'],
        'midshift' => ['label' => 'MidShift', 'time' => '10:00 AM - 6:00 PM']
    ];

    $contract['shift_label'] = $shiftHours[$contract['shift']]['label'] ?? ucfirst($contract['shift']);
    $contract['shift_time'] = $shiftHours[$contract['shift']]['time'] ?? '';

    Response::success([
        'has_contract' => true,
        'contract' => $contract
    ], 'Contract fetched successfully');

} catch (Exception $e) {
    error_log('get_employee_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}