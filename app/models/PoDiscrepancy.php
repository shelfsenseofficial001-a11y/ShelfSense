<?php
namespace App\Models;

use App\Core\Database;

/**
 * A single damaged/missing line raised by a Goods Receipt. Blocks the PO's
 * invoice path until the Supplier resolves it: Backorder (missing) just
 * needs a new delivery date and stops blocking right away -- the shortfall
 * stays open on the PO for a future receipt. RMA (damaged) needs a pickup
 * date but keeps blocking until a later, clean GR line against the same PO
 * item marks it resolved (see goods_receipts/create.php).
 */
class PoDiscrepancy
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO po_discrepancies (po_id, po_item_id, goods_receipt_item_id, type, quantity)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['po_id'],
            $data['po_item_id'],
            $data['goods_receipt_item_id'],
            $data['type'],
            $data['quantity'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM po_discrepancies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Every discrepancy ever raised for a PO, for display (store manager + supplier). */
    public function getByPo($poId)
    {
        $stmt = $this->db->prepare("
            SELECT d.*, p.name as store_product_name
            FROM po_discrepancies d
            JOIN purchase_order_items poi ON d.po_item_id = poi.id
            JOIN products p ON poi.store_product_id = p.id
            WHERE d.po_id = ?
            ORDER BY d.created_at ASC, d.id ASC
        ");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    /** Still-open (no supplier action taken yet) rows for a PO. */
    public function getOpenForPo($poId)
    {
        $stmt = $this->db->prepare("SELECT * FROM po_discrepancies WHERE po_id = ? AND status = 'open'");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    /** A 'missing'-type row still open against a given PO item, if any. */
    public function getOpenForPoItem($poItemId, $type = null)
    {
        $sql = "SELECT * FROM po_discrepancies WHERE po_item_id = ? AND status != 'resolved'";
        $params = [$poItemId];
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function markActionTaken($id, $extra = [])
    {
        $fields = ["status = 'action_taken'"];
        $params = [];
        foreach (['new_delivery_date', 'pickup_date', 'rma_number', 'resolution_notes'] as $col) {
            if (array_key_exists($col, $extra)) {
                $fields[] = "$col = ?";
                $params[] = $extra[$col];
            }
        }
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE po_discrepancies SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?");
        return $stmt->execute($params);
    }

    public function markResolved($id, $resolvedGoodsReceiptItemId)
    {
        $stmt = $this->db->prepare("
            UPDATE po_discrepancies
            SET status = 'resolved', resolved_goods_receipt_item_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$resolvedGoodsReceiptItemId, $id]);
    }

    /**
     * True if the PO still has anything standing in the way of invoicing:
     * any row with no action taken yet at all, or any damaged/RMA row that
     * hasn't had its replacement physically received and re-receipted.
     */
    public function hasBlockingDiscrepancy($poId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as c FROM po_discrepancies
            WHERE po_id = ? AND (status = 'open' OR (type = 'damaged' AND status != 'resolved'))
        ");
        $stmt->execute([$poId]);
        return (int)$stmt->fetch()['c'] > 0;
    }

    /** Every discrepancy across every PO, for the Store Manager's Backorders/RMA list. */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $where = "1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $where .= " AND d.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $where .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM po_discrepancies d WHERE $where");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        $sql = "
            SELECT d.*, p.name as store_product_name, po.po_number, s.company_name as supplier_name
            FROM po_discrepancies d
            JOIN purchase_order_items poi ON d.po_item_id = poi.id
            JOIN products p ON poi.store_product_id = p.id
            JOIN purchase_orders po ON d.po_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE $where
            ORDER BY d.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'discrepancies' => $stmt->fetchAll(),
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$limit,
                'totalRecords' => (int)$total,
                'totalPages' => (int)ceil($total / $limit),
            ],
        ];
    }

    /**
     * Quantity per PO item still owed as a replacement -- any discrepancy
     * not yet resolved, regardless of whether the supplier has taken action
     * on it (backordered/RMA-requested) or not. Lets the Store Manager log a
     * follow-up Goods Receipt once the original ordered quantity has
     * already been fully accounted for (good+damaged+missing == ordered),
     * which would otherwise leave "Remaining" at 0 and block all input.
     */
    public function getOutstandingQuantities($poId)
    {
        $stmt = $this->db->prepare("
            SELECT po_item_id, COALESCE(SUM(quantity), 0) as outstanding
            FROM po_discrepancies
            WHERE po_id = ? AND status != 'resolved'
            GROUP BY po_item_id
        ");
        $stmt->execute([$poId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int)$row['po_item_id']] = (int)$row['outstanding'];
        }
        return $out;
    }

    public function generateRmaNumber()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM po_discrepancies WHERE rma_number IS NOT NULL AND YEAR(created_at) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetch()['count'] + 1;
        return 'RMA-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
