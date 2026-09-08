<?php
// app/handlers/hr/get_recruitment_calendar.php
// Returns every recruitment-workflow event (job posting opened/closed dates,
// scheduled interviews) that falls within a given month, for the
// Recruitment Calendar prototype view.

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

if (!Auth::isHR() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. HR role required.');
}

try {
    $db = Database::getInstance()->getConnection();

    $month = isset($_GET['month']) ? trim($_GET['month']) : date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        Response::error('Invalid month format, expected YYYY-MM.', 400);
    }

    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    $events = [];

    // Job postings opened this month
    $stmt = $db->prepare("
        SELECT jp.id, jp.title, jp.department, DATE(jp.approved_at) AS event_date,
               u.first_name AS by_first_name, u.last_name AS by_last_name
        FROM job_postings jp
        LEFT JOIN users u ON u.user_id = jp.approved_by
        WHERE jp.approved_at IS NOT NULL
          AND DATE(jp.approved_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$monthStart, $monthEnd]);
    foreach ($stmt->fetchAll() as $row) {
        $events[] = [
            'date' => $row['event_date'],
            'type' => 'posting_opened',
            'label' => 'Opened ' . $row['title'],
            'time' => null,
            'meta' => trim(($row['by_first_name'] ?? '') . ' ' . ($row['by_last_name'] ?? '')),
        ];
    }

    // Job postings closing (application deadline) this month
    $stmt = $db->prepare("
        SELECT jp.id, jp.title, jp.department, jp.open_until AS event_date
        FROM job_postings jp
        WHERE jp.status IN ('approved', 'closed')
          AND jp.open_until BETWEEN ? AND ?
    ");
    $stmt->execute([$monthStart, $monthEnd]);
    foreach ($stmt->fetchAll() as $row) {
        $events[] = [
            'date' => $row['event_date'],
            'type' => 'posting_closes',
            'label' => 'Closes ' . $row['title'],
            'time' => null,
            'meta' => null,
        ];
    }

    // Scheduled interviews (initial/final) this month
    $stmt = $db->prepare("
        SELECT i.id, i.interview_type, i.scheduled_date, i.status,
               a.first_name AS applicant_first_name, a.last_name AS applicant_last_name,
               u.first_name AS hr_first_name, u.last_name AS hr_last_name
        FROM interviews i
        JOIN applicants a ON a.id = i.applicant_id
        LEFT JOIN users u ON u.user_id = i.hr_user_id
        WHERE i.status != 'cancelled'
          AND DATE(i.scheduled_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$monthStart, $monthEnd]);
    foreach ($stmt->fetchAll() as $row) {
        $applicantName = trim($row['applicant_first_name'] . ' ' . $row['applicant_last_name']);
        $hrName = trim(($row['hr_first_name'] ?? '') . ' ' . ($row['hr_last_name'] ?? ''));
        $events[] = [
            'date' => date('Y-m-d', strtotime($row['scheduled_date'])),
            'type' => $row['interview_type'] === 'final' ? 'final_interview' : 'initial_interview',
            'label' => $applicantName,
            'time' => date('g:i A', strtotime($row['scheduled_date'])),
            'meta' => $hrName !== '' ? $hrName : null,
        ];
    }

    usort($events, function ($a, $b) {
        return strcmp($a['time'] ?? '00:00', $b['time'] ?? '00:00');
    });

    $byDate = [];
    foreach ($events as $event) {
        $byDate[$event['date']][] = $event;
    }

    Response::success([
        'month' => $month,
        'events_by_date' => $byDate,
    ], 'Recruitment calendar fetched successfully');

} catch (Exception $e) {
    error_log('get_recruitment_calendar.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
