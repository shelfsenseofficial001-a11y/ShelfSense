-- Goods Receipts had no human-readable identifier of their own -- unlike
-- requisitions/POs/invoices (REQ-/PO-/INV-YYYY-NNNN), a GR was only ever
-- referenced by its internal numeric id. Adds the same numbering scheme so
-- a receipt can actually be recorded/cited as a document, not just an
-- unlabeled timeline entry.

ALTER TABLE `goods_receipts` ADD COLUMN `gr_number` VARCHAR(30) NULL AFTER `id`;

UPDATE `goods_receipts` gr
JOIN (
    SELECT id, CONCAT('GR-', YEAR(created_at), '-', LPAD(ROW_NUMBER() OVER (PARTITION BY YEAR(created_at) ORDER BY created_at, id), 4, '0')) AS computed_number
    FROM `goods_receipts`
) numbered ON numbered.id = gr.id
SET gr.gr_number = numbered.computed_number;

ALTER TABLE `goods_receipts` MODIFY COLUMN `gr_number` VARCHAR(30) NOT NULL;
ALTER TABLE `goods_receipts` ADD UNIQUE KEY `uq_gr_number` (`gr_number`);
