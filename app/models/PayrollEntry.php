<?php
namespace App\Models;

use App\Core\Database;

class PayrollEntry
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payroll_entries (
                payroll_cycle_id, user_id,
                total_working_days, attended_days, absent_days,
                total_overtime_hours, total_holiday_work_hours, late_minutes,
                monthly_salary, daily_rate, regular_pay,
                overtime_pay, holiday_pay,
                late_deduction, absent_deduction, unpaid_leave_deduction, other_deductions,
                gross_pay, total_deductions, net_pay,
                notes
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )
        ");
        return $stmt->execute([
            $data['payroll_cycle_id'],
            $data['user_id'],
            $data['total_working_days'] ?? 0,
            $data['attended_days'] ?? 0,
            $data['absent_days'] ?? 0,
            $data['total_overtime_hours'] ?? 0,
            $data['total_holiday_work_hours'] ?? 0,
            $data['late_minutes'] ?? 0,
            $data['monthly_salary'] ?? 0,
            $data['daily_rate'] ?? 0,
            $data['regular_pay'] ?? 0,
            $data['overtime_pay'] ?? 0,
            $data['holiday_pay'] ?? 0,
            $data['late_deduction'] ?? 0,
            $data['absent_deduction'] ?? 0,
            $data['unpaid_leave_deduction'] ?? 0,
            $data['other_deductions'] ?? 0,
            $data['gross_pay'] ?? 0,
            $data['total_deductions'] ?? 0,
            $data['net_pay'] ?? 0,
            $data['notes'] ?? null
        ]);
    }

    public function getEntriesForCycle($cycleId)
    {
        $stmt = $this->db->prepare("
            SELECT pe.*, u.first_name, u.last_name, u.employee_number, u.role
            FROM payroll_entries pe
            JOIN users u ON pe.user_id = u.user_id
            WHERE pe.payroll_cycle_id = ?
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute([$cycleId]);
        return $stmt->fetchAll();
    }

    public function getEntry($cycleId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM payroll_entries WHERE payroll_cycle_id = ? AND user_id = ?
        ");
        $stmt->execute([$cycleId, $userId]);
        return $stmt->fetch();
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'payroll_cycle_id' && $key !== 'user_id') {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $sql = "UPDATE payroll_entries SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteForCycle($cycleId)
    {
        $stmt = $this->db->prepare("DELETE FROM payroll_entries WHERE payroll_cycle_id = ?");
        return $stmt->execute([$cycleId]);
    }

    public function getPayslipsForUser($userId, $limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare("
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
        
        // Get total count for pagination
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM payroll_entries WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = $stmt->fetch()['total'];
        
        return [
            'entries' => $entries,
            'total' => $total
        ];
    }

}