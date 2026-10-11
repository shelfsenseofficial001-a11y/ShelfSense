<?php
// app/handlers/hr/trainee_reports/get_daily_logs.php
// Everything the day-grid needs to render whichever week is currently open
// for one trainee: all saved daily logs, the open week number, and rest
// days -- in one call.

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

    // Same access rule as get_reports.php: the trainee themselves, their
    // trainer, a department reviewer, HR, or SuperAdmin.
    $isSelf = (int)$trainee['user_id'] === (int)Auth::userId();
    if (!$isSelf && !Auth::isHR() && !Auth::isStoreManager() && !Auth::isFinanceHead() && !Auth::isSuperAdmin() && (int)$trainee['trainer_id'] !== (int)Auth::userId()) {
        Response::forbidden('Access denied.');
    }

    // The open week is the first one without a finalized trainee_reports row.
    $stmt = $db->prepare("SELECT week_number FROM trainee_reports WHERE trainee_id = ?");
    $stmt->execute([$traineeId]);
    $submittedWeeks = array_column($stmt->fetchAll(), 'week_number');
    $expectedWeeks = 12;
    $currentOpenWeek = null;
    for ($w = 1; $w <= $expectedWeeks; $w++) {
        if (!in_array($w, $submittedWeeks, true)) {
            $currentOpenWeek = $w;
            break;
        }
    }

    $stmt = $db->prepare("SELECT log_date, day_name, content, updated_at FROM trainee_daily_logs WHERE trainee_id = ? ORDER BY log_date ASC");
    $stmt->execute([$traineeId]);
    $logs = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT day_of_week FROM schedules WHERE user_id = ? AND is_rest_day = 1");
    $stmt->execute([$trainee['user_id']]);
    $restDays = array_column($stmt->fetchAll(), 'day_of_week');

    Response::success([
        'daily_logs' => $logs,
        'current_open_week' => $currentOpenWeek,
        'submitted_weeks' => count($submittedWeeks),
        'expected_weeks' => $expectedWeeks,
        'trainee_start_date' => $trainee['start_date'],
        'trainee_end_date' => $trainee['end_date'],
        'trainee_rest_days' => $restDays
    ], 'Daily logs fetched');

} catch (Exception $e) {
    error_log('get_daily_logs.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
