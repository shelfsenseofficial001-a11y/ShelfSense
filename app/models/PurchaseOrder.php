<?php
namespace App\Models;

use App\Core\Database;

class PurchaseOrder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generateNumber()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE YEAR(created_at) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetch()['count'] + 1;
        return 'PO-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO purchase_orders (
                po_number, requisition_id, supplier_id, status, order_date,
                expected_delivery_date, subtotal, tax, total, terms, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['po_number'],
            $data['requisition_id'],
            $data['supplier_id'],
            $data['status'] ?? 'pending_dispatch',
            $data['order_date'],
            $data['expected_delivery_date'] ?? null,
            $data['subtotal'] ?? 0,
            $data['tax'] ?? 0,
            $data['total'] ?? 0,
            $data['terms'] ?? 'Net 30',
            $data['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function addItem($poId, $storeProductId, $supplierProductId, $quantity, $unitPrice)
    {
        $total = round($quantity * $unitPrice, 2);
        $stmt = $this->db->prepare("
            INSERT INTO purchase_order_items (po_id, store_product_id, supplier_product_id, quantity, unit_price, total)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$poId, $storeProductId, $supplierProductId, $quantity, $unitPrice, $total]);
        return (int)$this->db->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT po.*, s.company_name as supplier_name, s.email as supplier_email,
                   r.requisition_number, r.department_id, r.period_key, r.requested_by,
                   CONCAT(u.first_name, ' ', u.last_name) as created_by_name
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN requisitions r ON po.requisition_id = r.id
            JOIN users u ON po.created_by = u.user_id
            WHERE po.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getItems($poId)
    {
        $stmt = $this->db->prepare("
            SELECT poi.*, p.name as store_product_name, p.barcode, sp.name as supplier_product_name
            FROM purchase_order_items poi
            JOIN products p ON poi.store_product_id = p.id
            JOIN supplier_products sp ON poi.supplier_product_id = sp.id
            WHERE poi.po_id = ?
            ORDER BY poi.id
        ");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function getWithItems($id)
    {
        $po = $this->getById($id);
        if (!$po) {
            return null;
        }
        $po['items'] = $this->getItems($id);
        return $po;
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function markDispatched($id, $via = 'email')
    {
        $stmt = $this->db->prepare("
            UPDATE purchase_orders SET status = 'pending_confirmation', dispatched_at = NOW(), dispatched_via = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$via, $id]);
    }

    public function updateItemQuantityPrice($itemId, $quantity, $unitPrice)
    {
        $total = round($quantity * $unitPrice, 2);
        $stmt = $this->db->prepare("UPDATE purchase_order_items SET quantity = ?, unit_price = ?, total = ? WHERE id = ?");
        $stmt->execute([$quantity, $unitPrice, $total, $itemId]);
        return $total;
    }

    public function recalculateTotals($id)
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(total), 0) as subtotal FROM purchase_order_items WHERE po_id = ?");
        $stmt->execute([$id]);
        $subtotal = (float)$stmt->fetch()['subtotal'];
        $stmt = $this->db->prepare("UPDATE purchase_orders SET subtotal = ?, total = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$subtotal, $subtotal, $id]);
        return $subtotal;
    }

    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND po.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $placeholders = implode(',', array_fill(0, count($filters['statuses']), '?'));
            $where .= " AND po.status IN ($placeholders)";
            foreach ($filters['statuses'] as $s) {
                $params[] = $s;
            }
        }
        if (!empty($filters['supplier_id'])) {
            $where .= " AND po.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM purchase_orders po WHERE $where");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        $sql = "
            SELECT po.*, s.company_name as supplier_name, r.requisition_number
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            JOIN requisitions r ON po.requisition_id = r.id
            WHERE $where
            ORDER BY po.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'purchase_orders' => $stmt->fetchAll(),
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => (int)ceil($total / $limit),
            ],
        ];
    }

    /** Total received per PO item so far, across every goods receipt. */
    public function getReceivedQuantities($poId)
    {
        $stmt = $this->db->prepare("
            SELECT po_item_id, COALESCE(SUM(quantity_received), 0) as received
            FROM goods_receipt_items gri
            JOIN goods_receipts gr ON gri.goods_receipt_id = gr.id
            WHERE gr.po_id = ?
            GROUP BY po_item_id
        ");
        $stmt->execute([$poId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int)$row['po_item_id']] = (int)$row['received'];
        }
        return $out;
    }
}
