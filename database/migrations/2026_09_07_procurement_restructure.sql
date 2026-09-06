-- Procurement restructure: Store Manager now picks the supplier directly
-- (one store product -> many suppliers, filtered by which suppliers carry
-- every selected product) and creates the Purchase Order immediately with
-- exact, on-file prices. Finance Staff budget-checks the PO (moved off the
-- requisition), Finance Head gets an explicit PO-approval gate (previously
-- implicit inside requisition approval). Payment is also reordered to
-- happen only after delivery + a reconciled 3-way match, instead of right
-- after PO confirmation.

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1. supplier_products: link to the internal catalog + track available qty,
--    enabling "which suppliers carry all of these products" lookups.
-- ---------------------------------------------------------------------------
ALTER TABLE `supplier_products`
  ADD COLUMN `store_product_id` int(11) DEFAULT NULL AFTER `supplier_id`,
  ADD COLUMN `quantity` int(11) NOT NULL DEFAULT 0 AFTER `price`;

ALTER TABLE `supplier_products`
  ADD KEY `idx_sp_store_product` (`store_product_id`),
  ADD CONSTRAINT `fk_sp_store_product` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`);

-- ---------------------------------------------------------------------------
-- 2. purchase_orders: budget check + explicit Finance Head approval now
--    happen on the PO itself (it's created directly by Store Manager,
--    instead of being auto-derived later from an approved requisition).
-- ---------------------------------------------------------------------------
ALTER TABLE `purchase_orders`
  MODIFY `status` enum(
    'pending_budget_check','budget_rejected','pending_fh_approval',
    'pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed',
    'confirmed','pending_payment','paid','shipped','partially_received','received','cancelled','closed'
  ) NOT NULL DEFAULT 'pending_budget_check';

ALTER TABLE `purchase_orders`
  ADD COLUMN `approved_by` int(11) DEFAULT NULL AFTER `created_by`,
  ADD COLUMN `approved_at` datetime DEFAULT NULL AFTER `approved_by`,
  ADD COLUMN `rejection_reason` text DEFAULT NULL AFTER `approved_at`,
  ADD COLUMN `budget_rejected_reason` text DEFAULT NULL AFTER `rejection_reason`;

ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

COMMIT;
