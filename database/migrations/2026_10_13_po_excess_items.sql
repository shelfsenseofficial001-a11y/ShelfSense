-- Adds an 'excess' condition for goods received beyond the PO's ordered
-- quantity. Unlike good/damaged/missing, excess never auto-adds to sellable
-- stock_quantity and is excluded from the 3-way match's good-received total
-- (InvoiceMatcher only sums condition='good') -- it sits open until the
-- Store Manager decides to accept it into inventory (at a price, since it
-- was never on the PO) or return it to the supplier.

ALTER TABLE `goods_receipt_items` MODIFY COLUMN `condition` ENUM('good','damaged','missing','excess') NOT NULL DEFAULT 'good';

CREATE TABLE IF NOT EXISTS `po_excess_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_id` INT NOT NULL,
    `po_item_id` INT NOT NULL,
    `goods_receipt_item_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `status` ENUM('open','accepted','returned') NOT NULL DEFAULT 'open',
    `accepted_unit_price` DECIMAL(10,2) NULL,
    `resolution_notes` TEXT NULL,
    `resolved_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_excess_po` (`po_id`),
    KEY `idx_excess_status` (`status`),
    CONSTRAINT `fk_excess_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_excess_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`),
    CONSTRAINT `fk_excess_gr_item` FOREIGN KEY (`goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`),
    CONSTRAINT `fk_excess_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
