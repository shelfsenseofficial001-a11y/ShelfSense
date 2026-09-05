-- Reorders procurement payment ahead of delivery: Finance Staff requests
-- payment on a supplier-confirmed PO, Finance Head approves it (posting the
-- real budget expense + releasing the reservation), the supplier is only
-- notified to ship after that payment is approved, and Goods Receipt /
-- Invoice + 3-way match become post-payment delivery/reconciliation records
-- rather than payment gates. Also adds a generic per-PO history timeline.

START TRANSACTION;

ALTER TABLE `purchase_orders`
  MODIFY `status` enum(
    'pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed',
    'confirmed','pending_payment','paid','shipped','partially_received','received','cancelled','closed'
  ) NOT NULL DEFAULT 'pending_dispatch';

CREATE TABLE `po_payment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ppr_po` (`po_id`),
  KEY `idx_ppr_status` (`status`),
  CONSTRAINT `fk_ppr_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_ppr_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_ppr_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Real money movement now happens at PO-payment-request approval, not at
-- invoice/batch time -- payments.invoice_id becomes optional (a payment can
-- exist before any invoice does), and payments carry the PO + originating
-- payment request directly. Split into single-clause statements: MySQL
-- refuses to drop `unique_invoice_payment` in the same ALTER that still
-- needs it for `fk_pay_invoice` (errno 150), so the FK has to be dropped
-- and re-added around the index change explicitly.
ALTER TABLE `payments` DROP FOREIGN KEY `fk_pay_invoice`;
ALTER TABLE `payments` DROP INDEX `unique_invoice_payment`;
ALTER TABLE `payments` MODIFY `invoice_id` int(11) DEFAULT NULL;
ALTER TABLE `payments` ADD KEY `idx_pay_invoice` (`invoice_id`);
ALTER TABLE `payments` ADD CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);
ALTER TABLE `payments` ADD COLUMN `po_id` int(11) DEFAULT NULL AFTER `invoice_id`;
ALTER TABLE `payments` ADD COLUMN `payment_request_id` int(11) DEFAULT NULL AFTER `po_id`;
ALTER TABLE `payments` ADD KEY `idx_pay_po` (`po_id`);
ALTER TABLE `payments` ADD KEY `idx_pay_payment_request` (`payment_request_id`);
ALTER TABLE `payments` ADD CONSTRAINT `fk_pay_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`);
ALTER TABLE `payments` ADD CONSTRAINT `fk_pay_payment_request` FOREIGN KEY (`payment_request_id`) REFERENCES `po_payment_requests` (`id`);

-- Invoice match_status keeps its meaning (3-way match result) but is now a
-- reconciliation record only -- 'paid' no longer applies to an invoice
-- (nothing is disbursed against it anymore), replaced by 'reconciled'.
ALTER TABLE `invoices`
  MODIFY `match_status` enum('pending','matched','price_hold','quantity_hold','approved','reconciled','rejected') NOT NULL DEFAULT 'pending';

-- Generic timestamped history timeline, one row per PO lifecycle event.
-- `visibility` lets the shared PO-detail endpoint filter entries the same
-- way it already filters goods_receipts (hidden from Supplier) and invoices
-- (hidden from Store Manager), without special-casing event types there.
CREATE TABLE `po_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `visibility` enum('all','not_supplier','not_store_manager') NOT NULL DEFAULT 'all',
  `actor_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_po_events_po` (`po_id`, `created_at`),
  CONSTRAINT `fk_po_events_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_po_events_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
