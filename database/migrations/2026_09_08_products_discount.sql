-- Adds a percent-off discount field to products, applied throughout POS.
ALTER TABLE products
  ADD COLUMN discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER cost;
