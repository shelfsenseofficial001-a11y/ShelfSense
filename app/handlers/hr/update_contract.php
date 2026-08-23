<?php
// app/handlers/hr/update_contract.php

require_once __DIR__ . '/../../core/Database.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

// Only full HR can update contracts (not trainees)
if (!Auth::canAccessModule('hr_head') && !Auth::isHR()) {
    Response::forbidden('Access denied. HR role required.');
}

if (Auth::isTrainee()) {
    Response::forbidden('Trainees cannot update contracts.');
}

$input = json_decode(file_get_contents('php://input'), true);
$contractId = isset($input['contract_id']) ? intval($input['contract_id']) : 0;
$action = isset($input['action']) ? trim($input['action']) : '';

if ($contractId <= 0 || empty($action)) {
    Response::error('Missing required fields', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT c.*, a.target_role, u.first_name, u.user_id as employee_user_id 
        FROM contracts c 
        JOIN applicants a ON c.applicant_id = a.id 
        JOIN users u ON c.user_id = u.user_id 
        WHERE c.id = ?
    ");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();

    if (!$contract) {
        Response::notFound('Contract not found');
    }

    if ($contract['status'] !== 'pending') {
        Response::error('This contract has already been processed.', 400);
    }

    switch ($action) {
        case 'accept':
            $targetRole = $contract['target_role'];
            $firstName = $contract['first_name'];
            $userId = $contract['employee_user_id'];

            $roleMap = [
                'Employee' => 'employee',
                'HR Staff' => 'hr_staff',
                'Finance Staff' => 'finance_staff',
                'Head HR' => 'hr_head',
                'Head Finance' => 'finance_head'
            ];
            $dbRole = $roleMap[$targetRole] ?? 'employee';

            $rolePrefixes = [
                'Employee' => 'EM',
                'HR Staff' => 'HS',
                'Finance Staff' => 'FS',
                'Head HR' => 'HH',
                'Head Finance' => 'FH'
            ];
            $prefix = $rolePrefixes[$targetRole] ?? 'USR';

            $unique = false;
            $attempts = 0;
            while (!$unique && $attempts < 10) {
                $number = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                $newEmployeeNumber = $prefix . '-' . $number;
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE employee_number = ?");
                $stmt->execute([$newEmployeeNumber]);
                if ($stmt->fetchColumn() == 0) {
                    $unique = true;
                }
                $attempts++;
            }
            if (!$unique) {
                $newEmployeeNumber = $prefix . '-' . substr(time(), -3);
            }

            $stmt = $db->prepare("UPDATE contracts SET status = 'accepted', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$contractId]);

            $stmt = $db->prepare("UPDATE applicants SET status = 'hired', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$contract['applicant_id']]);

            $stmt = $db->prepare("
                UPDATE users 
                SET role = ?, 
                    employee_number = ?, 
                    can_train = 1, 
                    is_first_login = 1, 
                    hired_date = NOW(),
                    updated_at = NOW() 
                WHERE user_id = ?
            ");
            $stmt->execute([$dbRole, $newEmployeeNumber, $userId]);

            $shift = $contract['shift'];
            $restDaysStr = $contract['rest_days'] ?? '';
            $restDaysArray = !empty($restDaysStr) ? explode(',', $restDaysStr) : [];

            $shiftHours = [
                'opening' => ['08:00:00', '17:00:00'],
                'closing' => ['14:00:00', '22:00:00'],
                'midshift' => ['10:00:00', '18:00:00']
            ];
            $hours = $shiftHours[$shift] ?? ['08:00:00', '17:00:00'];

            $stmt = $db->prepare("DELETE FROM schedules WHERE user_id = ?");
            $stmt->execute([$userId]);

            $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
            foreach ($days as $day) {
                $isRestDay = in_array($day, $restDaysArray) ? 1 : 0;
                $timeIn = $isRestDay ? '00:00:00' : $hours[0];
                $timeOut = $isRestDay ? '00:00:00' : $hours[1];
                $stmt = $db->prepare("
                    INSERT INTO schedules (user_id, day_of_week, time_in, time_out, is_rest_day) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $day, $timeIn, $timeOut, $isRestDay]);
            }

            $message = 'Contract accepted. Employee hired and schedule created!';
            break;

        case 'decline':
            $stmt = $db->prepare("UPDATE contracts SET status = 'declined', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$contractId]);

            $stmt = $db->prepare("UPDATE applicants SET status = 'contract_declined', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$contract['applicant_id']]);

            $message = 'Contract declined.';
            break;

        case 'update_details':
            $shift = isset($input['shift']) ? trim($input['shift']) : '';
            $salary = isset($input['salary']) ? floatval($input['salary']) : 0;
            $startDate = isset($input['start_date']) ? trim($input['start_date']) : '';
            $jobDetails = isset($input['job_details']) ? trim($input['job_details']) : '';
            $decisionDeadline = isset($input['decision_deadline']) ? trim($input['decision_deadline']) : '';
            $restDays = isset($input['rest_days']) ? trim($input['rest_days']) : '';

            $today = date('Y-m-d');
            if ($startDate < $today) {
                Response::error('Start date cannot be in the past.', 400);
            }
            if ($decisionDeadline < $today) {
                Response::error('Decision deadline cannot be in the past.', 400);
            }
            if ($decisionDeadline < $startDate) {
                Response::error('Decision deadline must be after the start date.', 400);
            }

            if (!empty($restDays)) {
                $daysArray = explode(',', $restDays);
                if (count($daysArray) > 2) {
                    Response::error('You can only select up to 2 rest days.', 400);
                }
                $validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                foreach ($daysArray as $day) {
                    if (!in_array(trim($day), $validDays)) {
                        Response::error('Invalid day selected.', 400);
                    }
                }
            } else {
                $restDays = '';
            }

            if (empty($shift) || $salary <= 0 || empty($startDate) || empty($decisionDeadline)) {
                Response::error('All required fields must be filled.', 400);
            }

            if (strlen($jobDetails) > 250) {
                Response::error('Job details cannot exceed 250 characters.', 400);
            }

            $stmt = $db->prepare("
                UPDATE contracts 
                SET shift = ?, salary = ?, start_date = ?, 
                    job_details = ?, decision_deadline = ?, rest_days = ?,
                    updated_at = NOW() 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$shift, $salary, $startDate, $jobDetails, $decisionDeadline, $restDays, $contractId]);

            if ($stmt->rowCount() === 0) {
                Response::error('Contract not found or already processed.', 404);
            }

            $message = 'Contract details updated successfully.';
            break;

        default:
            Response::error('Invalid action');
    }

    $notificationMessage = "Contract {$action}ed for employee ID: " . $contract['employee_user_id'];
    createNotification(Auth::userId(), 'contract_' . $action, $notificationMessage);

    Response::success([
        'contract_id' => $contractId,
        'action' => $action
    ], $message);

} catch (Exception $e) {
    error_log('update_contract.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}