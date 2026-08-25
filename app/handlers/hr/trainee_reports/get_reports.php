<?php
// app/handlers/hr/trainee_reports/get_reports.php

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

$traineeId = isset($_GET['trainee_id']) ? intval($_GET['trainee_id']) : 0;
if ($traineeId <= 0) {
    Response::error('Invalid trainee ID', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM trainees WHERE id = ?");
    $stmt->execute([$traineeId]);
    $trainee = $stmt->fetch();
    if (!$trainee) {
        Response::notFound('Trainee not found');
    }

    // Trainees may view only their own reports; everyone else must be HR/the
    // relevant department reviewer -- enforced generously here since this is
    // a read-only listing (write actions are separately gated per-handler).
    $isSelf = (int)$trainee['user_id'] === (int)Auth::userId();
    if (!$isSelf && !Auth::isHR() && !Auth::isStoreManager() && !Auth::isFinanceHead() && !Auth::isSuperAdmin() && (int)$trainee['trainer_id'] !== (int)Auth::userId()) {
        Response::forbidden('Access denied.');
    }

    $stmt = $db->prepare("
        SELECT tr.*, t1.first_name as trainer_first, t1.last_name as trainer_last,
               t2.first_name as reviewer_first, t2.last_name as reviewer_last,
               t3.first_name as hrhead_first, t3.last_name as hrhead_last
        FROM trainee_reports tr
        LEFT JOIN users t1 ON tr.trainer_id = t1.user_id
        LEFT JOIN users t2 ON tr.reviewer_id = t2.user_id
        LEFT JOIN users t3 ON tr.hr_head_id = t3.user_id
        WHERE tr.trainee_id = ?
        ORDER BY tr.week_number ASC
    ");
    $stmt->execute([$traineeId]);
    $reports = $stmt->fetchAll();

    // Expected week count derived from the trainee's real start/end dates,
    // not a hard-coded "12 weeks" assumption.
    $start = new DateTime($trainee['start_date']);
    $end = new DateTime($trainee['end_date']);
    $totalDays = max(1, $start->diff($end)->days);
    $expectedWeeks = (int)ceil($totalDays / 7);

    $submittedWeeks = array_column($reports, 'week_number');
    $missingWeeks = [];
    for ($w = 1; $w <= $expectedWeeks; $w++) {
        if (!in_array($w, $submittedWeeks, true)) {
            $missingWeeks[] = $w;
        }
    }

    Response::success([
        'reports' => $reports,
        'expected_weeks' => $expectedWeeks,
        'submitted_weeks' => count($reports),
        'missing_weeks' => $missingWeeks,
        'all_reports_complete' => empty($missingWeeks),
        'all_forwarded' => count(array_filter($reports, fn($r) => in_array($r['status'], ['forwarded', 'hr_reviewed'], true))) === count($reports) && !empty($reports)
    ], 'Trainee reports fetched');

} catch (Exception $e) {
    error_log('get_reports.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
