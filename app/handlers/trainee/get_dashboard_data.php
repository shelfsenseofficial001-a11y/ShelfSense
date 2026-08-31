<?php
// app/handlers/trainee/get_dashboard_data.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isTrainee() && !Auth::isOwner()) {
    Response::forbidden('Access denied');
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = Auth::userId();

    // Get trainee data
    $stmt = $db->prepare("
        SELECT 
            t.*,
            a.first_name as applicant_first_name,
            a.last_name as applicant_last_name,
            a.email as applicant_email,
            u.employee_number,
            u.first_name,
            u.last_name,
            u.email,
            tr.first_name as trainer_first_name,
            tr.last_name as trainer_last_name,
            tr.employee_number as trainer_employee_number,
            tr.can_train as trainer_can_train
        FROM trainees t
        JOIN applicants a ON t.applicant_id = a.id
        JOIN users u ON t.user_id = u.user_id
        LEFT JOIN users tr ON t.trainer_id = tr.user_id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $trainee = $stmt->fetch();

    if (!$trainee) {
        Response::error('Trainee record not found', 404);
    }

    // Calculate days remaining
    $now = new DateTime();
    $end = new DateTime($trainee['end_date']);
    $diff = $now->diff($end);
    $daysRemaining = $diff->days;
    $isCompleted = $now > $end;

    // Get report status
    $reports = [
        1 => !empty($trainee['report_1']),
        2 => !empty($trainee['report_2']),
        3 => !empty($trainee['report_3'])
    ];
    $reportsSubmitted = array_sum($reports);
    $reportsTotal = 3;
    $allReportsSubmitted = $reportsSubmitted === $reportsTotal;

    // Get leave balances (trainees might not have them, but we can show zeros)
    $stmt = $db->prepare("
        SELECT 
            sick_leave_balance,
            vacation_leave_balance,
            emergency_leave_balance,
            other_leave_balance
        FROM users
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $balances = $stmt->fetch();

    // Get leave used for current year
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN leave_type = 'sick' AND status = 'approved' THEN 1 ELSE 0 END) as sick_used,
            SUM(CASE WHEN leave_type = 'vacation' AND status = 'approved' THEN 1 ELSE 0 END) as vacation_used,
            SUM(CASE WHEN leave_type = 'emergency' AND status = 'approved' THEN 1 ELSE 0 END) as emergency_used,
            SUM(CASE WHEN leave_type = 'other' AND status = 'approved' THEN 1 ELSE 0 END) as other_used
        FROM leaves
        WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW()) AND status = 'approved'
    ");
    $stmt->execute([$userId]);
    $used = $stmt->fetch();

    // Pending Hired Contract awaiting this trainee's own response, if any.
    $stmt = $db->prepare("
        SELECT c.*, u2.first_name as offered_by_first, u2.last_name as offered_by_last
        FROM contracts c
        LEFT JOIN users u2 ON c.offered_by = u2.user_id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $pendingContract = $stmt->fetch() ?: null;

    // Get recent activity (last 3 notifications)
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();

    // Role → Module mapping
    $roleKey = strtolower($trainee['target_role'] ?? '');
    $moduleMap = [
        'cashier' => [
            'name' => 'Cashier / POS',
            'url' => '?page=pos_checkout',
            'icon' => 'bi-cart-plus',
            'description' => 'Process sales, manage orders, and handle daily transactions.'
        ],
        'hr_staff' => [
            'name' => 'Human Resources',
            'url' => '?page=hr_dashboard',
            'icon' => 'bi-people',
            'description' => 'Manage applicants, interviews, employees, and attendance.'
        ],
        'hr_head' => [
            'name' => 'Human Resources (Head)',
            'url' => '?page=hr_dashboard',
            'icon' => 'bi-people-fill',
            'description' => 'Oversee HR operations, approvals, and payroll management.'
        ],
        'finance_staff' => [
            'name' => 'Finance',
            'url' => '?page=finance_staff_dashboard',
            'icon' => 'bi-cash',
            'description' => 'Manage financial records, payroll, and reports.'
        ],
        'finance_head' => [
            'name' => 'Finance (Head)',
            'url' => '?page=finance_head_dashboard',
            'icon' => 'bi-cash-stack',
            'description' => 'Lead finance operations and strategic planning.'
        ]
    ];

    $module = $moduleMap[$roleKey] ?? [
        'name' => 'Training',
        'url' => '#',
        'icon' => 'bi-mortarboard',
        'description' => 'Your training is in progress.'
    ];

    Response::success([
        'trainee' => [
            'id' => $trainee['id'],
            'applicant_id' => $trainee['applicant_id'],
            'user_id' => $trainee['user_id'],
            'employee_number' => $trainee['employee_number'],
            'first_name' => $trainee['first_name'],
            'last_name' => $trainee['last_name'],
            'email' => $trainee['email'],
            'target_role' => $trainee['target_role'],
            'start_date' => $trainee['start_date'],
            'end_date' => $trainee['end_date'],
            'formatted_start' => date('M d, Y', strtotime($trainee['start_date'])),
            'formatted_end' => date('M d, Y', strtotime($trainee['end_date'])),
            'schedule_start' => date('h:i A', strtotime($trainee['schedule_start'])),
            'schedule_end' => date('h:i A', strtotime($trainee['schedule_end'])),
            'status' => $trainee['status'],
            'status_label' => ucfirst($trainee['status']),
            'days_remaining' => $daysRemaining,
            'is_completed' => $isCompleted,
            'eligible_for_contract' => (bool)$trainee['eligible_for_contract'],
            'reports_status' => $trainee['reports_status'],
            'reports' => $reports,
            'reports_submitted' => $reportsSubmitted,
            'reports_total' => $reportsTotal,
            'all_reports_submitted' => $allReportsSubmitted,
            'trainer' => [
                'id' => $trainee['trainer_id'],
                'first_name' => $trainee['trainer_first_name'],
                'last_name' => $trainee['trainer_last_name'],
                'employee_number' => $trainee['trainer_employee_number'],
                'can_train' => (bool)$trainee['trainer_can_train']
            ]
        ],
        'module' => $module,
        'pending_contract' => $pendingContract,
        'leave_balances' => [
            'sick' => [
                'entitled' => (float)($balances['sick_leave_balance'] ?? 0),
                'used' => (int)($used['sick_used'] ?? 0),
                'remaining' => (float)($balances['sick_leave_balance'] ?? 0) - (int)($used['sick_used'] ?? 0)
            ],
            'vacation' => [
                'entitled' => (float)($balances['vacation_leave_balance'] ?? 0),
                'used' => (int)($used['vacation_used'] ?? 0),
                'remaining' => (float)($balances['vacation_leave_balance'] ?? 0) - (int)($used['vacation_used'] ?? 0)
            ],
            'emergency' => [
                'entitled' => (float)($balances['emergency_leave_balance'] ?? 0),
                'used' => (int)($used['emergency_used'] ?? 0),
                'remaining' => (float)($balances['emergency_leave_balance'] ?? 0) - (int)($used['emergency_used'] ?? 0)
            ],
            'other' => [
                'entitled' => (float)($balances['other_leave_balance'] ?? 0),
                'used' => (int)($used['other_used'] ?? 0),
                'remaining' => (float)($balances['other_leave_balance'] ?? 0) - (int)($used['other_used'] ?? 0)
            ]
        ],
        'notifications' => $notifications
    ], 'Trainee dashboard data fetched');

} catch (Exception $e) {
    error_log('get_dashboard_data.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}