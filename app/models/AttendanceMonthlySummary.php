<?php
namespace App\Models;

use App\Core\Database;

class AttendanceMonthlySummary
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getOrCreate($monthYear)
    {
        $stmt = $this->db->prepare("SELECT * FROM attendance_monthly_summaries WHERE month_year = ?");
        $stmt->execute([$monthYear]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        // Create new
        $stmt = $this->db->prepare("
            INSERT INTO attendance_monthly_summaries (month_year, total_employees, total_weeks, overall_status)
            VALUES (?, (SELECT COUNT(*) FROM users WHERE is_active = 1 AND role != 'trainee'), 4, 'draft')
        ");
        $stmt->execute([$monthYear]);
        $stmt = $this->db->prepare("SELECT * FROM attendance_monthly_summaries WHERE month_year = ?");
        $stmt->execute([$monthYear]);
        return $stmt->fetch();
    }

    public function updateOverallStatus($monthYear, $status, $actorId = null)
    {
        $sql = "UPDATE attendance_monthly_summaries SET overall_status = ?";
        $params = [$status];
        if ($actorId) {
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $actorId;
        }
        $sql .= " WHERE month_year = ?";
        $params[] = $monthYear;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}