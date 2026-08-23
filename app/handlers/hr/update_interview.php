<?php
// app/handlers/hr/update_interview.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Block all trainees from updating interviews
if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot update interviews. Please ask a full HR staff.');
}

if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$interviewId = isset($input['interview_id']) ? intval($input['interview_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';
$result = isset($input['result']) ? trim($input['result']) : '';

if ($interviewId <= 0 || empty($action)) {
    Response::error('Missing required fields', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT i.*, a.id as applicant_id, a.status as applicant_status 
                          FROM interviews i 
                          JOIN applicants a ON i.applicant_id = a.id 
                          WHERE i.id = ?");
    $stmt->execute([$interviewId]);
    $interview = $stmt->fetch();

    if (!$interview) {
        Response::notFound('Interview not found');
    }

    $currentUserId = Auth::userId();

    switch ($action) {
        case 'complete':
            $stmt = $db->prepare("UPDATE interviews SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$interviewId]);
            $message = 'Interview marked as completed';
            break;

        case 'cancel':
            $stmt = $db->prepare("UPDATE interviews SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$interviewId]);
            $message = 'Interview cancelled';
            break;

        case 'set_result':
            if (!in_array($result, ['passed', 'failed'])) {
                Response::error('Invalid result value', 400);
            }

            $stmt = $db->prepare("UPDATE interviews SET status = 'completed', result = ?, notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$result, $input['notes'] ?? null, $interviewId]);

            if ($interview['interview_type'] === 'initial') {
                $newStatus = $result === 'passed' ? 'initial_passed' : 'initial_failed';
            } elseif ($interview['interview_type'] === 'final') {
                $newStatus = $result === 'passed' ? 'final_passed' : 'final_failed';
            } else {
                $newStatus = '';
            }

            if (!empty($newStatus)) {
                $stmt = $db->prepare("UPDATE applicants SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $interview['applicant_id']]);
            }

            $message = 'Interview result set to: ' . ucfirst($result);
            break;

        default:
            Response::error('Invalid action', 400);
    }

    Response::success([
        'interview_id' => $interviewId,
        'action' => $action,
        'result' => $result ?? null
    ], $message);

} catch (Exception $e) {
    error_log('update_interview.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}