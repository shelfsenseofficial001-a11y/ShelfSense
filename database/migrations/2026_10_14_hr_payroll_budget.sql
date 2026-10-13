-- Payroll now draws against a Finance-set 'hr' department budget, reserved
-- at Finance Head's verify step (mirrors the Procurement requisition
-- approval pattern: exceeding available budget requires a justification).

ALTER TABLE `budget_transactions`
    MODIFY COLUMN `reference_type` ENUM('requisition','purchase_order','invoice','manual','payroll_cycle') NOT NULL;
