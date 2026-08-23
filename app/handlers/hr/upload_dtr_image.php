<?php
// app/handlers/hr/upload_dtr_image.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/AttendanceWeeklySummary.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\AttendanceWeeklySummary;

header('Content-Type: application/json');

error_log('=== DTR UPLOAD START ===');

if (!Auth::check()) {
    error_log('Unauthorized');
    Response::unauthorized('Please login');
}

if (!Auth::canAccessModule('hr_head')) {
    error_log('Forbidden');
    Response::forbidden('Access denied. HR role required.');
}

if (!isset($_FILES['dtr_image']) || $_FILES['dtr_image']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['dtr_image']['error'] ?? 'unknown';
    error_log("File error: $err");
    Response::error('File upload error. Code: ' . $err, 400);
}

$userId = intval($_POST['user_id'] ?? 0);
$weekStart = trim($_POST['week_start'] ?? '');

if ($userId <= 0 || empty($weekStart)) {
    error_log("Missing user_id or week_start");
    Response::error('Missing parameters', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id, status FROM attendance_weekly_summaries WHERE user_id = ? AND week_start_date = ?");
    $stmt->execute([$userId, $weekStart]);
    $summary = $stmt->fetch();

    if (!$summary) {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        $monthYear = date('Y-m', strtotime($weekStart));
        $monthStart = new DateTime(date('Y-m-01', strtotime($weekStart)));
        $weekStartDT = new DateTime($weekStart);
        $diff = $monthStart->diff($weekStartDT);
        $days = $diff->days;
        if ($days >= 0 && $days < 8) $weekNum = 1;
        elseif ($days >= 8 && $days < 16) $weekNum = 2;
        elseif ($days >= 16 && $days < 24) $weekNum = 3;
        else $weekNum = 4;
        if ($weekNum > 4) $weekNum = 4;

        $summaryModel = new AttendanceWeeklySummary();
        $result = $summaryModel->generateForUser($userId, $weekStart, $weekEnd, $weekNum, $monthYear);
        if (!$result) {
            error_log("Failed to generate summary");
            Response::error('Failed to generate summary', 500);
        }
        $stmt = $db->prepare("SELECT id, status FROM attendance_weekly_summaries WHERE user_id = ? AND week_start_date = ?");
        $stmt->execute([$userId, $weekStart]);
        $summary = $stmt->fetch();
        if (!$summary) {
            error_log("Could not fetch summary after generation");
            Response::error('Could not create summary', 500);
        }
    }

    if (in_array($summary['status'], ['sent', 'locked', 'approved'])) {
        error_log("Week locked: " . $summary['status']);
        Response::error('Week is locked', 400);
    }

    $year = date('Y', strtotime($weekStart));
    $month = date('m', strtotime($weekStart));
    $monthStart = new DateTime(date('Y-m-01', strtotime($weekStart)));
    $weekStartDT = new DateTime($weekStart);
    $diff = $monthStart->diff($weekStartDT);
    $days = $diff->days;
    if ($days >= 0 && $days < 8) $weekNum = 1;
    elseif ($days >= 8 && $days < 16) $weekNum = 2;
    elseif ($days >= 16 && $days < 24) $weekNum = 3;
    else $weekNum = 4;
    if ($weekNum > 4) $weekNum = 4;

    $baseDir = 'C:/xampp/htdocs/ShelfSense/public/uploads/dtr/';
    $folder = $year . '/' . $month . '/week' . $weekNum . '/';
    $fullPath = $baseDir . $folder;

    error_log("Full path: $fullPath");

    if (!is_dir($fullPath)) {
        $created = mkdir($fullPath, 0777, true);
        error_log("mkdir result: " . ($created ? 'success' : 'failed'));
        if (!$created) {
            error_log("mkdir failed. Error: " . error_get_last()['message']);
            Response::error('Failed to create directory: ' . $fullPath, 500);
        }
    }

    $file = $_FILES['dtr_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowed)) {
        error_log("Invalid file type: $ext");
        Response::error('Invalid file type', 400);
    }

    $filename = time() . '_' . $userId . '.' . $ext;
    $dest = $fullPath . $filename;
    error_log("Destination: $dest");

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        if (file_exists($dest)) {
            error_log("✅ File confirmed at: $dest");
            error_log("File size: " . filesize($dest));
            $relativePath = 'uploads/dtr/' . $folder . $filename;
            $stmt = $db->prepare("UPDATE attendance_weekly_summaries SET dtr_image_path = ? WHERE id = ?");
            if ($stmt->execute([$relativePath, $summary['id']])) {
                error_log("Database updated");
                Response::success(['dtr_image_path' => $relativePath], 'Upload success');
            } else {
                unlink($dest);
                error_log("DB update failed");
                Response::error('Database update failed', 500);
            }
        } else {
            error_log("❌ File does NOT exist at: $dest");
            error_log("❌ move_uploaded_file returned true but file is missing!");
            Response::error('File was not saved. Check permissions.', 500);
        }
    } else {
        error_log("move_uploaded_file failed");
        Response::error('Failed to move file. Check permissions.', 500);
    }
} catch (Exception $e) {
    error_log('EXCEPTION: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}