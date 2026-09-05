<?php
namespace App\Models;

use App\Core\Database;

class GoodsReceipt
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($poId, $receivedBy, $receiptDate, $notes, $items)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO goods_receipts (po_id, received_by, receipt_date, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$poId, $receivedBy, $receiptDate, $notes]);
            $grId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare("
                INSERT INTO goods_receipt_items (goods_receipt_id, po_item_id, quantity_received, `condition`, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $itemStmt->execute([
                    $grId,
                    $item['po_item_id'],
                    $item['quantity_received'],
                    $item['condition'] ?? 'good',
                    $item['notes'] ?? null,
                ]);

                $upd = $this->db->prepare("UPDATE purchase_order_items SET received_quantity = received_quantity + ? WHERE id = ?");
                $upd->execute([$item['quantity_received'], $item['po_item_id']]);

                // Only 'good' condition stock actually becomes sellable inventory --
                // damaged/missing items were never usable, so they shouldn't inflate
                // stock_quantity even though they're still logged on the receipt.
                if (($item['condition'] ?? 'good') === 'good') {
                    $stockUpd = $this->db->prepare("
                        UPDATE products p
                        JOIN purchase_order_items poi ON poi.store_product_id = p.id
                        SET p.stock_quantity = p.stock_quantity + ?
                        WHERE poi.id = ?
                    ");
                    $stockUpd->execute([$item['quantity_received'], $item['po_item_id']]);
                }
            }

            $this->db->commit();
            return $grId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getByPoId($poId)
    {
        $stmt = $this->db->prepare("
            SELECT gr.*, CONCAT(u.first_name, ' ', u.last_name) as received_by_name
            FROM goods_receipts gr
            JOIN users u ON gr.received_by = u.user_id
            WHERE gr.po_id = ?
            ORDER BY gr.created_at DESC
        ");
        $stmt->execute([$poId]);
        return $stmt->fetchAll();
    }

    public function getItems($goodsReceiptId)
    {
        $stmt = $this->db->prepare("
            SELECT gri.*, p.name as store_product_name
            FROM goods_receipt_items gri
            JOIN purchase_order_items poi ON gri.po_item_id = poi.id
            JOIN products p ON poi.store_product_id = p.id
            WHERE gri.goods_receipt_id = ?
        ");
        $stmt->execute([$goodsReceiptId]);
        return $stmt->fetchAll();
    }
}
