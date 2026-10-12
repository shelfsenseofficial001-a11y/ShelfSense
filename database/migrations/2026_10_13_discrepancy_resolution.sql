-- Today a Goods Receipt logs damaged/missing lines but nothing downstream reacts to them --
-- the PO still becomes received/partially_received exactly like a clean delivery, and a
-- Supplier can invoice (and get paid) for units that were never usable. This adds a PO-level
-- freeze ('discrepancy_logged') the moment a GR finds damage/missing stock, plus a per-line
-- resolution record: Backorder (missing, just needs a new delivery date, unblocks invoicing
-- for the quantity already received in good condition) or RMA Replace & Return (damaged,
-- keeps the whole PO's invoice path locked until a replacement arrives and is re-receipted
-- clean).

ALTER TABLE `purchase_orders`
    MODIFY COLUMN `status` ENUM('pending_budget_check','budget_rejected','pending_fh_approval','pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed','confirmed','pending_payment','paid','shipped','partially_received','received','cancelled','closed','discrepancy_logged') NOT NULL DEFAULT 'pending_budget_check';

CREATE TABLE IF NOT EXISTS `po_discrepancies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_id` INT NOT NULL,
    `po_item_id` INT NOT NULL,
    `goods_receipt_item_id` INT NOT NULL,
    `type` ENUM('missing','damaged') NOT NULL,
    `quantity` INT NOT NULL,
    `status` ENUM('open','action_taken','resolved') NOT NULL DEFAULT 'open',
    `new_delivery_date` DATE NULL,
    `pickup_date` DATE NULL,
    `rma_number` VARCHAR(50) NULL,
    `resolved_goods_receipt_item_id` INT NULL,
    `resolution_notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_disc_po` (`po_id`),
    KEY `idx_disc_status` (`status`),
    CONSTRAINT `fk_disc_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_disc_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`),
    CONSTRAINT `fk_disc_gr_item` FOREIGN KEY (`goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`),
    CONSTRAINT `fk_disc_resolved_gr_item` FOREIGN KEY (`resolved_goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
