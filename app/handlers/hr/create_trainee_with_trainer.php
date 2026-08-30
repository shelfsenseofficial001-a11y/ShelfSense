<?php
// app/handlers/hr/create_trainee_with_trainer.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Mailer.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR can create trainees (not trainee HR)
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot create other trainees.');
}

$input = json_decode(file_get_contents('php://input'), true);
error_log('📥 create_trainee_with_trainer input: ' . print_r($input, true));

$applicantId = isset($input['applicant_id']) ? intval($input['applicant_id']) : 0;
$trainerId = isset($input['trainer_id']) ? intval($input['trainer_id']) : 0;
// A single figure, as actually discussed and agreed in the interview --
// ₱3,900–₱4,500 is only a suggested guide shown in the UI, never enforced.
$salary = isset($input['salary']) ? floatval($input['salary']) : 0;
$scheduleStartRaw = isset($input['schedule_start']) ? trim($input['schedule_start']) : '10:00';
$restDays = isset($input['rest_days']) ? trim($input['rest_days']) : 'saturday,sunday';

if ($applicantId <= 0) {
    Response::error('Invalid applicant ID.', 400);
}

if ($trainerId <= 0) {
    Response::error('Please select a valid trainer.', 400);
}

if ($salary <= 0) {
    Response::error('Please enter a valid salary.', 400);
}

// Trainee shifts are always exactly 5 hours -- the end time is always
// computed server-side from the start, never trusted from the client, so a
// tampered request can never create a shift of a different length.
if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $scheduleStartRaw)) {
    Response::error('Invalid working hours start time.', 400);
}
$scheduleStart = $scheduleStartRaw . ':00';
$scheduleEnd = date('H:i:s', strtotime($scheduleStartRaw) + 5 * 3600);

$restDaysArray = array_filter(array_map('trim', explode(',', $restDays)));
$validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
foreach ($restDaysArray as $d) {
    if (!in_array($d, $validDays, true)) {
        Response::error('Invalid rest day value.', 400);
    }
}
if (count($restDaysArray) > 2) {
    Response::error('A trainee cannot have more than 2 rest days per week.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM applicants WHERE id = ?");
    $stmt->execute([$applicantId]);
    $applicant = $stmt->fetch();

    if (!$applicant) {
        Response::error('Applicant not found.', 404);
    }

    // Trainee Contract now follows the Initial Interview directly.
    if ($applicant['status'] !== 'initial_passed') {
        Response::error('Applicant must have passed the initial interview (status: initial_passed). Current status: ' . $applicant['status'], 400);
    }

    $dbTargetRole = mapDisplayRoleToDbRole($applicant['target_role']);

    $stmt = $db->prepare("SELECT user_id, role, can_train, first_name, last_name, email FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$trainerId]);
    $trainer = $stmt->fetch();

    if (!$trainer) {
        Response::error('Trainer not found or inactive.', 400);
    }

    if ($trainer['can_train'] != 1) {
        Response::error('This trainer is currently locked. Please select another trainer.', 400);
    }

    if (strtolower($trainer['role']) !== strtolower($dbTargetRole)) {
        Response::error('Trainer must have the same role as the trainee\'s target role.', 400);
    }

    $stmt = $db->prepare("UPDATE users SET can_train = 0, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$trainerId]);

    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$applicant['email']]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        $newUserId = $existingUser['user_id'];
        $defaultPassword = null;
    } else {
        $employeeNumber = generateEmployeeNumber('trainee');
        $defaultPassword = generateDefaultPassword($applicant['first_name'], $applicant['last_name']);
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (employee_number, first_name, last_name, middle_name, email, password, role, is_first_login, can_train) 
            VALUES (?, ?, ?, ?, ?, ?, 'trainee', 1, 0)
        ");
        $stmt->execute([
            $employeeNumber,
            $applicant['first_name'],
            $applicant['last_name'],
            $applicant['middle_name'] ?? '',
            $applicant['email'],
            $hashedPassword
        ]);
        $newUserId = $db->lastInsertId();
    }

    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+3 months'));

    $stmt = $db->prepare("
        INSERT INTO trainees (applicant_id, user_id, trainer_id, target_role, start_date, end_date, schedule_start, schedule_end, status, trainee_salary)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
    ");
    $stmt->execute([
        $applicantId,
        $newUserId,
        $trainerId,
        $applicant['target_role'],
        $startDate,
        $endDate,
        $scheduleStart,
        $scheduleEnd,
        $salary
    ]);
    $traineeId = $db->lastInsertId();

    $db->prepare("INSERT INTO trainer_assignments (trainee_id, trainer_id, assigned_by) VALUES (?, ?, ?)")
        ->execute([$traineeId, $trainerId, Auth::userId()]);

    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    if (empty($restDaysArray)) $restDaysArray = ['saturday', 'sunday'];

    $stmt = $db->prepare("
        INSERT INTO schedules (user_id, day_of_week, time_in, time_out, is_rest_day) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            time_in = VALUES(time_in),
            time_out = VALUES(time_out),
            is_rest_day = VALUES(is_rest_day)
    ");
    
    foreach ($days as $day) {
        $isRestDay = in_array(trim($day), $restDaysArray) ? 1 : 0;
        $timeIn = $isRestDay ? '00:00:00' : $scheduleStart;
        $timeOut = $isRestDay ? '00:00:00' : $scheduleEnd;
        $stmt->execute([$newUserId, $day, $timeIn, $timeOut, $isRestDay]);
    }

    $stmt = $db->prepare("UPDATE applicants SET status = 'screening', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$applicantId]);

    $notificationMessage = "Trainee account created for {$applicant['first_name']} {$applicant['last_name']}. Trainer {$trainer['first_name']} {$trainer['last_name']} is locked.";
    createNotification(Auth::userId(), 'trainee_created', $notificationMessage);
    logRecruitmentEvent('applicant', $applicantId, 'trainee_contract_assigned', [
        'previous_status' => 'initial_passed',
        'new_status' => 'screening',
        'notes' => "Trainer #{$trainerId} assigned"
    ]);

    $trainerName = trim(($trainer['first_name'] ?? '') . ' ' . ($trainer['last_name'] ?? ''));
    if (empty($trainerName)) {
        $trainerName = 'Trainer #' . $trainerId;
    }

    // Notify both the new trainee and the trainer -- in-app and by email.
    $restDaysLabel = ucwords(str_replace(',', ', ', $restDays));
    createNotification($newUserId, 'trainee_contract_ready', "Your Trainee Contract is ready. Trainer: {$trainerName}. Salary: ₱{$salary}. Hours: {$scheduleStart}–{$scheduleEnd}. Rest days: {$restDaysLabel}.");
    createNotification($trainerId, 'trainee_assigned', "You have been assigned as trainer for {$applicant['first_name']} {$applicant['last_name']} ({$applicant['target_role']}).");

    $mailer = new Mailer();
    $mailer->sendTraineeContractNotice($applicant, [
        'trainer_name' => $trainerName,
        'salary' => $salary,
        'schedule_start' => date('g:i A', strtotime($scheduleStart)),
        'schedule_end' => date('g:i A', strtotime($scheduleEnd)),
        'rest_days' => $restDaysLabel,
        'start_date' => formatDate($startDate),
        'end_date' => formatDate($endDate),
    ]);
    $mailer->sendTrainerAssignmentNotice($trainer, trim($applicant['first_name'] . ' ' . $applicant['last_name']), $applicant['target_role']);

    Response::success([
        'trainee_id' => $traineeId,
        'user_id' => $newUserId,
        'employee_number' => $employeeNumber ?? '',
        'default_password' => $defaultPassword,
        'salary' => $salary,
        'schedule_start' => $scheduleStart,
        'schedule_end' => $scheduleEnd,
        'rest_days' => $restDays,
        'trainer_id' => $trainerId,
        'trainer_name' => $trainerName,
        'trainer_locked' => true,
        'trainer_role' => $trainer['role']
    ], 'Trainee account created successfully with schedule.');

} catch (Exception $e) {
    error_log('❌ create_trainee_with_trainer.php error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    Response::error('Error: ' . $e->getMessage());
}