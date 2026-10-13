-- Reverts the negotiation-first/multi-supplier-split procurement redesign
-- (requisition_quotes, requisition_groups, rush_fee) -- the user decided to
-- go back to the original flow: Store Manager picks a supplier directly at
-- requisition time, Finance Head approval creates the PO immediately.
-- Unrelated schema added in the same session (gr_number, po_excess_items)
-- is NOT touched here.

DROP TABLE IF EXISTS `requisition_quote_items`;
DROP TABLE IF EXISTS `requisition_quotes`;
DROP TABLE IF EXISTS `requisition_quote_requests`;
DROP TABLE IF EXISTS `requisition_group_items`;

-- purchase_orders' FK to requisition_groups must go before the table itself.
ALTER TABLE `purchase_orders` DROP FOREIGN KEY `fk_po_group`;
ALTER TABLE `purchase_orders` DROP COLUMN `requisition_group_id`;
ALTER TABLE `purchase_orders` DROP COLUMN `rush_fee`;

DROP TABLE IF EXISTS `requisition_groups`;

ALTER TABLE `requisitions`
    MODIFY COLUMN `status` ENUM('draft','pending_budget_check','budget_rejected','pending_finance_head','revision_requested','approved','rejected','converted_to_po','cancelled') NOT NULL DEFAULT 'pending_budget_check';

ALTER TABLE `budget_transactions`
    MODIFY COLUMN `reference_type` ENUM('requisition','purchase_order','invoice','manual') NOT NULL;

-- preferred_supplier_id / supplier_product_id stay NULLable -- harmless
-- (the restored code always sets them immediately at creation time again),
-- and reverting them back to NOT NULL risks failing on any leftover row.
