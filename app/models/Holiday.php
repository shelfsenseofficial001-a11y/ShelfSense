<?php
namespace App\Models;

use App\Core\Database;

class Holiday
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($year = null)
    {
        $sql = "SELECT * FROM holidays";
        $params = [];
        if ($year) {
            $sql .= " WHERE YEAR(holiday_date) = ?";
            $params[] = (int)$year;
        }
        $sql .= " ORDER BY holiday_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByDate($date)
    {
        $stmt = $this->db->prepare("SELECT * FROM holidays WHERE holiday_date = ?");
        $stmt->execute([$date]);
        return $stmt->fetch();
    }

    public function isHoliday($date)
    {
        return (bool)$this->getByDate($date);
    }

    public function create($date, $name, $type, $createdBy)
    {
        $stmt = $this->db->prepare("
            INSERT INTO holidays (holiday_date, name, type, created_by)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$date, $name, $type, $createdBy]);
    }

    public function update($id, $date, $name, $type)
    {
        $stmt = $this->db->prepare("
            UPDATE holidays SET holiday_date = ?, name = ?, type = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$date, $name, $type, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM holidays WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
