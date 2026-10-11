<?php
// app/handlers/hr/trainee_reports/get_review_board.php
// Data source for the reviewer-side "Trainee Report" board: a searchable
// list of trainees scoped to the current reviewer's stage, each with a
// lightweight per-week status array (for the week-tab column) -- full
// report content is fetched on demand via the existing
// api_trainee_get_reports, same as the old modal already did.

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

$role = Auth::role();
$isSuperAdmin = Auth::isSuperAdmin();

// Which departments this role is allowed to see on the board at all. HR
// Staff never sees 'hr' department trainees -- those skip HR Staff
// screening entirely (see add_hr_staff_screening.php).
if ($isSuperAdmin || Auth::isHRHead()) {
    $departments = ['store', 'finance', 'hr'];
} elseif (Auth::isHRStaff()) {
    $departments = ['store', 'finance'];
} elseif (Auth::isStoreManager()) {
    $departments = ['store'];
} elseif (Auth::isFinanceHead()) {
    $departments = ['finance'];
} else {
    Response::success(['active_trainees' => [], 'history_trainees' => []], 'No review board for this role');
}

try {
    $db = Database::getInstance()->getConnection();
    $expectedWeeks = 12;

    // trainees.department doesn't exist as a stored column -- it's derived
    // from target_role via mapRoleToDepartment() -- so weeks are matched to
    // their trainee in PHP below rather than filtered in SQL.
    $stmt = $db->query("SELECT trainee_id, week_number, status FROM trainee_reports");
    $weeksByTrainee = [];
    foreach ($stmt->fetchAll() as $row) {
        $weeksByTrainee[$row['trainee_id']][(int)$row['week_number']] = $row['status'];
    }

    $buildWeeks = function (int $traineeId, string $department) use ($weeksByTrainee, $expectedWeeks, $role, $isSuperAdmin) {
        $weeks = [];
        for ($w = 1; $w <= $expectedWeeks; $w++) {
            $status = $weeksByTrainee[$traineeId][$w] ?? 'not_submitted';
            $actionable = false;
            if ($isSuperAdmin) {
                $actionable = in_array($status, ['submitted', 'forwarded', 'hr_staff_reviewed'], true);
            } elseif ($role === 'store_manager' || $role === 'finance_head') {
                $actionable = $status === 'submitted' && mapDepartmentToReviewerRole($department) === $role;
            } elseif ($role === 'hr_staff') {
                $actionable = $status === 'forwarded' && $department !== 'hr';
            } elseif ($role === 'hr_head') {
                $actionable = $status === 'hr_staff_reviewed' || ($status === 'forwarded' && $department === 'hr');
            }
            $weeks[] = ['week_number' => $w, 'status' => $status, 'actionable_by_me' => $actionable];
        }
        return $weeks;
    };

    $stmt = $db->query("
        SELECT t.id AS trainee_id, t.target_role, t.status, t.eligible_for_contract,
               a.first_name, a.last_name
        FROM trainees t
        JOIN applicants a ON a.id = t.applicant_id
        ORDER BY a.first_name, a.last_name
    ");
    $allTrainees = $stmt->fetchAll();

    $activeTrainees = [];
    $historyTrainees = [];
    foreach ($allTrainees as $row) {
        $department = mapRoleToDepartment($row['target_role']);
        if (!in_array($department, $departments, true)) {
            continue;
        }
        $name = trim($row['first_name'] . ' ' . $row['last_name']);

        if ($row['status'] === 'active') {
            $activeTrainees[] = [
                'trainee_id' => (int)$row['trainee_id'],
                'name' => $name,
                'target_role' => $row['target_role'],
                'department' => $department,
                'weeks' => $buildWeeks((int)$row['trainee_id'], $department)
            ];
        } elseif (in_array($row['status'], ['completed', 'terminated'], true)) {
            $historyTrainees[] = [
                'trainee_id' => (int)$row['trainee_id'],
                'name' => $name,
                'target_role' => $row['target_role'],
                'department' => $department,
                'outcome' => (int)$row['eligible_for_contract'] === 1 ? 'accepted' : 'failed',
                // Training has ended, so these weeks are shown read-only --
                // actionable_by_me is always false regardless of status.
                'weeks' => array_map(function ($w) { $w['actionable_by_me'] = false; return $w; }, $buildWeeks((int)$row['trainee_id'], $department))
            ];
        }
    }

    Response::success([
        'active_trainees' => $activeTrainees,
        'history_trainees' => $historyTrainees
    ], 'Review board fetched');

} catch (Exception $e) {
    error_log('get_review_board.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
