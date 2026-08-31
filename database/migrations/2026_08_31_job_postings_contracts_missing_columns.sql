-- Fixes schema drift: database/shelfsense.sql was never regenerated after the
-- hiring-flow overhaul (commit 4c5a0c0) added `department_group` to
-- job_postings and `contract_type` to contracts in application code.

ALTER TABLE `job_postings`
  ADD COLUMN `department_group` VARCHAR(50) NOT NULL DEFAULT 'Front Department' AFTER `title`;

ALTER TABLE `contracts`
  ADD COLUMN `contract_type` VARCHAR(20) NULL DEFAULT 'hired' AFTER `user_id`;

-- Trainee salary moved from a min/max range to a single figure agreed at
-- interview; trainee_salary_min/max are no longer referenced by app code but
-- are left in place rather than dropped, in case historical data matters.
ALTER TABLE `trainees`
  ADD COLUMN `trainee_salary` DECIMAL(10,2) NULL DEFAULT NULL AFTER `schedule_end`;

-- Missing column caused activateEmployeeFromAcceptedContract() (functions.php)
-- to throw mid-transaction on every Hired Contract acceptance, silently
-- rolling back the whole hire (role change, contract status, applicant
-- status all failed to commit).
ALTER TABLE `trainees`
  ADD COLUMN `archived_at` DATETIME NULL DEFAULT NULL AFTER `training_completed_at`;

UPDATE `job_postings` SET `department_group` = 'Human Resources Department' WHERE `department` = 'HR Staff';
UPDATE `job_postings` SET `department_group` = 'Finance Department' WHERE `department` = 'Finance Staff';
