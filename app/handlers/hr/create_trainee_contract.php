<?php
// app/handlers/hr/create_trainee_contract.php
// HR offers a Trainee Contract to an applicant who passed the Initial
// Interview, BEFORE a trainer is assigned. Creates the trainee's user
// account (if not already created) and a `contracts` row (contract_type =
// 'trainee', status = 'pending'). The applicant/trainee then accepts or
// declines it (respond_to_contract.php) before Assign Trainer becomes
// available (see create_trainee_with_trainer.php, which now requires this
// contract to already be accepted).

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}
if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot create contracts.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$applicantId = isset($input['applicant_id']) ? intval($input['applicant_id']) : 0;
$salaryMin = isset($input['salary_min']) ? floatval($input['salary_min']) : 0;
$salaryMax = isset($input['salary_max']) ? floatval($input['salary_max']) : 0;
$scheduleStartRaw = isset($input['schedule_start']) ? trim($input['schedule_start']) : '10:00';
$restDays = isset($input['rest_days']) ? trim($input['rest_days']) : 'saturday,sunday';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$decisionDeadlineDays = isset($input['decision_deadline_days']) ? intval($input['decision_deadline_days']) : 5;

if ($applicantId <= 0) {
    Response::error('Invalid applicant ID.', 400);
}
if ($salaryMin <= 0 || $salaryMax <= 0) {
    Response::error('Please enter a valid salary range.', 400);
}
if ($salaryMax < $salaryMin) {
    Response::error('Maximum salary cannot be less than minimum salary.', 400);
}
if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $scheduleStartRaw)) {
    Response::error('Invalid working hours start time.', 400);
}
if ($decisionDeadlineDays < 3 || $decisionDeadlineDays > 7) {
    Response::error('Decision deadline must be between 3 and 7 days.', 400);
}
if (strlen($notes) > 500) {
    Response::error('HR notes cannot exceed 500 characters.', 400);
}
$scheduleStart = $scheduleStartRaw . ':00';
$scheduleEnd = date('H:i:s', strtotime($scheduleStartRaw) + 5 * 3600);
$decisionDeadline = date('Y-m-d', strtotime("+{$decisionDeadlineDays} days"));

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
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM applicants WHERE id = ? FOR UPDATE");
    $stmt->execute([$applicantId]);
    $applicant = $stmt->fetch();

    if (!$applicant) {
        $db->rollBack();
        Response::error('Applicant not found.', 404);
    }
    if ($applicant['status'] !== 'initial_passed') {
        $db->rollBack();
        Response::error('Applicant must have passed the Initial Interview. Current status: ' . $applicant['status'], 400);
    }

    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$applicant['email']]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        $userId = $existingUser['user_id'];
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
        $userId = $db->lastInsertId();
    }

    $stmt = $db->prepare("SELECT id FROM contracts WHERE user_id = ? AND contract_type = 'trainee'");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        $db->rollBack();
        Response::error('A Trainee Contract already exists for this applicant.', 400);
    }

    $salaryMid = round(($salaryMin + $salaryMax) / 2, 2);
    $startDate = date('Y-m-d');
    $responseToken = generateContractResponseToken();

    $stmt = $db->prepare("
        INSERT INTO contracts (applicant_id, user_id, contract_type, schedule_start, schedule_end, salary, salary_range_min, salary_range_max, start_date, rest_days, job_details, decision_deadline, response_token, status, offered_by, offered_at)
        VALUES (?, ?, 'trainee', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([
        $applicantId,
        $userId,
        $scheduleStart,
        $scheduleEnd,
        $salaryMid,
        $salaryMin,
        $salaryMax,
        $startDate,
        implode(',', $restDaysArray) ?: 'saturday,sunday',
        $notes !== '' ? $notes : null,
        $decisionDeadline,
        $responseToken,
        Auth::userId()
    ]);
    $contractId = $db->lastInsertId();

    createNotification($userId, 'trainee_contract_offered', "You have a Trainee Contract to review. Salary: ₱" . number_format($salaryMin, 2) . " – ₱" . number_format($salaryMax, 2) . ". Respond by " . date('M d, Y', strtotime($decisionDeadline)) . ".", "?page=trainee_dashboard");

    logRecruitmentEvent('applicant', $applicantId, 'trainee_contract_offered', [
        'previous_status' => 'initial_passed', 'new_status' => 'initial_passed'
    ]);

    try {
        $mailer = new Mailer();
        $mailer->sendTraineeContractOffer($applicant, [
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'schedule_start' => date('g:i A', strtotime($scheduleStart)),
            'schedule_end' => date('g:i A', strtotime($scheduleEnd)),
            'rest_days' => ucwords(str_replace(',', ', ', implode(',', $restDaysArray))),
            'notes' => $notes,
            'decision_deadline' => date('F j, Y', strtotime($decisionDeadline)),
            'response_url' => appUrl('/?page=contract_response&token=' . $responseToken),
        ]);
    } catch (Exception $e) {
        error_log('create_trainee_contract.php: offer email failed: ' . $e->getMessage());
    }

    $db->commit();

    Response::success([
        'contract_id' => $contractId,
        'user_id' => $userId,
        'default_password' => $defaultPassword,
    ], 'Trainee Contract offered. Awaiting the applicant\'s response.');

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('create_trainee_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
