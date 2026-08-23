<?php
// app/handlers/hr/approve_week.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../models/PayrollCycle.php';
require_once __DIR__ . '/../../models/PayrollEntry.php';
require_once __DIR__ . '/../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\PayrollCycle;
use App\Models\PayrollEntry;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

// Only HR Head, SuperAdmin, and Head HR Trainee can approve
$targetRole = Auth::getNormalizedTargetRole();
$isHeadHrTrainee = Auth::isTrainee() && $targetRole === 'hr_head';

if (!Auth::canApprove() && !Auth::isHRHead() && !$isHeadHrTrainee) {
    Response::forbidden('Only HR Head can approve.');
}

$input = json_decode(file_get_contents('php://input'), true);
$monthYear = $input['month_year'] ?? '';
$weekNumber = intval($input['week_number'] ?? 0);
$action = $input['action'] ?? 'approve';

if (empty($monthYear) || $weekNumber < 1 || $weekNumber > 4) {
    Response::error('Invalid parameters.', 400);
}

try {
    $db = Database::getInstance()->getConnection();
    list($year, $month) = explode('-', $monthYear);
    $year = intval($year);
    $month = intval($month);

    if ($action === 'approve') {
        $stmt = $db->prepare("
            UPDATE attendance_weekly_summaries
            SET status = 'locked', approved_by = ?, approved_at = NOW()
            WHERE month_year = ? AND week_number = ?
        ");
        $stmt->execute([Auth::userId(), $monthYear, $weekNumber]);

        $stmt = $db->prepare("
            UPDATE attendance_monthly_summaries
            SET overall_status = 'in_progress'
            WHERE month_year = ?
        ");
        $stmt->execute([$monthYear]);

        $half = ($weekNumber <= 2) ? 1 : 2;
        $weekRange = ($half == 1) ? [1,2] : [3,4];
        $stmt = $db->prepare("
            SELECT COUNT(*) as incomplete
            FROM attendance_weekly_summaries
            WHERE month_year = ? AND week_number IN (?, ?) AND status != 'locked'
        ");
        $stmt->execute([$monthYear, $weekRange[0], $weekRange[1]]);
        $incomplete = $stmt->fetchColumn();

        if ($incomplete == 0) {
            $startDate = date('Y-m-d', strtotime("$year-$month-01"));
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            if ($half == 1) {
                $endDay = ($daysInMonth == 31) ? 16 : 15;
                $paymentDay = $endDay;
            } else {
                $endDay = $daysInMonth;
                $paymentDay = $daysInMonth;
            }
            $endDate = date('Y-m-d', strtotime("$year-$month-$endDay"));
            $paymentDate = date('Y-m-d', strtotime("$year-$month-$paymentDay"));
            $cycleName = date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));

            $stmt = $db->prepare("SELECT id FROM payroll_cycles WHERE start_date = ? AND end_date = ? AND status NOT IN ('cancelled')");
            $stmt->execute([$startDate, $endDate]);
            if (!$stmt->fetch()) {
                $cycleModel = new PayrollCycle();
                $entryModel = new PayrollEntry();
                $cycleId = $cycleModel->create([
                    'cycle_name' => $cycleName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'payment_date' => $paymentDate,
                    'created_by' => Auth::userId(),
                    'notes' => 'Auto-generated from attendance approval'
                ]);

                $stmt = $db->prepare("
                    SELECT u.user_id, c.salary
                    FROM users u
                    JOIN contracts c ON u.user_id = c.user_id
                    WHERE u.is_active = 1 AND u.role != 'trainee' AND c.status = 'accepted'
                ");
                $stmt->execute();
                $employees = $stmt->fetchAll();

                $totalGross = 0;
                $totalDeductions = 0;
                $totalNet = 0;
                $employeeCount = 0;

                foreach ($employees as $emp) {
                    $result = calculateAndSavePayrollEntry($emp['user_id'], $startDate, $endDate, $cycleId);
                    if ($result) {
                        $totalGross += $result['gross'];
                        $totalDeductions += $result['deductions'];
                        $totalNet += $result['net'];
                        $employeeCount++;
                    }
                }

                $cycleModel->updateTotals($cycleId, $employeeCount, $totalGross, $totalDeductions, $totalNet);
                $cycleModel->addLog($cycleId, 'create', Auth::userId(), 'Auto-generated payroll');
                
                createNotification(
                    Auth::userId(),
                    'payroll_generated',
                    "Payroll cycle for {$cycleName} was auto-generated after week {$weekNumber} approval."
                );
            }
        }

        $message = "Week $weekNumber approved and locked.";
        $status = 'locked';

    } elseif ($action === 'retract') {
        $stmt = $db->prepare("
            UPDATE attendance_weekly_summaries
            SET status = 'draft', approved_by = NULL, approved_at = NULL
            WHERE month_year = ? AND week_number = ?
        ");
        $stmt->execute([$monthYear, $weekNumber]);

        $stmt = $db->prepare("
            UPDATE attendance_monthly_summaries
            SET overall_status = 'in_progress'
            WHERE month_year = ?
        ");
        $stmt->execute([$monthYear]);

        $message = "Week $weekNumber returned to draft.";
        $status = 'draft';
    } else {
        Response::error('Invalid action', 400);
    }

    Response::success([
        'month_year' => $monthYear,
        'week_number' => $weekNumber,
        'status' => $status
    ], $message);

} catch (Exception $e) {
    error_log('approve_week.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}