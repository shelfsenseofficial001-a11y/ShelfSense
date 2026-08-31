-- Register budget flow: Store Manager allocates a physical cash float to a
-- register, a cashier works against it, and must cash out (pulling the float
-- + cash sales together) before another allocation can be made. Online
-- payments (card/gcash/paymaya/other) are tracked separately per session for
-- visibility only -- they settle to the payment processor, not the drawer,
-- and are not part of this cash-out calc (per business rule: only the
-- payment_method='cash' total is pulled from the drawer).
--
-- One register per store manager for now (single-branch store today; if
-- multi-branch/multi-register support is needed later, drop the UNIQUE on
-- store_manager_id and add an explicit branch/store entity).

CREATE TABLE `registers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_manager_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT 'Main Register',
  `status` enum('closed','open') NOT NULL DEFAULT 'closed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_manager_id` (`store_manager_id`),
  CONSTRAINT `registers_ibfk_1` FOREIGN KEY (`store_manager_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `register_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `register_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `allocated_by` int(11) NOT NULL,
  `initial_budget` decimal(10,2) NOT NULL,
  `status` enum('active','cashed_out') NOT NULL DEFAULT 'active',
  `cash_sales` decimal(10,2) DEFAULT NULL,
  `online_sales` decimal(10,2) DEFAULT NULL,
  `total_pulled` decimal(10,2) DEFAULT NULL,
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cashed_out_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `register_id` (`register_id`),
  KEY `cashier_id` (`cashier_id`),
  KEY `allocated_by` (`allocated_by`),
  CONSTRAINT `register_allocations_ibfk_1` FOREIGN KEY (`register_id`) REFERENCES `registers` (`id`),
  CONSTRAINT `register_allocations_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `register_allocations_ibfk_3` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Scopes each order to the budget session it was rung up under, so cash-out
-- totals only ever cover orders from the current float, never a prior shift's.
ALTER TABLE `orders`
  ADD COLUMN `register_allocation_id` int(11) NULL DEFAULT NULL AFTER `cashier_id`,
  ADD KEY `register_allocation_id` (`register_allocation_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`register_allocation_id`) REFERENCES `register_allocations` (`id`);
