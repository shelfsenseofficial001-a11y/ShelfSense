<?php
namespace App\Models;

use App\Core\Database;

class Supplier
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($page = 1, $limit = 20, $search = '')
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($search)) {
            $where .= " AND (company_name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
            $searchParam = "%" . $search . "%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $countSql = "SELECT COUNT(*) as total FROM suppliers WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $sql = "SELECT * FROM suppliers WHERE $where ORDER BY company_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $suppliers = $stmt->fetchAll();

        return [
            'suppliers' => $suppliers,
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
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO suppliers (company_name, contact_person, email, phone, address, tax_id, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $data['company_name'],
            $data['contact_person'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['tax_id'] ?? null,
            $data['notes'] ?? null
        ]);
        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE suppliers 
            SET company_name = ?, contact_person = ?, email = ?, phone = ?, 
                address = ?, tax_id = ?, notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['company_name'],
            $data['contact_person'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['tax_id'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleActive($id, $active)
    {
        $stmt = $this->db->prepare("UPDATE suppliers SET is_active = ? WHERE id = ?");
        return $stmt->execute([$active ? 1 : 0, $id]);
    }
}