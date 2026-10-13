<?php
namespace App\Models;

use App\Core\Database;

/**
 * A line received in quantity beyond what the PO ordered. Logged on the
 * Goods Receipt for visibility but never auto-added to sellable stock or
 * counted toward the 3-way match -- stays 'open' until the Store Manager
 * either accepts it into inventory (at a price, since it was never on the
 * PO) or returns it to the supplier.
 */
class PoExcessItem
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO po_excess_items (po_id, po_item_id, goods_receipt_item_id, quantity)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['po_id'],
            $data['po_item_id'],
            $data['goods_receipt_item_id'],
            $data['quantity'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM po_excess_items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Every excess line ever raised for a PO, for display. */
    public function getByPo($poId)
    {
        $stmt = $this->db->prepare("
            SELECT e.*, p.name as store_product_name
            FROM po_excess_items e
            JOIN purchase_order_items poi ON e.po_item_id = poi.id
            JOIN products p ON poi.store_product_id = p.id
            WHERE e.po_id = ?
            ORDER BY e.created_at ASC, e.id ASC
        ");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function markAccepted($id, $unitPrice, $resolvedBy, $notes = null)
    {
        $stmt = $this->db->prepare("
            UPDATE po_excess_items
            SET status = 'accepted', accepted_unit_price = ?, resolution_notes = ?, resolved_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$unitPrice, $notes, $resolvedBy, $id]);
    }

    public function markReturned($id, $resolvedBy, $notes = null)
    {
        $stmt = $this->db->prepare("
            UPDATE po_excess_items
            SET status = 'returned', resolution_notes = ?, resolved_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$notes, $resolvedBy, $id]);
    }
}
