<?php
namespace App\Models;

use App\Core\Database;

class Schedule
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserSchedule($userId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM schedules 
            WHERE user_id = ? 
            ORDER BY FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getScheduleByDay($userId, $dayOfWeek)
    {
        $stmt = $this->db->prepare("SELECT * FROM schedules WHERE user_id = ? AND day_of_week = ?");
        $stmt->execute([$userId, $dayOfWeek]);
        return $stmt->fetch();
    }

    public function saveSchedule($userId, $dayOfWeek, $timeIn, $timeOut, $isRestDay = 0)
    {
        $stmt = $this->db->prepare("
            INSERT INTO schedules (user_id, day_of_week, time_in, time_out, is_rest_day) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                time_in = VALUES(time_in), 
                time_out = VALUES(time_out), 
                is_rest_day = VALUES(is_rest_day),
                updated_at = NOW()
        ");
        return $stmt->execute([$userId, $dayOfWeek, $timeIn, $timeOut, $isRestDay]);
    }

    public function deleteSchedule($userId, $dayOfWeek)
    {
        $stmt = $this->db->prepare("DELETE FROM schedules WHERE user_id = ? AND day_of_week = ?");
        return $stmt->execute([$userId, $dayOfWeek]);
    }

    public function getEmployeesWithSchedules()
    {
        $stmt = $this->db->query("
            SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.employee_number, u.role
            FROM users u
            INNER JOIN schedules s ON u.user_id = s.user_id
            WHERE u.is_active = 1 AND u.role != 'trainee'
            ORDER BY u.first_name
        ");
        return $stmt->fetchAll();
    }

    /**
     * Front Department staff for Store Manager's scheduling scope: hired
     * cashiers (role=employee) plus Employee-track trainees. Mirrors the
     * same role/target_role rule already used at the POS cashier picker.
     */
    public function getFrontDepartmentEmployees()
    {
        $stmt = $this->db->query("
            SELECT u.user_id, u.first_name, u.last_name, u.employee_number, u.role
            FROM users u
            WHERE u.is_active = 1 AND u.role = 'employee'
            UNION
            SELECT u.user_id, u.first_name, u.last_name, u.employee_number, u.role
            FROM users u
            JOIN trainees t ON t.user_id = u.user_id
            WHERE u.is_active = 1 AND u.role = 'trainee'
              AND t.status = 'active' AND t.target_role = 'Employee'
            ORDER BY first_name
        ");
        return $stmt->fetchAll();
    }

    /**
     * True if $userId is in Store Manager's Front Department scope -- used
     * to server-side-enforce the scoping restriction on every write, not
     * just hide other employees in the UI.
     */
    public function isFrontDepartmentUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM users u
            LEFT JOIN trainees t ON t.user_id = u.user_id AND t.status = 'active'
            WHERE u.user_id = ? AND u.is_active = 1
              AND (u.role = 'employee' OR (u.role = 'trainee' AND t.target_role = 'Employee'))
        ");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetch();
    }

    // ============================================
    // PER-CUTOFF OVERRIDES
    // The standing schedule above is the recurring baseline (contract-
    // synced). These rows are actual deviations for one specific cutoff
    // period only -- most users/periods have none. Effective schedule for
    // a (user, period, day) is the override if present, else baseline.
    // ============================================

    public function getOverridesForUserPeriod($userId, $periodKey)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM schedule_overrides WHERE user_id = ? AND period_key = ?
        ");
        $stmt->execute([$userId, $periodKey]);
        $rows = $stmt->fetchAll();
        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row['day_of_week']] = $row;
        }
        return $byDay;
    }

    /**
     * The baseline schedule with any per-period overrides merged in --
     * what actually applies for this user on this cutoff. Each returned
     * day is tagged is_override so the UI can show which days deviate.
     */
    public function getEffectiveSchedule($userId, $periodKey)
    {
        $baseline = $this->getUserSchedule($userId);
        $overrides = $this->getOverridesForUserPeriod($userId, $periodKey);

        $result = [];
        foreach ($baseline as $row) {
            $day = $row['day_of_week'];
            if (isset($overrides[$day])) {
                $o = $overrides[$day];
                $result[] = [
                    'day_of_week' => $day,
                    'time_in' => $o['time_in'],
                    'time_out' => $o['time_out'],
                    'is_rest_day' => $o['is_rest_day'],
                    'is_override' => true,
                    'reason' => $o['reason'],
                    'swap_with_day' => $o['swap_with_day'],
                ];
                unset($overrides[$day]);
            } else {
                $result[] = [
                    'day_of_week' => $day,
                    'time_in' => $row['time_in'],
                    'time_out' => $row['time_out'],
                    'is_rest_day' => $row['is_rest_day'],
                    'is_override' => false,
                    'reason' => null,
                    'swap_with_day' => null,
                ];
            }
        }

        // An override for a day with no baseline row at all (baseline
        // hasn't been set up yet) still applies -- surface it too.
        foreach ($overrides as $day => $o) {
            $result[] = [
                'day_of_week' => $day,
                'time_in' => $o['time_in'],
                'time_out' => $o['time_out'],
                'is_rest_day' => $o['is_rest_day'],
                'is_override' => true,
                'reason' => $o['reason'],
                'swap_with_day' => $o['swap_with_day'],
            ];
        }

        return $result;
    }

    private function saveOverrideRow($userId, $periodKey, $dayOfWeek, $timeIn, $timeOut, $isRestDay, $changedBy, $reason, $swapWithDay)
    {
        $stmt = $this->db->prepare("
            INSERT INTO schedule_overrides (user_id, period_key, day_of_week, swap_with_day, time_in, time_out, is_rest_day, changed_by, reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                swap_with_day = VALUES(swap_with_day),
                time_in = VALUES(time_in),
                time_out = VALUES(time_out),
                is_rest_day = VALUES(is_rest_day),
                changed_by = VALUES(changed_by),
                reason = VALUES(reason),
                updated_at = NOW()
        ");
        return $stmt->execute([$userId, $periodKey, $dayOfWeek, $swapWithDay, $timeIn, $timeOut, $isRestDay, $changedBy, $reason]);
    }

    /**
     * The only way a cutoff schedule change is made: pick a day that
     * becomes a rest day and an existing rest day (in this same period)
     * that becomes the work day instead, in one paired action with one
     * reason. The new work day inherits the rest day's former hours --
     * there's no separate time entry, it's a straight swap.
     */
    public function saveRestDaySwap($userId, $periodKey, $restDay, $workDay, $reason, $changedBy)
    {
        if ($restDay === $workDay) {
            throw new \Exception('Choose two different days.');
        }

        $effective = $this->getEffectiveSchedule($userId, $periodKey);
        $byDay = [];
        foreach ($effective as $row) {
            $byDay[$row['day_of_week']] = $row;
        }

        $restDayRow = $byDay[$restDay] ?? null;
        $workDayRow = $byDay[$workDay] ?? null;

        if (!$restDayRow || $restDayRow['is_rest_day']) {
            throw new \Exception(ucfirst($restDay) . ' is already a rest day.');
        }
        if (!$workDayRow || !$workDayRow['is_rest_day']) {
            throw new \Exception(ucfirst($workDay) . ' is not currently a rest day.');
        }

        // The day becoming rest hands its former hours to the day that
        // picks up the work instead.
        $formerTimeIn = $restDayRow['time_in'];
        $formerTimeOut = $restDayRow['time_out'];

        $this->db->beginTransaction();
        try {
            $this->saveOverrideRow($userId, $periodKey, $restDay, null, null, 1, $changedBy, $reason, $workDay);
            $this->saveOverrideRow($userId, $periodKey, $workDay, $formerTimeIn, $formerTimeOut, 0, $changedBy, $reason, $restDay);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Reverts both sides of a swap back to the standing baseline -- looked
     * up from whichever day is passed in, so either half of the pair works.
     */
    public function revertSwap($userId, $periodKey, $dayOfWeek)
    {
        $stmt = $this->db->prepare("
            SELECT day_of_week, swap_with_day FROM schedule_overrides WHERE user_id = ? AND period_key = ? AND day_of_week = ?
        ");
        $stmt->execute([$userId, $periodKey, $dayOfWeek]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        $days = [$row['day_of_week']];
        if (!empty($row['swap_with_day'])) {
            $days[] = $row['swap_with_day'];
        }

        $placeholders = implode(',', array_fill(0, count($days), '?'));
        $stmt = $this->db->prepare("
            DELETE FROM schedule_overrides WHERE user_id = ? AND period_key = ? AND day_of_week IN ($placeholders)
        ");
        return $stmt->execute(array_merge([$userId, $periodKey], $days));
    }

    /**
     * Every change made for a cutoff period, across users -- the "list of
     * changes" view. $frontDepartmentOnly scopes it to Store Manager's
     * Front Department staff instead of everyone.
     */
    public function getChangesForPeriod($periodKey, $frontDepartmentOnly = false)
    {
        $sql = "
            SELECT so.*, u.first_name, u.last_name, u.employee_number, u.role,
                   CONCAT(cb.first_name, ' ', cb.last_name) as changed_by_name
            FROM schedule_overrides so
            JOIN users u ON u.user_id = so.user_id
            LEFT JOIN users cb ON cb.user_id = so.changed_by
            LEFT JOIN trainees t ON t.user_id = u.user_id AND t.status = 'active'
            WHERE so.period_key = ?
        ";
        if ($frontDepartmentOnly) {
            $sql .= " AND (u.role = 'employee' OR (u.role = 'trainee' AND t.target_role = 'Employee'))";
        }
        $sql .= " ORDER BY so.updated_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$periodKey]);
        return $stmt->fetchAll();
    }
}