-- Finance Head: split total store revenue into department budgets per
-- semi-monthly cutoff (mirrors the 1-15/16-31 payroll cutoff pattern).
-- Two-step flow: a "draft" computation previews the split for review, and
-- "apply" writes it into the existing `budgets.allocated_budget` (additive,
-- so two cutoffs in the same month accumulate into that month's total) via
-- Budget::adjustAllocation(), which already produces a budget_adjustments
-- audit row -- no separate audit table needed here.

-- Current percentage policy per department. Exactly one row should have
-- is_remainder=1 -- that department absorbs whatever's left after the
-- explicit percentages are taken (e.g. store=5%, hr=60%, general=remainder).
-- Departments with no rule here are simply not touched by revenue splits
-- (they keep being budgeted manually via the existing Set/Adjust Budget form).
CREATE TABLE `revenue_split_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(20) NOT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_remainder` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `department` (`department`),
  CONSTRAINT `revenue_split_rules_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `revenue_split_rules` (`department`, `percentage`, `is_remainder`) VALUES
  ('store', 5.00, 0),
  ('hr', 60.00, 0),
  ('general', 0.00, 1);

-- One row per computed cutoff period. Recomputing the same period while it
-- is still 'draft' refreshes this row in place (live preview); once
-- 'applied' it is locked and immutable -- a later recompute of the same
-- dates creates a new draft row (a correction), never mutates history.
CREATE TABLE `revenue_splits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `period_label` varchar(50) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `total_revenue` decimal(12,2) NOT NULL,
  `status` enum('draft','applied') NOT NULL DEFAULT 'draft',
  `computed_by` int(11) NOT NULL,
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `applied_by` int(11) DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `period_range` (`period_start`,`period_end`),
  KEY `computed_by` (`computed_by`),
  KEY `applied_by` (`applied_by`),
  CONSTRAINT `revenue_splits_ibfk_1` FOREIGN KEY (`computed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `revenue_splits_ibfk_2` FOREIGN KEY (`applied_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Per-department breakdown for a revenue_splits row. Snapshots the
-- percentage actually used at computation time, so editing the rules later
-- never rewrites the meaning of a past split.
CREATE TABLE `revenue_split_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revenue_split_id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `revenue_split_id` (`revenue_split_id`),
  CONSTRAINT `revenue_split_shares_ibfk_1` FOREIGN KEY (`revenue_split_id`) REFERENCES `revenue_splits` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
