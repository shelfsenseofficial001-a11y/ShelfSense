-- Renames revenue_splits.month_year to budget_period (aligning with the
-- budget_period naming used elsewhere after 2026_09_04_procurement_rebuild.sql)
-- and seeds the revenue_split_rules department percentages.
--
-- This is the still-valid portion of 2026_09_01_budget_cutoff_periods.sql;
-- that migration's other statements (ALTERs on budgets, budget_adjustments,
-- store_requisitions) are superseded by 2026_09_04_procurement_rebuild.sql
-- and must NOT be replayed against a database already on the rebuilt schema.

ALTER TABLE `revenue_splits`
  CHANGE COLUMN `month_year` `budget_period` VARCHAR(10) NOT NULL;

INSERT INTO `revenue_split_rules` (`department`, `percentage`, `is_remainder`) VALUES
  ('hr', 60.00, 0),
  ('store', 5.00, 0),
  ('finance', 10.00, 0),
  ('general', 0.00, 1)
ON DUPLICATE KEY UPDATE
  `percentage` = VALUES(`percentage`),
  `is_remainder` = VALUES(`is_remainder`);
