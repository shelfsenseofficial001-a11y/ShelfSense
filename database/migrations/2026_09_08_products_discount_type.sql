-- Lets a product discount be a percentage off or a flat peso amount off,
-- defaulting to percentage. Renames discount_percent -> discount_value
-- since the value is no longer always a percent.
ALTER TABLE products
  CHANGE COLUMN discount_percent discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent' AFTER discount_value;
