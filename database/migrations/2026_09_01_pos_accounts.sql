-- POS accounts: a POS ID + 4-digit PIN pair that authenticates into a
-- register/terminal directly (not into a specific employee's account). The
-- POS account IS the register -- credentials live on the same row rather
-- than a parallel table, so the existing register budget/cash-float flow
-- keeps working underneath it unchanged.
ALTER TABLE `registers`
  ADD COLUMN `pos_id` VARCHAR(20) NULL AFTER `name`,
  ADD COLUMN `pin_hash` VARCHAR(255) NULL AFTER `pos_id`,
  ADD COLUMN `pos_created_by` INT(11) NULL AFTER `pin_hash`,
  ADD COLUMN `pos_created_at` TIMESTAMP NULL DEFAULT NULL AFTER `pos_created_by`,
  ADD UNIQUE KEY `pos_id` (`pos_id`),
  ADD CONSTRAINT `registers_pos_created_by_fk` FOREIGN KEY (`pos_created_by`) REFERENCES `users` (`user_id`);

-- Budget floats now belong to the register/POS account itself, not to a
-- specific cashier picked at allocation time (that pick now happens later,
-- at POS login, purely for order attribution) -- so a float can be
-- allocated with no cashier assigned yet.
ALTER TABLE `register_allocations`
  MODIFY COLUMN `cashier_id` INT(11) NULL;
