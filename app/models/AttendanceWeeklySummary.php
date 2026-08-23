<?php
namespace App\Models;

use App\Core\Database;

class AttendanceWeeklySummary
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generateForUser($userId, $weekStart, $weekEnd, $weekNumber, $monthYear)
    {
        // Get attendance stats for this user for this week
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status IN ('present','late','leave_paid','holiday_work') THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN status IN ('absent','leave_unpaid') THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'leave_paid' THEN 1 ELSE 0 END) as leave_paid,
                SUM(CASE WHEN status = 'leave_unpaid' THEN 1 ELSE 0 END) as leave_unpaid,
                SUM(CASE WHEN status = 'holiday_no_work' THEN 1 ELSE 0 END) as holiday,
                SUM(overtime_hours) as overtime
            FROM attendance
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $weekStart, $weekEnd]);
        $data = $stmt->fetch();

        // Get rest days from schedule
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as rest_days
            FROM schedules
            WHERE user_id = ? AND is_rest_day = 1
              AND day_of_week IN (
                  SELECT LOWER(DAYNAME(date)) FROM (
                      SELECT DATE_ADD(?, INTERVAL n DAY) as date
                      FROM (SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) nums
                      WHERE DATE_ADD(?, INTERVAL n DAY) <= ?
                  ) dates
              )
        ");
        $stmt->execute([$userId, $weekStart, $weekStart, $weekEnd]);
        $restDays = $stmt->fetchColumn();

        $totalDays = ($data['total_days'] ?? 0) + $restDays;

        // Insert or update — default status is now 'draft'
        $stmt = $this->db->prepare("
            INSERT INTO attendance_weekly_summaries (
                user_id, week_start_date, week_end_date, week_number, month_year,
                total_days, present_days, late_days, absent_days,
                leave_paid_days, leave_unpaid_days, rest_days, holiday_days,
                total_overtime_hours, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ON DUPLICATE KEY UPDATE
                total_days = VALUES(total_days),
                present_days = VALUES(present_days),
                late_days = VALUES(late_days),
                absent_days = VALUES(absent_days),
                leave_paid_days = VALUES(leave_paid_days),
                leave_unpaid_days = VALUES(leave_unpaid_days),
                rest_days = VALUES(rest_days),
                holiday_days = VALUES(holiday_days),
                total_overtime_hours = VALUES(total_overtime_hours),
                status = 'draft',
                updated_at = NOW()
        ");
        return $stmt->execute([
            $userId,
            $weekStart,
            $weekEnd,
            $weekNumber,
            $monthYear,
            $totalDays,
            $data['attended'] ?? 0,
            $data['late'] ?? 0,
            $data['absent'] ?? 0,
            $data['leave_paid'] ?? 0,
            $data['leave_unpaid'] ?? 0,
            $restDays,
            $data['holiday'] ?? 0,
            $data['overtime'] ?? 0
        ]);
    }

    public function updateStatus($weekStart, $weekNumber, $monthYear, $status, $actorId = null)
    {
        $sql = "UPDATE attendance_weekly_summaries SET status = ?";
        $params = [$status];
        if ($status === 'sent' && $actorId) {
            $sql .= ", sent_by = ?, sent_at = NOW()";
            $params[] = $actorId;
        } elseif ($status === 'locked' && $actorId) {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $actorId;
        }
        $sql .= " WHERE month_year = ? AND week_number = ?";
        $params[] = $monthYear;
        $params[] = $weekNumber;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getWeekSummaries($monthYear, $weekNumber)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM attendance_weekly_summaries
            WHERE month_year = ? AND week_number = ?
        ");
        $stmt->execute([$monthYear, $weekNumber]);
        return $stmt->fetchAll();
    }
}