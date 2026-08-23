<?php
namespace App\Models;

use App\Core\Database;

class SupplierProduct
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($supplierId, $page = 1, $limit = 20, $search = '', $statusFilter = '')
    {
        $offset = ($page - 1) * $limit;
        $where = "supplier_id = ?";
        $params = [$supplierId];

        if (!empty($search)) {
            $where .= " AND (name LIKE ? OR description LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if ($statusFilter === 'active') {
            $where .= " AND is_active = 1";
        } elseif ($statusFilter === 'inactive') {
            $where .= " AND is_active = 0";
        }

        $countSql = "SELECT COUNT(*) as total FROM supplier_products WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $sql = "SELECT * FROM supplier_products WHERE $where ORDER BY name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        return [
            'products' => $products,
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
        $stmt = $this->db->prepare("SELECT * FROM supplier_products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getBySupplierAndProduct($supplierId, $storeProductId)
    {
        // For 1-to-1 mapping: store product name matches supplier product name
        $stmt = $this->db->prepare("
            SELECT sp.* 
            FROM supplier_products sp
            JOIN products p ON p.name = sp.name
            WHERE sp.supplier_id = ? AND p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$supplierId, $storeProductId]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO supplier_products (supplier_id, name, description, price)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['supplier_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['price']
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE supplier_products 
            SET name = ?, description = ?, price = ?, is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['price'],
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM supplier_products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getSupplierIdFromProduct($productId)
    {
        // Get supplier_id from supplier_products where name matches store product name
        $stmt = $this->db->prepare("
            SELECT sp.supplier_id 
            FROM supplier_products sp
            JOIN products p ON p.name = sp.name
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $result = $stmt->fetch();
        return $result ? $result['supplier_id'] : null;
    }

    public function getByStoreProductId($storeProductId)
    {
        $stmt = $this->db->prepare("
            SELECT sp.* 
            FROM supplier_products sp
            JOIN products p ON p.name = sp.name
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$storeProductId]);
        return $stmt->fetch();
    }
}