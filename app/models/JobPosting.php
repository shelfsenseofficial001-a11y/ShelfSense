<?php
namespace App\Models;

use App\Core\Database;

class JobPosting
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($page = 1, $limit = 15, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where .= " AND jp.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where .= " AND (jp.title LIKE ? OR jp.department LIKE ? OR jp.role LIKE ?)";
            $like = "%{$filters['search']}%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['created_by'])) {
            $where .= " AND jp.created_by = ?";
            $params[] = $filters['created_by'];
        }

        $countSql = "SELECT COUNT(*) as total FROM job_postings jp WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $sql = "
            SELECT jp.*, u1.first_name as creator_first, u1.last_name as creator_last,
                   u2.first_name as approver_first, u2.last_name as approver_last,
                   u3.first_name as rejecter_first, u3.last_name as rejecter_last,
                   (
                       SELECT COUNT(*) FROM job_postings jp2
                       WHERE jp2.id != jp.id
                         AND jp2.location IS NOT NULL AND jp.location IS NOT NULL
                         AND LOWER(jp2.location) = LOWER(jp.location)
                         AND jp2.status IN ('draft', 'pending_approval', 'approved')
                   ) as shares_location_count
            FROM job_postings jp
            LEFT JOIN users u1 ON jp.created_by = u1.user_id
            LEFT JOIN users u2 ON jp.approved_by = u2.user_id
            LEFT JOIN users u3 ON jp.rejected_by = u3.user_id
            WHERE $where
            ORDER BY jp.updated_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($params, [(int)$limit, (int)$offset]));

        return [
            'postings' => $stmt->fetchAll(),
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => (int)ceil($total / $limit)
            ]
        ];
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT jp.*, u1.first_name as creator_first, u1.last_name as creator_last,
                   u2.first_name as approver_first, u2.last_name as approver_last,
                   u3.first_name as rejecter_first, u3.last_name as rejecter_last,
                   (
                       SELECT COUNT(*) FROM job_postings jp2
                       WHERE jp2.id != jp.id
                         AND jp2.location IS NOT NULL AND jp.location IS NOT NULL
                         AND LOWER(jp2.location) = LOWER(jp.location)
                         AND jp2.status IN ('draft', 'pending_approval', 'approved')
                   ) as shares_location_count
            FROM job_postings jp
            LEFT JOIN users u1 ON jp.created_by = u1.user_id
            LEFT JOIN users u2 ON jp.approved_by = u2.user_id
            LEFT JOIN users u3 ON jp.rejected_by = u3.user_id
            WHERE jp.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Every earlier/later posting sharing the same reuse lineage, oldest first. */
    public function getLineage($id)
    {
        $posting = $this->getById($id);
        if (!$posting) return [];
        $rootId = $posting['reused_from_id'] ?: $id;

        $stmt = $this->db->prepare("
            SELECT id, status, title, created_at, submitted_at, approved_at, rejected_at, archived_at, reused_from_id
            FROM job_postings
            WHERE id = ? OR reused_from_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$rootId, $rootId]);
        return $stmt->fetchAll();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO job_postings
                (title, department_group, department, location, role, employment_type, description, requirements, responsibilities,
                 salary_range_min, salary_range_max, slots, open_until, status, created_by, reused_from_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $data['title'], $data['department_group'] ?? 'Front Department', $data['department'], $data['location'] ?? null, $data['role'], $data['employment_type'] ?? 'Full-Time',
            $data['description'], $data['requirements'] ?? null, $data['responsibilities'] ?? null,
            $data['salary_range_min'] ?? null, $data['salary_range_max'] ?? null, $data['slots'] ?? null,
            $data['open_until'], $data['status'] ?? 'draft', $data['created_by'], $data['reused_from_id'] ?? null
        ]);
        return $result ? (int)$this->db->lastInsertId() : false;
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        foreach (['title', 'department_group', 'department', 'location', 'role', 'employment_type', 'description', 'requirements',
                   'responsibilities', 'salary_range_min', 'salary_range_max', 'slots', 'open_until'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $fields[] = "updated_at = NOW()";
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE job_postings SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function submitForApproval($id)
    {
        $stmt = $this->db->prepare("UPDATE job_postings SET status = 'pending_approval', submitted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function approve($id, $approvedBy)
    {
        $stmt = $this->db->prepare("UPDATE job_postings SET status = 'approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL, rejected_by = NULL, rejected_at = NULL WHERE id = ?");
        return $stmt->execute([$approvedBy, $id]);
    }

    public function reject($id, $rejectedBy, $reason)
    {
        $stmt = $this->db->prepare("UPDATE job_postings SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE id = ?");
        return $stmt->execute([$rejectedBy, $reason, $id]);
    }

    public function archive($id)
    {
        $stmt = $this->db->prepare("UPDATE job_postings SET status = 'archived', archived_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function close($id)
    {
        $stmt = $this->db->prepare("UPDATE job_postings SET status = 'closed' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Public, real, currently-hiring postings only -- approved, not past
     * their closing date, and with at least one remaining slot (a NULL
     * slots value means unlimited openings). This is the single source of
     * eligibility for both the landing page and the application form, so
     * the two can never show different sets of jobs.
     *
     * A slot is consumed the moment an applicant for this posting reaches
     * the Trainee stage (a non-terminated row in `trainees`) -- not only
     * once they're fully hired -- since a trainee already occupies the
     * headcount for that role during their 3-month training period. A
     * terminated trainee frees the slot back up.
     */
    public function getPublicListings()
    {
        $stmt = $this->db->prepare("
            SELECT jp.id, jp.title, jp.department_group, jp.department, jp.location, jp.role, jp.employment_type,
                   jp.description, jp.requirements, jp.responsibilities,
                   jp.salary_range_min, jp.salary_range_max, jp.slots, jp.open_until,
                   COALESCE(occupied.cnt, 0) AS hired_count
            FROM job_postings jp
            LEFT JOIN (
                SELECT a.job_posting_id, COUNT(*) AS cnt
                FROM trainees t
                JOIN applicants a ON a.id = t.applicant_id
                WHERE t.status != 'terminated' AND a.job_posting_id IS NOT NULL
                GROUP BY a.job_posting_id
            ) occupied ON occupied.job_posting_id = jp.id
            WHERE jp.status = 'approved'
              AND jp.open_until >= CURDATE()
              AND (jp.slots IS NULL OR COALESCE(occupied.cnt, 0) < jp.slots)
            ORDER BY jp.approved_at DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['remaining_slots'] = $row['slots'] !== null ? max(0, (int)$row['slots'] - (int)$row['hired_count']) : null;
        }
        unset($row);
        return $this->flagDuplicateLocations($rows);
    }

    /**
     * Re-validates one job posting against the exact same eligibility rules
     * as getPublicListings(), for authoritative checks at application
     * submission time -- never trust what the browser last saw at page load.
     */
    public function getEligibleJobById($id)
    {
        $stmt = $this->db->prepare("
            SELECT jp.id, jp.title, jp.department, jp.role, jp.slots,
                   COALESCE(occupied.cnt, 0) AS hired_count
            FROM job_postings jp
            LEFT JOIN (
                SELECT a.job_posting_id, COUNT(*) AS cnt
                FROM trainees t
                JOIN applicants a ON a.id = t.applicant_id
                WHERE t.status != 'terminated' AND a.job_posting_id IS NOT NULL
                GROUP BY a.job_posting_id
            ) occupied ON occupied.job_posting_id = jp.id
            WHERE jp.id = ?
              AND jp.status = 'approved'
              AND jp.open_until >= CURDATE()
              AND (jp.slots IS NULL OR COALESCE(occupied.cnt, 0) < jp.slots)
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    /**
     * Marks each posting with whether another still-live posting shares its
     * exact location -- informational only (per product decision, sharing a
     * location across different departments is allowed), surfaced in the
     * HR job postings list so HR can see it, not used to block anything.
     */
    private function flagDuplicateLocations($rows)
    {
        if (empty($rows)) return $rows;
        $counts = [];
        foreach ($rows as $row) {
            $loc = trim((string)($row['location'] ?? ''));
            if ($loc === '') continue;
            $key = mb_strtolower($loc);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        foreach ($rows as &$row) {
            $loc = trim((string)($row['location'] ?? ''));
            $row['shares_location'] = $loc !== '' && ($counts[mb_strtolower($loc)] ?? 0) > 1;
        }
        unset($row);
        return $rows;
    }

    public function getStatusCounts()
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
            FROM job_postings
        ");
        return $stmt->fetch();
    }
}
