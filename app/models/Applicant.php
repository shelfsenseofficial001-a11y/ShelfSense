<?php
namespace App\Models;

use App\Core\Database;

class Applicant
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($page = 1, $limit = 15, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $where = "1=1";

        // Status filter - only if not 'all'
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where .= " AND status = ?";
            $params[] = $filters['status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR target_role LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        // Role filter - only if NOT 'all' and NOT empty
        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $where .= " AND target_role = ?";
            $params[] = $filters['role'];
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM applicants WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Get data
        $sql = "SELECT * FROM applicants WHERE $where ORDER BY applied_date DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $queryParams = array_merge($params, [(int)$limit, (int)$offset]);
        $stmt->execute($queryParams);
        $applicants = $stmt->fetchAll();

        return [
            'applicants' => $applicants,
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => ceil($total / $limit)
            ]
        ];
    }

    public function getById($id)
    {
        // job_department/job_role come along so the caller can resolve which
        // skills questionnaire (if any) applies via jobPostingToQuestionnaireKey()
        // -- the same mapping used at application time, never re-guessed from
        // the applicant's own target_role (that value is a display label, not
        // a controlled key).
        $stmt = $this->db->prepare("
            SELECT a.*, jp.department AS job_department, jp.role AS job_role
            FROM applicants a
            LEFT JOIN job_postings jp ON jp.id = a.job_posting_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $applicant = $stmt->fetch();

        if ($applicant) {
            // Get interviews
            $stmt = $this->db->prepare("SELECT * FROM interviews WHERE applicant_id = ? ORDER BY scheduled_date DESC");
            $stmt->execute([$id]);
            $applicant['interviews'] = $stmt->fetchAll();

            // Skills self-assessment answers, keyed by skill_key -> 1-5 rating.
            $stmt = $this->db->prepare("SELECT skill_key, rating FROM applicant_skill_ratings WHERE applicant_id = ?");
            $stmt->execute([$id]);
            $ratings = [];
            foreach ($stmt->fetchAll() as $row) {
                $ratings[$row['skill_key']] = (int)$row['rating'];
            }
            $applicant['skill_ratings'] = $ratings;
        }

        return $applicant;
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE applicants SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getStatusCounts()
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status IN ('initial_scheduled', 'final_scheduled') THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status IN ('initial_passed', 'final_passed') THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN status IN ('initial_failed', 'final_failed', 'screening_failed', 'contract_declined') THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'screening' THEN 1 ELSE 0 END) as screening,
                SUM(CASE WHEN status = 'hired' THEN 1 ELSE 0 END) as hired
            FROM applicants
        ");
        return $stmt->fetch();
    }

    public function addRejectionReason($applicantId, $hrUserId, $stage, $reason = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO rejection_reasons (applicant_id, hr_user_id, stage, reason) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$applicantId, $hrUserId, $stage, $reason]);
    }
}