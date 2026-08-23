<?php
// app/handlers/hr/submit_report.php

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
$traineeId = isset($input['trainee_id']) ? intval($input['trainee_id']) : 0;
$month = isset($input['month']) ? intval($input['month']) : 0;
$reportText = isset($input['report_text']) ? trim($input['report_text']) : '';

if ($traineeId <= 0 || $month < 1 || $month > 3 || empty($reportText)) {
    Response::error('Missing required fields', 400);
}

if (strlen($reportText) > 250) {
    Response::error('Report text cannot exceed 250 characters.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id, report_1, report_2, report_3, reports_status FROM trainees WHERE id = ?");
    $stmt->execute([$traineeId]);
    $trainee = $stmt->fetch();
    
    if (!$trainee) {
        Response::notFound('Trainee not found');
    }
    
    $column = 'report_' . $month;
    if (!empty($trainee[$column])) {
        Response::error('Report for this month has already been submitted and locked.', 400);
    }
    
    for ($i = 1; $i < $month; $i++) {
        $col = 'report_' . $i;
        if (empty($trainee[$col])) {
            Response::error('Please submit reports in order. Month ' . $i . ' is missing.', 400);
        }
    }
    
    $stmt = $db->prepare("UPDATE trainees SET report_$month = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reportText, $traineeId]);
    
    $stmt = $db->prepare("SELECT report_1, report_2, report_3 FROM trainees WHERE id = ?");
    $stmt->execute([$traineeId]);
    $updated = $stmt->fetch();
    
    $allSubmitted = !empty($updated['report_1']) && !empty($updated['report_2']) && !empty($updated['report_3']);
    if ($allSubmitted) {
        $stmt = $db->prepare("UPDATE trainees SET reports_status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$traineeId]);
    }
    
    createNotification(Auth::userId(), 'report_submitted', "Report submitted for trainee ID: " . $traineeId);
    
    Response::success([
        'trainee_id' => $traineeId,
        'month' => $month
    ], 'Report submitted successfully');

} catch (Exception $e) {
    error_log('submit_report.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}