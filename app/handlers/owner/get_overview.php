<?php
// app/handlers/owner/get_overview.php
// Prototype Owner dashboard data -- a lightweight overview, not a full
// reporting suite. Built for testing the Owner role end-to-end.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}
if (!Auth::isOwner() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Owner role required.');
}

try {
    $db = Database::getInstance()->getConnection();

    $applicantStats = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'hired' THEN 1 ELSE 0 END) as hired
        FROM applicants
    ")->fetch();

    $traineeStats = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'terminated' THEN 1 ELSE 0 END) as `terminated`
        FROM trainees
    ")->fetch();

    $jobPostingStats = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
        FROM job_postings
    ")->fetch();

    $upcomingFinals = $db->query("
        SELECT i.id, i.scheduled_date, i.gmeet_link, a.first_name, a.last_name, a.target_role
        FROM interviews i
        JOIN applicants a ON a.id = i.applicant_id
        WHERE i.interview_type = 'final' AND i.status = 'scheduled'
        ORDER BY i.scheduled_date ASC
        LIMIT 10
    ")->fetchAll();

    Response::success([
        'applicants' => $applicantStats,
        'trainees' => $traineeStats,
        'job_postings' => $jobPostingStats,
        'upcoming_final_interviews' => $upcomingFinals
    ], 'Owner overview fetched');

} catch (Exception $e) {
    error_log('owner/get_overview.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
