-- Switches department budgets from monthly periods ('YYYY-MM') to
-- semi-monthly cutoff periods ('YYYY-MM-H1'/'YYYY-MM-H2', see
-- App\Core\CutoffPeriod), matching the payroll cutoff halves. Every column
-- that stores this period key is widened to fit the longer key.

ALTER TABLE `budgets`
  MODIFY COLUMN `month_year` VARCHAR(10) NOT NULL;

ALTER TABLE `budget_adjustments`
  MODIFY COLUMN `month_year` VARCHAR(10) NOT NULL;

ALTER TABLE `store_requisitions`
  MODIFY COLUMN `budget_month_year` VARCHAR(10) NOT NULL;

-- Renamed for clarity now that it holds a cutoff key, not a calendar month.
ALTER TABLE `revenue_splits`
  CHANGE COLUMN `month_year` `budget_period` VARCHAR(10) NOT NULL;

-- Standardize the revenue split rules to the four named departments, with
-- General Budget as the fixed fallback that always absorbs the remainder.
-- Existing store/hr rows are updated in place; finance is newly added.
INSERT INTO `revenue_split_rules` (`department`, `percentage`, `is_remainder`) VALUES
  ('hr', 60.00, 0),
  ('store', 5.00, 0),
  ('finance', 10.00, 0),
  ('general', 0.00, 1)
ON DUPLICATE KEY UPDATE
  `percentage` = VALUES(`percentage`),
  `is_remainder` = VALUES(`is_remainder`);
