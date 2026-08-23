<?php
// app/handlers/hr/create_payroll_cycle.php

require_once __DIR__ . '/../../models/PayrollCycle.php';
require_once __DIR__ . '/../../models/PayrollEntry.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PayrollCycle;
use App\Models\PayrollEntry;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::canAccessModule('hr_head')) {
    Response::forbidden('Access denied. HR role required.');
}

$input = json_decode(file_get_contents('php://input'), true);
$startDate = $input['start_date'] ?? '';
$endDate = $input['end_date'] ?? '';
$paymentDate = $input['payment_date'] ?? '';
$notes = $input['notes'] ?? '';

if (empty($startDate) || empty($endDate) || empty($paymentDate)) {
    Response::error('Start date, end date, and payment date are required.', 400);
}
if ($startDate > $endDate || $paymentDate < $endDate) {
    Response::error('Invalid dates.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    $cycleModel = new PayrollCycle();
    $entryModel = new PayrollEntry();
    $currentUserId = Auth::userId();

    $stmt = $db->prepare("SELECT id FROM payroll_cycles WHERE start_date = ? AND end_date = ? AND status NOT IN ('cancelled')");
    $stmt->execute([$startDate, $endDate]);
    if ($stmt->fetch()) {
        Response::error('A payroll cycle already exists for this period.', 400);
    }

    $cycleName = date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));
    $cycleId = $cycleModel->create([
        'cycle_name' => $cycleName,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'payment_date' => $paymentDate,
        'created_by' => $currentUserId,
        'notes' => $notes
    ]);

    $stmt = $db->prepare("
        SELECT u.user_id
        FROM users u
        JOIN contracts c ON u.user_id = c.user_id
        WHERE u.is_active = 1 AND u.role != 'trainee' AND c.status = 'accepted'
    ");
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($employees)) {
        $cycleModel->updateTotals($cycleId, 0, 0, 0, 0);
        $cycleModel->addLog($cycleId, 'create', $currentUserId, 'Cycle created (no employees)');
        Response::success(['cycle_id' => $cycleId], 'Payroll cycle created with no employees.');
    }

    $totalGross = 0;
    $totalDeductions = 0;
    $totalNet = 0;
    $employeeCount = 0;

    foreach ($employees as $userId) {
        $result = calculateAndSavePayrollEntry($userId, $startDate, $endDate, $cycleId);
        if ($result) {
            $totalGross += $result['gross'];
            $totalDeductions += $result['deductions'];
            $totalNet += $result['net'];
            $employeeCount++;
        }
    }

    $cycleModel->updateTotals($cycleId, $employeeCount, $totalGross, $totalDeductions, $totalNet);
    $cycleModel->addLog($cycleId, 'create', $currentUserId, 'Payroll cycle created');

    Response::success([
        'cycle_id' => $cycleId,
        'employee_count' => $employeeCount,
        'total_gross' => $totalGross,
        'total_net' => $totalNet
    ], 'Payroll cycle created successfully.');

} catch (Exception $e) {
    error_log('create_payroll_cycle.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}