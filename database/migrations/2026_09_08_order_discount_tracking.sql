-- Records the discount actually applied at sale time so receipts and
-- reporting stay accurate even if a product's discount changes later.
ALTER TABLE order_items
  ADD COLUMN original_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price;

ALTER TABLE orders
  ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER subtotal;
