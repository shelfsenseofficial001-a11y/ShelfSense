<?php
namespace App\Models;

use App\Core\Database;

class Attendance
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getWeekAttendance($weekStart, $weekEnd, $department = null)
    {
        $sql = "SELECT 
                    u.user_id, u.first_name, u.last_name, u.employee_number, u.role,
                    a.date, a.time_in, a.time_out, a.overtime_hours, a.status, a.notes,
                    s.time_in as scheduled_in, s.time_out as scheduled_out, s.is_rest_day
                FROM users u
                LEFT JOIN attendance a ON u.user_id = a.user_id AND a.date BETWEEN ? AND ?
                LEFT JOIN schedules s ON u.user_id = s.user_id AND s.day_of_week = LOWER(DAYNAME(?))
                WHERE u.is_active = 1 AND u.role != 'trainee'";
        if ($department && $department !== 'all') {
            $sql .= " AND u.role = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$weekStart, $weekEnd, $weekStart, $department]);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$weekStart, $weekEnd, $weekStart]);
        }
        $results = $stmt->fetchAll();
        $grouped = [];
        foreach ($results as $row) {
            $uid = $row['user_id'];
            if (!isset($grouped[$uid])) {
                $grouped[$uid] = [
                    'user_id' => $uid,
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'employee_number' => $row['employee_number'],
                    'role' => $row['role'],
                    'days' => []
                ];
            }
            $date = $row['date'];
            if ($date) {
                $grouped[$uid]['days'][$date] = [
                    'date' => $date,
                    'time_in' => $row['time_in'],
                    'time_out' => $row['time_out'],
                    'overtime_hours' => $row['overtime_hours'],
                    'status' => $row['status'],
                    'notes' => $row['notes'],
                    'scheduled_in' => $row['scheduled_in'],
                    'scheduled_out' => $row['scheduled_out'],
                    'is_rest_day' => $row['is_rest_day']
                ];
            }
        }
        return $grouped;
    }

    public function save($userId, $date, $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO attendance (user_id, date, time_in, time_out, overtime_hours, status, notes, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                time_in = VALUES(time_in),
                time_out = VALUES(time_out),
                overtime_hours = VALUES(overtime_hours),
                status = VALUES(status),
                notes = VALUES(notes),
                recorded_by = VALUES(recorded_by),
                updated_at = NOW()
        ");
        return $stmt->execute([
            $userId,
            $date,
            $data['time_in'] ?? null,
            $data['time_out'] ?? null,
            $data['overtime_hours'] ?? 0,
            $data['status'] ?? 'absent',
            $data['notes'] ?? null,
            $data['recorded_by'] ?? null
        ]);
    }

    public function getUserAttendance($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM attendance
            WHERE user_id = ? AND date BETWEEN ? AND ?
            ORDER BY date ASC
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll();
    }
}