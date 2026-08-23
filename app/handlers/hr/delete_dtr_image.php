<?php
// app/handlers/hr/delete_dtr_image.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$weekStart = isset($input['week_start']) ? trim($input['week_start']) : '';

if ($userId <= 0 || empty($weekStart)) {
    Response::error('Missing user_id or week_start.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id, dtr_image_path, status FROM attendance_weekly_summaries WHERE user_id = ? AND week_start_date = ?");
    $stmt->execute([$userId, $weekStart]);
    $summary = $stmt->fetch();

    if (!$summary) {
        Response::error('Attendance summary not found.', 404);
    }

    if (in_array($summary['status'], ['sent', 'locked', 'approved'])) {
        Response::error('Cannot delete DTR for a locked week.', 400);
    }

    if (empty($summary['dtr_image_path'])) {
        Response::error('No DTR image to delete.', 400);
    }

    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/ShelfSense/public/' . $summary['dtr_image_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
        error_log("Deleted file: $filePath");
    } else {
        error_log("File not found: $filePath");
    }

    $stmt = $db->prepare("UPDATE attendance_weekly_summaries SET dtr_image_path = NULL WHERE id = ?");
    $stmt->execute([$summary['id']]);

    Response::success([], 'DTR image deleted successfully.');
} catch (Exception $e) {
    error_log('delete_dtr_image.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}