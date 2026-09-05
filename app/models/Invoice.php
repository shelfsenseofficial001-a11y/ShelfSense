<?php
namespace App\Models;

use App\Core\Database;

class Invoice
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generateNumber()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM invoices WHERE YEAR(created_at) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetch()['count'] + 1;
        return 'INV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO invoices (invoice_number, po_id, supplier_id, invoice_date, due_date, subtotal, tax, total, file_path, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['invoice_number'],
            $data['po_id'],
            $data['supplier_id'],
            $data['invoice_date'],
            $data['due_date'],
            $data['subtotal'] ?? 0,
            $data['tax'] ?? 0,
            $data['total'] ?? 0,
            $data['file_path'] ?? null,
            $data['notes'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function addItem($invoiceId, $poItemId, $billedQuantity, $billedUnitPrice)
    {
        $total = round($billedQuantity * $billedUnitPrice, 2);
        $stmt = $this->db->prepare("
            INSERT INTO invoice_items (invoice_id, po_item_id, billed_quantity, billed_unit_price, billed_total)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$invoiceId, $poItemId, $billedQuantity, $billedUnitPrice, $total]);
        return (int)$this->db->lastInsertId();
    }

    public function updateItemVariance($itemId, $quantityVariance, $priceVariance, $flag)
    {
        $stmt = $this->db->prepare("UPDATE invoice_items SET quantity_variance = ?, price_variance = ?, variance_flag = ? WHERE id = ?");
        return $stmt->execute([$quantityVariance, $priceVariance, $flag, $itemId]);
    }

    public function setMatchStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE invoices SET match_status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, s.company_name as supplier_name, po.po_number, po.requisition_id
            FROM invoices i
            JOIN suppliers s ON i.supplier_id = s.id
            JOIN purchase_orders po ON i.po_id = po.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getItems($invoiceId)
    {
        $stmt = $this->db->prepare("
            SELECT ii.*, p.name as store_product_name, poi.quantity as po_quantity, poi.unit_price as po_unit_price
            FROM invoice_items ii
            JOIN purchase_order_items poi ON ii.po_item_id = poi.id
            JOIN products p ON poi.store_product_id = p.id
            WHERE ii.invoice_id = ?
        ");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    public function getWithItems($id)
    {
        $invoice = $this->getById($id);
        if (!$invoice) {
            return null;
        }
        $invoice['items'] = $this->getItems($id);
        return $invoice;
    }

    public function getByPoId($poId)
    {
        $stmt = $this->db->prepare("SELECT * FROM invoices WHERE po_id = ? ORDER BY created_at DESC");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['match_status'])) {
            $where .= " AND i.match_status = ?";
            $params[] = $filters['match_status'];
        }
        if (!empty($filters['match_statuses']) && is_array($filters['match_statuses'])) {
            $placeholders = implode(',', array_fill(0, count($filters['match_statuses']), '?'));
            $where .= " AND i.match_status IN ($placeholders)";
            foreach ($filters['match_statuses'] as $s) {
                $params[] = $s;
            }
        }
        if (!empty($filters['supplier_id'])) {
            $where .= " AND i.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM invoices i WHERE $where");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        $sql = "
            SELECT i.*, s.company_name as supplier_name, po.po_number
            FROM invoices i
            JOIN suppliers s ON i.supplier_id = s.id
            JOIN purchase_orders po ON i.po_id = po.id
            WHERE $where
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'invoices' => $stmt->fetchAll(),
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => (int)ceil($total / $limit),
            ],
        ];
    }
}
