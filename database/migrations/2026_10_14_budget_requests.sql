-- Budget allocation request/approval flow: a department head (Store
-- Manager for 'store', HR Head for 'hr') requests a budget amount with a
-- reason, Finance Head approves (applies it via the existing
-- Budget::adjustAllocation(), same as a direct Set Allocation) or rejects
-- with a reason. Finance Head's existing direct "Set Allocation" action is
-- unchanged -- this is an additional path for non-Finance roles to propose
-- a number instead of Finance guessing one unprompted.

CREATE TABLE IF NOT EXISTS `budget_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT NOT NULL,
    `period_key` VARCHAR(10) NOT NULL,
    `requested_amount` DECIMAL(12,2) NOT NULL,
    `reason` TEXT NOT NULL,
    `requested_by` INT NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by` INT NULL,
    `reviewed_at` TIMESTAMP NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_budget_req_department` (`department_id`),
    KEY `idx_budget_req_status` (`status`),
    CONSTRAINT `fk_budget_req_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
    CONSTRAINT `fk_budget_req_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
    CONSTRAINT `fk_budget_req_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
