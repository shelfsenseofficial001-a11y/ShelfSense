-- Bundle deals: multiple products sold together at a set price. Fully
-- separate from products.discount_value/discount_type (single-item
-- discounts) -- a deal is its own sellable thing, not a modifier on a
-- product, and never affects Inventory's product data.

CREATE TABLE `deals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `deals_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The products that make up a deal, and how many of each.
CREATE TABLE `deal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deal_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_deal_product` (`deal_id`, `product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `deal_items_ibfk_1` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deal_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- A bundle rung up at POS is its own receipt line (not split into its
-- component products' order_items), so it needs its own line-item table.
-- deal_id can go NULL (ON DELETE SET NULL) if the deal is later removed --
-- deal_name/price are snapshotted so past receipts stay accurate either way.
CREATE TABLE `order_deal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `deal_id` int(11) DEFAULT NULL,
  `deal_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `deal_id` (`deal_id`),
  CONSTRAINT `order_deal_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_deal_items_ibfk_2` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
