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
        $storeProductId = $data['store_product_id'] ?? $this->resolveStoreProductId($data['name']);
        $stmt = $this->db->prepare("
            INSERT INTO supplier_products (supplier_id, store_product_id, name, description, price, quantity)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['supplier_id'],
            $storeProductId,
            $data['name'],
            $data['description'] ?? null,
            $data['price'],
            $data['quantity'] ?? 0
        ]);
    }

    public function update($id, $data)
    {
        $storeProductId = $data['store_product_id'] ?? $this->resolveStoreProductId($data['name']);
        $stmt = $this->db->prepare("
            UPDATE supplier_products
            SET name = ?, description = ?, price = ?, quantity = ?, store_product_id = ?, is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['price'],
            $data['quantity'] ?? 0,
            $storeProductId,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    /** Best-effort link to the internal catalog when a supplier doesn't pick one explicitly: exact name match. */
    private function resolveStoreProductId($name)
    {
        $stmt = $this->db->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    /**
     * Given the store products (and quantities) a Store Manager has added to
     * a resupply request, returns only the suppliers who carry every one of
     * them with enough quantity on file -- narrowing as items are added.
     * Each store_product_id maps to ['quantity' => int].
     */
    public function getEligibleSuppliers(array $storeProductQuantities)
    {
        if (empty($storeProductQuantities)) {
            return [];
        }

        $storeProductIds = array_keys($storeProductQuantities);
        $placeholders = implode(',', array_fill(0, count($storeProductIds), '?'));
        $stmt = $this->db->prepare("
            SELECT sp.*, s.company_name as supplier_name
            FROM supplier_products sp
            JOIN suppliers s ON sp.supplier_id = s.id
            WHERE sp.store_product_id IN ($placeholders) AND sp.is_active = 1
        ");
        $stmt->execute($storeProductIds);
        $rows = $stmt->fetchAll();

        $bySupplier = [];
        foreach ($rows as $row) {
            $bySupplier[$row['supplier_id']]['name'] = $row['supplier_name'];
            $bySupplier[$row['supplier_id']]['items'][(int)$row['store_product_id']] = $row;
        }

        $eligible = [];
        foreach ($bySupplier as $supplierId => $data) {
            $covers = true;
            $lines = [];
            $total = 0.0;
            foreach ($storeProductQuantities as $storeProductId => $need) {
                $needQty = (int)($need['quantity'] ?? 0);
                $sp = $data['items'][$storeProductId] ?? null;
                if (!$sp || (int)$sp['quantity'] < $needQty) {
                    $covers = false;
                    break;
                }
                $lineTotal = round($needQty * (float)$sp['price'], 2);
                $total += $lineTotal;
                $lines[] = [
                    'store_product_id' => (int)$storeProductId,
                    'supplier_product_id' => (int)$sp['id'],
                    'unit_price' => (float)$sp['price'],
                    'available_quantity' => (int)$sp['quantity'],
                    'quantity' => $needQty,
                    'total' => $lineTotal,
                ];
            }
            if ($covers) {
                $eligible[] = [
                    'supplier_id' => (int)$supplierId,
                    'supplier_name' => $data['name'],
                    'items' => $lines,
                    'total' => round($total, 2),
                ];
            }
        }

        return $eligible;
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