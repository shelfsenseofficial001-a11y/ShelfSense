<?php
// app/handlers/hr/trainee_reports/get_my_queue.php
// Powers the "Trainee Report" tab: everything the CURRENT user needs to see
// without already knowing a trainee_id -- their own active trainee (if
// they're a trainer), reports waiting on them as a department reviewer, and
// (HR Head only) reports already forwarded and waiting on HR review.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = Auth::userId();
    $role = Auth::role();

    $stmt = $db->prepare("
        SELECT t.*, a.first_name AS applicant_first, a.last_name AS applicant_last
        FROM trainees t
        JOIN applicants a ON a.id = t.applicant_id
        WHERE t.trainer_id = ? AND t.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $myActiveTrainee = $stmt->fetch() ?: null;

    // Only a real reviewer role ever has a department queue -- anyone else
    // (a plain trainer, a trainee, a supplier) just gets an empty list here.
    $pendingReview = [];
    if (!Auth::isSuperAdmin()) {
        $isReviewerRole = in_array($role, ['store_manager', 'finance_head', 'hr_head'], true);
        if ($isReviewerRole) {
            $stmt = $db->prepare("
                SELECT tr.id, tr.trainee_id, tr.week_number, tr.department, tr.submitted_at,
                       a.first_name AS applicant_first, a.last_name AS applicant_last
                FROM trainee_reports tr
                JOIN trainees t ON t.id = tr.trainee_id
                JOIN applicants a ON a.id = t.applicant_id
                WHERE tr.status = 'submitted'
                ORDER BY tr.submitted_at ASC
            ");
            $stmt->execute();
            $pendingReview = array_values(array_filter($stmt->fetchAll(), function ($r) use ($role) {
                return mapDepartmentToReviewerRole($r['department']) === $role;
            }));
        }
    } else {
        // SuperAdmin can act as any reviewer -- show everything still
        // submitted, across all departments.
        $stmt = $db->query("
            SELECT tr.id, tr.trainee_id, tr.week_number, tr.department, tr.submitted_at,
                   a.first_name AS applicant_first, a.last_name AS applicant_last
            FROM trainee_reports tr
            JOIN trainees t ON t.id = tr.trainee_id
            JOIN applicants a ON a.id = t.applicant_id
            WHERE tr.status = 'submitted'
            ORDER BY tr.submitted_at ASC
        ");
        $pendingReview = $stmt->fetchAll();
    }

    // Store/Finance reports must pass HR Staff screening first; HR
    // department reports skip that stage (trainer is already HR Staff) and
    // land in HR Head's queue straight off a plain 'forwarded'.
    $pendingHrStaffReview = [];
    if (Auth::isHRStaff() || Auth::isSuperAdmin()) {
        $stmt = $db->query("
            SELECT tr.id, tr.trainee_id, tr.week_number, tr.department, tr.forwarded_at,
                   a.first_name AS applicant_first, a.last_name AS applicant_last
            FROM trainee_reports tr
            JOIN trainees t ON t.id = tr.trainee_id
            JOIN applicants a ON a.id = t.applicant_id
            WHERE tr.status = 'forwarded' AND tr.department != 'hr'
            ORDER BY tr.forwarded_at ASC
        ");
        $pendingHrStaffReview = $stmt->fetchAll();
    }

    $pendingHrReview = [];
    if (Auth::isHRHead() || Auth::isSuperAdmin()) {
        $stmt = $db->query("
            SELECT tr.id, tr.trainee_id, tr.week_number, tr.department, tr.forwarded_at,
                   a.first_name AS applicant_first, a.last_name AS applicant_last
            FROM trainee_reports tr
            JOIN trainees t ON t.id = tr.trainee_id
            JOIN applicants a ON a.id = t.applicant_id
            WHERE tr.status = 'hr_staff_reviewed' OR (tr.status = 'forwarded' AND tr.department = 'hr')
            ORDER BY tr.forwarded_at ASC
        ");
        $pendingHrReview = $stmt->fetchAll();
    }

    Response::success([
        'my_active_trainee' => $myActiveTrainee,
        'pending_review' => $pendingReview,
        'pending_hr_staff_review' => $pendingHrStaffReview,
        'pending_hr_review' => $pendingHrReview
    ], 'Trainee report queue fetched');

} catch (Exception $e) {
    error_log('get_my_queue.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
