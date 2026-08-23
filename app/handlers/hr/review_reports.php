<?php
// app/handlers/hr/review_reports.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR can review reports (not trainees)
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot review reports.');
}

$input = json_decode(file_get_contents('php://input'), true);
$traineeId = isset($input['trainee_id']) ? intval($input['trainee_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';

if ($traineeId <= 0 || !in_array($action, ['eligible', 'terminate'])) {
    Response::error('Invalid request', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id, report_1, report_2, report_3, reports_status, applicant_id, user_id, trainer_id FROM trainees WHERE id = ?");
    $stmt->execute([$traineeId]);
    $trainee = $stmt->fetch();
    
    if (!$trainee) {
        Response::notFound('Trainee not found');
    }
    
    if (empty($trainee['report_1']) || empty($trainee['report_2']) || empty($trainee['report_3'])) {
        Response::error('All 3 reports must be submitted before reviewing.', 400);
    }
    
    if ($action === 'eligible') {
        if ($trainee['trainer_id']) {
            $stmt = $db->prepare("UPDATE users SET can_train = 1, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$trainee['trainer_id']]);
            error_log('✅ Trainer released: user_id = ' . $trainee['trainer_id']);
        }
        
        $stmt = $db->prepare("
            UPDATE trainees 
            SET eligible_for_contract = 1, 
                reports_status = 'reviewed', 
                status = 'completed',
                training_completed_at = NOW(),
                trainer_released_at = NOW(),
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$traineeId]);
        
        $stmt = $db->prepare("UPDATE applicants SET status = 'screening_success', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$trainee['applicant_id']]);
        
        $message = 'Trainee completed training and is now eligible for contract. Trainer has been released.';
        
    } else {
        if ($trainee['trainer_id']) {
            $stmt = $db->prepare("UPDATE users SET can_train = 1, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$trainee['trainer_id']]);
            error_log('✅ Trainer released (termination): user_id = ' . $trainee['trainer_id']);
        }
        
        $stmt = $db->prepare("
            UPDATE trainees 
            SET status = 'terminated', 
                eligible_for_contract = 0, 
                reports_status = 'reviewed', 
                trainer_released_at = NOW(),
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$traineeId]);
        
        $stmt = $db->prepare("UPDATE applicants SET status = 'screening_failed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$trainee['applicant_id']]);
        
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
        $stmt->execute([$trainee['user_id']]);
        
        $message = 'Trainee terminated. Trainer has been released.';
    }
    
    createNotification(Auth::userId(), 'reports_reviewed', "Trainee reviewed: " . $action . ". Trainer released.");
    
    Response::success([
        'trainee_id' => $traineeId,
        'action' => $action,
        'trainer_released' => true
    ], $message);

} catch (Exception $e) {
    error_log('review_reports.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}