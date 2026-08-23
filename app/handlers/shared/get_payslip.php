<?php
// app/handlers/shared/get_payslip.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/PayrollEntry.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\PayrollEntry;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : Auth::userId();

// Users can view their own payslip; Department Heads, SuperAdmin can view others
if ($userId != Auth::userId() && !Auth::canApprove() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied');
}

$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
$offset = ($page - 1) * $limit;

try {
    $entryModel = new PayrollEntry();
    
    if (method_exists($entryModel, 'getPayslipsForUser')) {
        $result = $entryModel->getPayslipsForUser($userId, $limit, $offset);
    } else {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT pe.*, 
                   pc.cycle_name, pc.start_date, pc.end_date, pc.payment_date,
                   pc.status as cycle_status,
                   u.first_name, u.last_name, u.employee_number
            FROM payroll_entries pe
            JOIN payroll_cycles pc ON pe.payroll_cycle_id = pc.id
            JOIN users u ON pe.user_id = u.user_id
            WHERE pe.user_id = ?
            ORDER BY pc.start_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        $entries = $stmt->fetchAll();
        
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM payroll_entries WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = $stmt->fetch()['total'];
        $result = ['entries' => $entries, 'total' => $total];
    }
    
    foreach ($result['entries'] as &$entry) {
        $entry['gross_pay'] = (float)($entry['gross_pay'] ?? 0);
        $entry['total_deductions'] = (float)($entry['total_deductions'] ?? 0);
        $entry['net_pay'] = (float)($entry['net_pay'] ?? 0);
        $entry['regular_pay'] = (float)($entry['regular_pay'] ?? 0);
        $entry['overtime_pay'] = (float)($entry['overtime_pay'] ?? 0);
        $entry['holiday_pay'] = (float)($entry['holiday_pay'] ?? 0);
        $entry['late_deduction'] = (float)($entry['late_deduction'] ?? 0);
        $entry['absent_deduction'] = (float)($entry['absent_deduction'] ?? 0);
        $entry['unpaid_leave_deduction'] = (float)($entry['unpaid_leave_deduction'] ?? 0);
        $entry['other_deductions'] = (float)($entry['other_deductions'] ?? 0);
        $entry['monthly_salary'] = (float)($entry['monthly_salary'] ?? 0);
        $entry['attended_days'] = (int)($entry['attended_days'] ?? 0);
        $entry['absent_days'] = (int)($entry['absent_days'] ?? 0);
        $entry['total_overtime_hours'] = (float)($entry['total_overtime_hours'] ?? 0);
        $entry['total_holiday_work_hours'] = (float)($entry['total_holiday_work_hours'] ?? 0);
        $entry['late_minutes'] = (int)($entry['late_minutes'] ?? 0);
        $entry['total_working_days'] = (int)($entry['total_working_days'] ?? 0);
        $entry['payment_status'] = $entry['payment_status'] ?? 'pending';
        
        $entry['formatted_start'] = !empty($entry['start_date']) ? date('M d, Y', strtotime($entry['start_date'])) : 'N/A';
        $entry['formatted_end'] = !empty($entry['end_date']) ? date('M d, Y', strtotime($entry['end_date'])) : 'N/A';
        $entry['formatted_payment_date'] = !empty($entry['payment_date']) ? date('M d, Y', strtotime($entry['payment_date'])) : 'N/A';
    }

    Response::success([
        'payslips' => $result['entries'],
        'pagination' => [
            'currentPage' => $page,
            'perPage' => $limit,
            'totalRecords' => (int)$result['total'],
            'totalPages' => ceil($result['total'] / $limit)
        ],
        'user_id' => $userId
    ], 'Payslips fetched successfully');

} catch (Exception $e) {
    error_log('get_payslip.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}