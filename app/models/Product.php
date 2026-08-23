<?php
namespace App\Models;

use App\Core\Database;

class Product
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "p.is_active = 1";
        $params = [];

        if (!empty($filters['category_id'])) {
            $where .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (p.name LIKE ? OR p.barcode LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
        }

        // Count
        $countSql = "SELECT COUNT(*) as total FROM products p WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Data
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE $where
                ORDER BY p.name
                LIMIT ? OFFSET ?";
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

    public function getByBarcode($barcode)
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name 
                                    FROM products p
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    WHERE p.barcode = ? AND p.is_active = 1");
        $stmt->execute([$barcode]);
        return $stmt->fetch();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name 
                                    FROM products p
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function search($query, $limit = 20)
    {
        $search = "%" . $query . "%";
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1 AND (p.name LIKE ? OR p.barcode LIKE ?)
            ORDER BY p.name
            LIMIT ?
        ");
        $stmt->execute([$search, $search, $limit]);
        return $stmt->fetchAll();
    }

    public function reduceStock($productId, $quantity)
    {
        $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
        return $stmt->execute([$quantity, $productId, $quantity]);
    }

    public function increaseStock($productId, $quantity)
    {
        $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        return $stmt->execute([$quantity, $productId]);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO products (barcode, name, description, category_id, price, cost, stock_quantity, reorder_level, image_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['barcode'],
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'] ?? null,
            $data['price'],
            $data['cost'] ?? null,
            $data['stock_quantity'] ?? 0,
            $data['reorder_level'] ?? 5,
            $data['image_path'] ?? null
        ]);
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        $allowed = ['barcode','name','description','category_id','price','cost','stock_quantity','reorder_level','image_path','is_active'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}