<?php
// app/handlers/hr/update_trainee.php

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

if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot update trainee records.');
}

$input = json_decode(file_get_contents('php://input'), true);
$traineeId = isset($input['trainee_id']) ? intval($input['trainee_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$trainerId = isset($input['trainer_id']) ? intval($input['trainer_id']) : 0;

if ($traineeId <= 0 || empty($action)) {
    Response::error('Missing required fields', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT t.*, a.id as applicant_id, a.status as applicant_status 
                          FROM trainees t 
                          JOIN applicants a ON t.applicant_id = a.id 
                          WHERE t.id = ?");
    $stmt->execute([$traineeId]);
    $trainee = $stmt->fetch();

    if (!$trainee) {
        Response::notFound('Trainee not found');
    }

    switch ($action) {
        case 'assign_trainer':
            if ($trainerId <= 0) {
                Response::error('Please select a trainer', 400);
            }
            
            $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1 AND can_train = 1");
            $stmt->execute([$trainerId]);
            $trainer = $stmt->fetch();
            
            if (!$trainer) {
                Response::error('Invalid trainer selected', 400);
            }
            
            $stmt = $db->prepare("UPDATE trainees SET trainer_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$trainerId, $traineeId]);
            $message = 'Trainer assigned successfully';
            break;

        case 'complete':
            $stmt = $db->prepare("UPDATE trainees SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$traineeId]);
            
            $stmt = $db->prepare("UPDATE applicants SET status = 'screening_success', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$trainee['applicant_id']]);
            
            $message = 'Training marked as completed';
            break;

        case 'terminate':
            $stmt = $db->prepare("UPDATE trainees SET status = 'terminated', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$traineeId]);
            
            $stmt = $db->prepare("UPDATE applicants SET status = 'screening_failed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$trainee['applicant_id']]);
            
            $message = 'Trainee terminated';
            break;

        default:
            Response::error('Invalid action', 400);
    }

    Response::success([
        'trainee_id' => $traineeId,
        'action' => $action
    ], $message);

} catch (Exception $e) {
    error_log('update_trainee.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}