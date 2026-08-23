<?php
// app/handlers/hr/export_payroll.php

require_once __DIR__ . '/../../models/PayrollEntry.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollEntry;

if (!Auth::check() || !Auth::isHR()) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

if (!Auth::canAccessModule('hr_head')) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$cycleId = isset($_GET['cycle_id']) ? intval($_GET['cycle_id']) : 0;

if ($cycleId <= 0) {
    http_response_code(400);
    echo "Invalid cycle ID";
    exit;
}

try {
    $entryModel = new PayrollEntry();
    $entries = $entryModel->getEntriesForCycle($cycleId);

    if (empty($entries)) {
        echo "No data to export.";
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="payroll_cycle_' . $cycleId . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Employee #', 'Name', 'Role', 
        'Working Days', 'Attended Days', 'Absent Days', 
        'Overtime Hours', 'Holiday Work Hours', 'Late Minutes',
        'Monthly Salary', 'Daily Rate', 'Regular Pay', 
        'Overtime Pay', 'Holiday Pay', 
        'Late Deduction', 'Absent Deduction', 'Unpaid Leave Deduction', 'Other Deductions',
        'Gross Pay', 'Total Deductions', 'Net Pay'
    ]);

    foreach ($entries as $row) {
        fputcsv($output, [
            $row['employee_number'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['role'],
            $row['total_working_days'],
            $row['attended_days'],
            $row['absent_days'],
            $row['total_overtime_hours'],
            $row['total_holiday_work_hours'],
            $row['late_minutes'],
            $row['monthly_salary'],
            $row['daily_rate'],
            $row['regular_pay'],
            $row['overtime_pay'],
            $row['holiday_pay'],
            $row['late_deduction'],
            $row['absent_deduction'],
            $row['unpaid_leave_deduction'],
            $row['other_deductions'],
            $row['gross_pay'],
            $row['total_deductions'],
            $row['net_pay']
        ]);
    }
    fclose($output);
    exit;

} catch (Exception $e) {
    error_log('export_payroll.php error: ' . $e->getMessage());
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    exit;
}