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
}