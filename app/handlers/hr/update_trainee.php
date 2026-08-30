<?php
// app/handlers/hr/update_trainee.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../helpers/functions.php';

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
$reason = isset($input['reason']) ? trim($input['reason']) : '';
$override = !empty($input['override']);

if ($traineeId <= 0 || empty($action)) {
    Response::error('Missing required fields', 400);
}
if (strlen($reason) > 500) {
    Response::error('Reason cannot exceed 500 characters.', 400);
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
        case 'reassign_trainer':
            if ($trainerId <= 0) {
                Response::error('Please select a trainer', 400);
            }

            $stmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id = ? AND is_active = 1 AND can_train = 1");
            $stmt->execute([$trainerId]);
            $trainer = $stmt->fetch();

            if (!$trainer) {
                Response::error('Invalid trainer selected. The trainer must be active and available to train.', 400);
            }

            // Same role-matching rule used at initial trainee creation
            // (create_trainee_with_trainer.php): the trainer must hold the
            // same real role as the trainee's target role.
            $dbTargetRole = mapDisplayRoleToDbRole($trainee['target_role']);
            if (strtolower($trainer['role']) !== strtolower($dbTargetRole)) {
                Response::error('Trainer must have the same role as the trainee\'s target role.', 400);
            }

            $db->beginTransaction();

            // Close out any previous open assignment so history is preserved,
            // then record the new one -- never overwritten, only superseded.
            $db->prepare("UPDATE trainer_assignments SET unassigned_at = NOW() WHERE trainee_id = ? AND unassigned_at IS NULL")
                ->execute([$traineeId]);

            $db->prepare("UPDATE trainees SET trainer_id = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$trainerId, $traineeId]);

            $db->prepare("INSERT INTO trainer_assignments (trainee_id, trainer_id, assigned_by, reason) VALUES (?, ?, ?, ?)")
                ->execute([$traineeId, $trainerId, Auth::userId(), $reason !== '' ? $reason : null]);

            $db->commit();

            logRecruitmentEvent('trainee', $traineeId, $action, ['notes' => "trainer #{$trainerId}"]);
            $message = $action === 'reassign_trainer' ? 'Trainer reassigned successfully' : 'Trainer assigned successfully';
            break;

        case 'complete':
            // Only HR Head decides training-completion eligibility -- HR Staff
            // may assign trainers and submit/forward reports, but not clear a
            // trainee for the Final Interview.
            if (!Auth::isHRHead() && !Auth::isSuperAdmin()) {
                Response::forbidden('Access denied. HR Head role required to mark a trainee eligible.');
            }

            // HR Head cannot mark eligible if required weekly reports are
            // missing or haven't all reached HR Head/forwarded stage, unless
            // explicitly overridden with a reason.
            $stmt = $db->prepare("SELECT week_number, status FROM trainee_reports WHERE trainee_id = ?");
            $stmt->execute([$traineeId]);
            $reports = $stmt->fetchAll();

            // Training is fixed at exactly 3 months / 12 weekly reports.
            $expectedWeeks = 12;
            $submittedWeeks = array_column($reports, 'week_number');
            $missing = array_diff(range(1, $expectedWeeks), $submittedWeeks);
            $notForwarded = array_filter($reports, fn($r) => !in_array($r['status'], ['forwarded', 'hr_reviewed'], true));

            if ((!empty($missing) || !empty($notForwarded)) && !$override) {
                Response::error(
                    'Cannot mark eligible: ' . count($missing) . ' missing report(s) and ' . count($notForwarded) . ' not yet forwarded. Provide an override reason to proceed anyway.',
                    400,
                    ['missing_weeks' => array_values($missing), 'not_forwarded' => count($notForwarded)]
                );
            }
            if ($override && $reason === '') {
                Response::error('An override reason is required to mark this trainee eligible with incomplete reports.', 400);
            }

            $stmt = $db->prepare("
                UPDATE trainees
                SET status = 'completed', eligible_for_contract = 1, reports_status = 'reviewed',
                    training_completed_at = NOW(), trainer_released_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$traineeId]);

            if ($trainee['trainer_id']) {
                $db->prepare("UPDATE users SET can_train = 1, updated_at = NOW() WHERE user_id = ?")->execute([$trainee['trainer_id']]);
                $db->prepare("UPDATE trainer_assignments SET unassigned_at = NOW() WHERE trainee_id = ? AND unassigned_at IS NULL")->execute([$traineeId]);
            }

            $stmt = $db->prepare("UPDATE applicants SET status = 'screening_success', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$trainee['applicant_id']]);

            logRecruitmentEvent('applicant', $trainee['applicant_id'], 'trainee_contract_completed', [
                'previous_status' => 'screening', 'new_status' => 'screening_success',
                'reason' => $override ? $reason : null
            ]);
            $message = 'Training marked as completed. Trainer released. Eligible for Final Interview.';
            break;

        case 'terminate':
            if (!Auth::isHRHead() && !Auth::isSuperAdmin()) {
                Response::forbidden('Access denied. HR Head role required to terminate a trainee.');
            }
            if ($reason === '') {
                Response::error('A termination reason is required.', 400);
            }

            $stmt = $db->prepare("
                UPDATE trainees
                SET status = 'terminated', eligible_for_contract = 0, reports_status = 'reviewed',
                    trainer_released_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$traineeId]);

            if ($trainee['trainer_id']) {
                $db->prepare("UPDATE users SET can_train = 1, updated_at = NOW() WHERE user_id = ?")->execute([$trainee['trainer_id']]);
            }

            $stmt = $db->prepare("UPDATE applicants SET status = 'screening_failed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$trainee['applicant_id']]);

            $db->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?")->execute([$trainee['user_id']]);

            $db->prepare("UPDATE trainer_assignments SET unassigned_at = NOW() WHERE trainee_id = ? AND unassigned_at IS NULL")
                ->execute([$traineeId]);

            logRecruitmentEvent('applicant', $trainee['applicant_id'], 'trainee_contract_terminated', [
                'previous_status' => 'screening', 'new_status' => 'screening_failed', 'reason' => $reason
            ]);
            $message = 'Trainee terminated. Trainer released.';
            break;

        default:
            Response::error('Invalid action', 400);
    }

    Response::success([
        'trainee_id' => $traineeId,
        'action' => $action
    ], $message);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('update_trainee.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
