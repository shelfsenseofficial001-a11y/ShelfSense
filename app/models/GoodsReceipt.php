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

    public function generateNumber()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM goods_receipts WHERE YEAR(created_at) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetch()['count'] + 1;
        return 'GR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function create($poId, $receivedBy, $receiptDate, $notes, $items)
    {
        $this->db->beginTransaction();
        try {
            $grNumber = $this->generateNumber();
            $stmt = $this->db->prepare("INSERT INTO goods_receipts (gr_number, po_id, received_by, receipt_date, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$grNumber, $poId, $receivedBy, $receiptDate, $notes]);
            $grId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare("
                INSERT INTO goods_receipt_items (goods_receipt_id, po_item_id, quantity_received, `condition`, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertedItems = [];
            foreach ($items as $item) {
                $itemStmt->execute([
                    $grId,
                    $item['po_item_id'],
                    $item['quantity_received'],
                    $item['condition'] ?? 'good',
                    $item['notes'] ?? null,
                ]);
                $insertedItems[] = [
                    'goods_receipt_item_id' => (int)$this->db->lastInsertId(),
                    'po_item_id' => (int)$item['po_item_id'],
                    'quantity_received' => (int)$item['quantity_received'],
                    'condition' => $item['condition'] ?? 'good',
                ];

                // 'excess' is beyond what was ordered -- it never counts toward
                // the ordered quantity being fulfilled, and it's never auto-added
                // to sellable stock; it sits in po_excess_items until the Store
                // Manager accepts it into inventory or returns it to the supplier.
                if (($item['condition'] ?? 'good') !== 'excess') {
                    $upd = $this->db->prepare("UPDATE purchase_order_items SET received_quantity = received_quantity + ? WHERE id = ?");
                    $upd->execute([$item['quantity_received'], $item['po_item_id']]);
                }

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
            return ['gr_id' => $grId, 'gr_number' => $grNumber, 'items' => $insertedItems];
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
