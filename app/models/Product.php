<?php
namespace App\Models;

use App\Core\Database;

class Product
{
    private $db;

    // Shared by every read method below -- COALESCEs a Catalog & Deals
    // override over the base product row for anything sellable (name,
    // description, category, price, cost, discount, image). Inventory
    // reads products directly with its own query (see
    // app/handlers/store_manager/get_inventory.php) and never sees this,
    // by design: Catalog & Deals edits are never supposed to change what
    // Inventory shows.
    private const SELECT_WITH_OVERRIDES = "
        SELECT
            p.id,
            p.barcode,
            COALESCE(co.name, p.name) AS name,
            COALESCE(co.description, p.description) AS description,
            COALESCE(co.category_id, p.category_id) AS category_id,
            COALESCE(co.price, p.price) AS price,
            COALESCE(co.cost, p.cost) AS cost,
            COALESCE(co.discount_value, p.discount_value) AS discount_value,
            COALESCE(co.discount_type, p.discount_type) AS discount_type,
            COALESCE(co.image_path, p.image_path) AS image_path,
            p.stock_quantity,
            p.reorder_level,
            p.is_active,
            p.created_at,
            p.updated_at,
            c.name AS category_name
        FROM products p
        LEFT JOIN catalog_overrides co ON co.product_id = p.id
        LEFT JOIN categories c ON c.id = COALESCE(co.category_id, p.category_id)
    ";

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
            $where .= " AND COALESCE(co.category_id, p.category_id) = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (COALESCE(co.name, p.name) LIKE ? OR p.barcode LIKE ?)";
            $search = "%" . $filters['search'] . "%";
            $params[] = $search;
            $params[] = $search;
        }

        // Count
        $countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN catalog_overrides co ON co.product_id = p.id WHERE $where";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Data
        $sql = self::SELECT_WITH_OVERRIDES . "
                WHERE $where
                ORDER BY COALESCE(co.name, p.name)
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
        $stmt = $this->db->prepare(self::SELECT_WITH_OVERRIDES . " WHERE p.barcode = ? AND p.is_active = 1");
        $stmt->execute([$barcode]);
        return $stmt->fetch();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_OVERRIDES . " WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function search($query, $limit = 20)
    {
        $search = "%" . $query . "%";
        $stmt = $this->db->prepare(self::SELECT_WITH_OVERRIDES . "
            WHERE p.is_active = 1 AND (COALESCE(co.name, p.name) LIKE ? OR p.barcode LIKE ?)
            ORDER BY COALESCE(co.name, p.name)
            LIMIT ?
        ");
        $stmt->execute([$search, $search, $limit]);
        return $stmt->fetchAll();
    }

    public function reduceStock($productId, $quantity)
    {
        $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
        $stmt->execute([$quantity, $productId, $quantity]);
        // execute() reports the query ran, not whether the `stock_quantity >= ?`
        // guard actually matched a row -- rowCount() is the real signal that
        // stock was insufficient at update time (e.g. another order beat this
        // one to the last units between the earlier check and this call).
        return $stmt->rowCount() > 0;
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

    public static function withEffectivePrice(array $product): array
    {
        $product['discount_type'] = $product['discount_type'] ?? 'percent';
        $product['discount_value'] = (float)($product['discount_value'] ?? 0);
        $product['original_price'] = (float)$product['price'];

        $discountAmount = 0;
        if ($product['discount_value'] > 0) {
            $discountAmount = $product['discount_type'] === 'fixed'
                ? min($product['discount_value'], $product['original_price'])
                : $product['original_price'] * ($product['discount_value'] / 100);
        }

        $product['price'] = round($product['original_price'] - $discountAmount, 2);
        $product['has_discount'] = $discountAmount > 0;
        return $product;
    }
}
