<?php
namespace App\Models;

use App\Core\Database;
use App\Core\CutoffPeriod;

class AttendanceWeeklySummary
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generateForUser($userId, $weekStart, $weekEnd, $weekNumber, $monthYear)
    {
        // Per-day categorization, mirroring the same schedule_overrides > schedules
        // precedence used by app/handlers/hr/get_week_attendance.php, so a day is
        // counted in exactly one bucket -- never both "present" (via a combined
        // "attended" total) and its own late/leave/holiday column, and a day
        // manually marked status='rest_day' on the Attendance page is recognized
        // here too instead of only schedule-driven rest days.
        $attStmt = $this->db->prepare("
            SELECT date, status, overtime_hours
            FROM attendance
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $attStmt->execute([$userId, $weekStart, $weekEnd]);
        $attendanceByDate = [];
        while ($row = $attStmt->fetch()) {
            $attendanceByDate[$row['date']] = $row;
        }

        $scheduleStmt = $this->db->prepare("
            SELECT day_of_week, is_rest_day
            FROM schedules
            WHERE user_id = ?
        ");
        $scheduleStmt->execute([$userId]);
        $scheduleByDay = [];
        while ($row = $scheduleStmt->fetch()) {
            $scheduleByDay[$row['day_of_week']] = $row;
        }

        $days = [];
        $cursor = new \DateTime($weekStart);
        $end = new \DateTime($weekEnd);
        while ($cursor <= $end) {
            $days[] = $cursor->format('Y-m-d');
            $cursor->modify('+1 day');
        }

        $periodKeys = array_unique(array_map(function ($d) {
            return CutoffPeriod::getKeyForDate($d);
        }, $days));
        $overridesByPeriodDay = [];
        if (!empty($periodKeys)) {
            $placeholders = implode(',', array_fill(0, count($periodKeys), '?'));
            $overrideStmt = $this->db->prepare("
                SELECT period_key, day_of_week, is_rest_day
                FROM schedule_overrides
                WHERE user_id = ? AND period_key IN ($placeholders)
            ");
            $overrideStmt->execute(array_merge([$userId], $periodKeys));
            while ($row = $overrideStmt->fetch()) {
                $overridesByPeriodDay[$row['period_key']][$row['day_of_week']] = $row;
            }
        }

        $totalDays = 0;
        $presentDays = 0;
        $lateDays = 0;
        $absentDays = 0;
        $leavePaidDays = 0;
        $leaveUnpaidDays = 0;
        $restDays = 0;
        $holidayDays = 0;
        $overtimeHours = 0.0;

        foreach ($days as $date) {
            $totalDays++;
            $record = $attendanceByDate[$date] ?? null;
            $status = $record['status'] ?? null;
            $overtimeHours += $record ? (float)$record['overtime_hours'] : 0.0;

            $dayOfWeek = strtolower(date('l', strtotime($date)));
            $periodKey = CutoffPeriod::getKeyForDate($date);
            $schedule = $overridesByPeriodDay[$periodKey][$dayOfWeek] ?? ($scheduleByDay[$dayOfWeek] ?? null);
            $scheduledRestDay = $schedule ? (bool)$schedule['is_rest_day'] : false;

            if ($status === 'rest_day' || (!$record && $scheduledRestDay)) {
                $restDays++;
            } elseif ($status === 'present' || $status === 'holiday_work') {
                $presentDays++;
            } elseif ($status === 'late') {
                $lateDays++;
            } elseif ($status === 'leave_paid') {
                $leavePaidDays++;
            } elseif ($status === 'leave_unpaid') {
                $leaveUnpaidDays++;
            } elseif ($status === 'holiday_no_work') {
                $holidayDays++;
            } elseif ($status === 'absent' || !$record) {
                $absentDays++;
            }
        }

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
            $presentDays,
            $lateDays,
            $absentDays,
            $leavePaidDays,
            $leaveUnpaidDays,
            $restDays,
            $holidayDays,
            $overtimeHours
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