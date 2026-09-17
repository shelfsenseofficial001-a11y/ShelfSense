-- Catalog & Deals edits a product's name/description/category/price/cost/
-- image/discount WITHOUT touching the products row Inventory reads --
-- stock_quantity, reorder_level, and is_active stay exclusively
-- Inventory's concern and are never overridden here. Any field left NULL
-- here means "use the base product's value" (see Product::getAll/getById/
-- getByBarcode/search, which COALESCE these over the base row for
-- anything that sells at POS -- Catalog's price/discount are what
-- actually gets charged, Inventory's own view of the product is
-- untouched).
CREATE TABLE `catalog_overrides` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `discount_type` enum('percent','fixed') DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `catalog_overrides_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_overrides_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
