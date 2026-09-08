-- Records whether the PWD/Senior Citizen 20% discount was applied to an
-- order, so receipts and reporting can reflect it.
ALTER TABLE orders
  ADD COLUMN pwd_senior_discount TINYINT(1) NOT NULL DEFAULT 0 AFTER discount_amount;
