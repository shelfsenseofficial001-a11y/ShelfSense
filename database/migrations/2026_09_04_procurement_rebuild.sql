-- Procurement System Reconstruction
-- Rebuilds requisition -> PO -> goods receipt -> invoice (3-way match) -> payment
-- as normalized tables with a budget ledger, replacing the store_requisitions-based flow.
-- Old tables are renamed (not dropped) to legacy_* so existing data is preserved.

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1. Retire old tables (rename, preserve data)
-- ---------------------------------------------------------------------------
RENAME TABLE `store_requisitions` TO `legacy_store_requisitions`;
RENAME TABLE `store_requisition_items` TO `legacy_store_requisition_items`;
RENAME TABLE `supplier_invoices` TO `legacy_supplier_invoices`;
RENAME TABLE `payment_requests` TO `legacy_payment_requests`;
RENAME TABLE `payments` TO `legacy_payments`;
RENAME TABLE `goods_receipts` TO `legacy_goods_receipts`;
RENAME TABLE `goods_receipt_items` TO `legacy_goods_receipt_items`;
RENAME TABLE `purchase_orders` TO `legacy_purchase_orders`;
RENAME TABLE `purchase_order_items` TO `legacy_purchase_order_items`;
RENAME TABLE `budgets` TO `legacy_budgets`;
RENAME TABLE `budget_adjustments` TO `legacy_budget_adjustments`;

-- ---------------------------------------------------------------------------
-- 2. Reference / config
-- ---------------------------------------------------------------------------
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_department_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `departments` (`name`, `code`, `is_active`)
SELECT DISTINCT `department`, UPPER(`department`), 1
FROM `legacy_store_requisitions`
WHERE `department` IS NOT NULL AND `department` <> '';

INSERT INTO `departments` (`name`, `code`, `is_active`)
SELECT 'store', 'STORE', 1
WHERE NOT EXISTS (SELECT 1 FROM `departments` WHERE `name` = 'store');

CREATE TABLE `variance_tolerance_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price_tolerance_percent` decimal(5,2) NOT NULL DEFAULT 2.00,
  `price_tolerance_amount` decimal(10,2) NOT NULL DEFAULT 50.00,
  `quantity_tolerance_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_variance_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `variance_tolerance_settings` (`price_tolerance_percent`, `price_tolerance_amount`, `quantity_tolerance_percent`)
VALUES (2.00, 50.00, 0.00);

-- ---------------------------------------------------------------------------
-- 3. Budget ledger
-- ---------------------------------------------------------------------------
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `period_key` varchar(10) NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_department_period` (`department_id`, `period_key`),
  KEY `idx_period_key` (`period_key`),
  CONSTRAINT `fk_budgets_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `budget_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `period_key` varchar(10) NOT NULL,
  `type` enum('allocation','reservation','release','expense','adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reference_type` enum('requisition','purchase_order','invoice','manual') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bt_department_period` (`department_id`, `period_key`),
  KEY `idx_bt_type` (`type`),
  KEY `idx_bt_reference` (`reference_type`, `reference_id`),
  KEY `idx_bt_created_by` (`created_by`),
  CONSTRAINT `fk_bt_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `fk_bt_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 4. Procurement core
-- ---------------------------------------------------------------------------
CREATE TABLE `requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_number` varchar(20) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `preferred_supplier_id` int(11) NOT NULL,
  `period_key` varchar(10) NOT NULL,
  `status` enum('draft','pending_budget_check','budget_rejected','pending_finance_head','approved','rejected','converted_to_po','cancelled') NOT NULL DEFAULT 'pending_budget_check',
  `order_date` date NOT NULL,
  `needed_by_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_requisition_number` (`requisition_number`),
  KEY `idx_req_status` (`status`),
  KEY `idx_req_department_period` (`department_id`, `period_key`),
  CONSTRAINT `fk_req_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_req_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `fk_req_supplier` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `requisition_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `store_product_id` int(11) NOT NULL,
  `supplier_product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `estimated_unit_price` decimal(10,2) NOT NULL,
  `estimated_total` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ri_requisition` (`requisition_id`),
  KEY `idx_ri_store_product` (`store_product_id`),
  KEY `idx_ri_supplier_product` (`supplier_product_id`),
  CONSTRAINT `fk_ri_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_store_product` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_ri_supplier_product` FOREIGN KEY (`supplier_product_id`) REFERENCES `supplier_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(20) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `status` enum('pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed','confirmed','partially_received','received','cancelled','closed') NOT NULL DEFAULT 'pending_dispatch',
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `terms` varchar(50) DEFAULT 'Net 30',
  `dispatched_at` datetime DEFAULT NULL,
  `dispatched_via` enum('email','portal','both') DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_po_number` (`po_number`),
  KEY `idx_po_status` (`status`),
  KEY `idx_po_supplier` (`supplier_id`),
  CONSTRAINT `fk_po_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`),
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `store_product_id` int(11) NOT NULL,
  `supplier_product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `received_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_poi_po` (`po_id`),
  KEY `idx_poi_store_product` (`store_product_id`),
  KEY `idx_poi_supplier_product` (`supplier_product_id`),
  CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poi_store_product` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_poi_supplier_product` FOREIGN KEY (`supplier_product_id`) REFERENCES `supplier_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `po_counter_proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `proposed_by` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pcp_po` (`po_id`),
  CONSTRAINT `fk_pcp_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcp_proposed_by` FOREIGN KEY (`proposed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_pcp_responded_by` FOREIGN KEY (`responded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `po_counter_proposal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `proposed_quantity` int(11) DEFAULT NULL,
  `proposed_unit_price` decimal(10,2) DEFAULT NULL,
  `proposed_delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pcpi_proposal` (`proposal_id`),
  KEY `idx_pcpi_po_item` (`po_item_id`),
  CONSTRAINT `fk_pcpi_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `po_counter_proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcpi_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `goods_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `status` enum('completed') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gr_po` (`po_id`),
  CONSTRAINT `fk_gr_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_gr_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `condition` enum('good','damaged','missing') NOT NULL DEFAULT 'good',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gri_receipt` (`goods_receipt_id`),
  KEY `idx_gri_po_item` (`po_item_id`),
  CONSTRAINT `fk_gri_receipt` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gri_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `match_status` enum('pending','matched','price_hold','quantity_hold','approved','paid','rejected') NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_invoice_number` (`invoice_number`),
  KEY `idx_inv_po` (`po_id`),
  KEY `idx_inv_supplier` (`supplier_id`),
  KEY `idx_inv_match_status` (`match_status`),
  CONSTRAINT `fk_inv_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_inv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `billed_quantity` int(11) NOT NULL,
  `billed_unit_price` decimal(10,2) NOT NULL,
  `billed_total` decimal(10,2) NOT NULL,
  `quantity_variance` int(11) NOT NULL DEFAULT 0,
  `price_variance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `variance_flag` enum('ok','price_variance','quantity_variance') NOT NULL DEFAULT 'ok',
  PRIMARY KEY (`id`),
  KEY `idx_ii_invoice` (`invoice_id`),
  KEY `idx_ii_po_item` (`po_item_id`),
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ii_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 5. Payment
-- ---------------------------------------------------------------------------
CREATE TABLE `payment_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_number` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('pending_approval','approved','rejected','disbursed') NOT NULL DEFAULT 'pending_approval',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_batch_number` (`batch_number`),
  KEY `idx_pb_status` (`status`),
  CONSTRAINT `fk_pb_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_pb_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `payment_batch_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_batch_invoice` (`batch_id`, `invoice_id`),
  KEY `idx_pbi_invoice` (`invoice_id`),
  CONSTRAINT `fk_pbi_batch` FOREIGN KEY (`batch_id`) REFERENCES `payment_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pbi_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `payment_batch_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('bank_transfer','check','cash','other') NOT NULL DEFAULT 'bank_transfer',
  `reference_number` varchar(50) DEFAULT NULL,
  `paid_by` int(11) NOT NULL,
  `paid_at` datetime NOT NULL,
  `remittance_sent` tinyint(4) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_invoice_payment` (`invoice_id`),
  KEY `idx_pay_batch` (`payment_batch_id`),
  CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `fk_pay_batch` FOREIGN KEY (`payment_batch_id`) REFERENCES `payment_batches` (`id`),
  CONSTRAINT `fk_pay_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
