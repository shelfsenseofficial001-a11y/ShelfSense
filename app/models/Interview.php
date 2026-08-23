<?php
namespace App\Models;

use App\Core\Database;

class Interview
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO interviews (applicant_id, hr_user_id, interview_type, scheduled_date, gmeet_link, message, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $result = $stmt->execute([
            $data['applicant_id'],
            $data['hr_user_id'],
            $data['interview_type'],
            $data['scheduled_date'],
            $data['gmeet_link'] ?? null,
            $data['message'] ?? null
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    public function getByApplicant($applicantId)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, u.first_name, u.last_name 
            FROM interviews i
            LEFT JOIN users u ON i.hr_user_id = u.user_id
            WHERE i.applicant_id = ? 
            ORDER BY i.scheduled_date DESC
        ");
        $stmt->execute([$applicantId]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, u.first_name, u.last_name 
            FROM interviews i
            LEFT JOIN users u ON i.hr_user_id = u.user_id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateResult($id, $result, $notes = null)
    {
        $stmt = $this->db->prepare("
            UPDATE interviews 
            SET result = ?, notes = ?, status = 'completed', updated_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$result, $notes, $id]);
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE interviews SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getUpcoming($hrUserId = null, $limit = 5)
    {
        $sql = "
            SELECT i.*, a.first_name, a.last_name, a.email 
            FROM interviews i
            JOIN applicants a ON i.applicant_id = a.id
            WHERE i.status = 'scheduled' 
            AND i.scheduled_date > NOW()
            ORDER BY i.scheduled_date ASC
            LIMIT ?
        ";
        
        $params = [$limit];
        
        if ($hrUserId) {
            $sql = "
                SELECT i.*, a.first_name, a.last_name, a.email 
                FROM interviews i
                JOIN applicants a ON i.applicant_id = a.id
                WHERE i.status = 'scheduled' 
                AND i.scheduled_date > NOW()
                AND i.hr_user_id = ?
                ORDER BY i.scheduled_date ASC
                LIMIT ?
            ";
            $params = [$hrUserId, $limit];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getInitialInterview($applicantId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM interviews 
            WHERE applicant_id = ? AND interview_type = 'initial'
            ORDER BY scheduled_date DESC 
            LIMIT 1
        ");
        $stmt->execute([$applicantId]);
        return $stmt->fetch();
    }

    public function getFinalInterview($applicantId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM interviews 
            WHERE applicant_id = ? AND interview_type = 'final' AND status = 'scheduled'
            ORDER BY scheduled_date ASC LIMIT 1
        ");
        $stmt->execute([$applicantId]);
        return $stmt->fetch();
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        if (isset($data['scheduled_date'])) {
            $fields[] = "scheduled_date = ?";
            $params[] = $data['scheduled_date'];
        }
        if (isset($data['gmeet_link'])) {
            $fields[] = "gmeet_link = ?";
            $params[] = $data['gmeet_link'];
        }
        if (isset($data['message'])) {
            $fields[] = "message = ?";
            $params[] = $data['message'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE interviews SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function hasConflict($hrUserId, $scheduledDate)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM interviews 
            WHERE hr_user_id = ? 
            AND status = 'scheduled'
            AND scheduled_date = ?
        ");
        $stmt->execute([$hrUserId, $scheduledDate]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function updateApplicantStatus($applicantId, $status)
    {
        $stmt = $this->db->prepare("UPDATE applicants SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $applicantId]);
    }
}