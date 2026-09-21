-- New-product onboarding: a 3-party agreement (Store Manager or Owner
-- proposes -> Supplier confirms their own name/price/availability or
-- declines -> Owner gives final sign-off or rejects) before anything is
-- ever added to Inventory. Rejection at either gate is a dead end (logged,
-- not resubmittable) -- mirrors the `contracts` offer/accept/decline
-- pattern already used for HR job offers.
--
-- On Owner approval, this is the ONLY path that creates a new `products`
-- row (at zero stock) plus its linked `supplier_products` row
-- (store_product_id-linked, per the Supplier/Store labeling fix) --
-- getting actual stock in after that is a normal Requisition, same as any
-- other product.

CREATE TABLE `product_proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposed_by` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `proposed_name` varchar(100) NOT NULL,
  `proposed_barcode` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `proposed_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending_supplier','pending_owner','approved','rejected') NOT NULL DEFAULT 'pending_supplier',
  `supplier_product_name` varchar(100) DEFAULT NULL,
  `supplier_price` decimal(10,2) DEFAULT NULL,
  `supplier_quantity` int(11) DEFAULT NULL,
  `supplier_responded_by` int(11) DEFAULT NULL,
  `supplier_responded_at` timestamp NULL DEFAULT NULL,
  `supplier_decline_reason` text DEFAULT NULL,
  `owner_decided_by` int(11) DEFAULT NULL,
  `owner_decided_at` timestamp NULL DEFAULT NULL,
  `owner_reject_reason` text DEFAULT NULL,
  `resulting_product_id` int(11) DEFAULT NULL,
  `resulting_supplier_product_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proposed_by` (`proposed_by`),
  KEY `supplier_id` (`supplier_id`),
  KEY `category_id` (`category_id`),
  KEY `supplier_responded_by` (`supplier_responded_by`),
  KEY `owner_decided_by` (`owner_decided_by`),
  KEY `resulting_product_id` (`resulting_product_id`),
  KEY `status` (`status`),
  CONSTRAINT `pp_proposed_by_fk` FOREIGN KEY (`proposed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `pp_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `pp_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pp_supplier_responded_by_fk` FOREIGN KEY (`supplier_responded_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `pp_owner_decided_by_fk` FOREIGN KEY (`owner_decided_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `pp_resulting_product_fk` FOREIGN KEY (`resulting_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
