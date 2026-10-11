-- One upsertable row per trainee per calendar day -- the per-day entry a
-- trainer fills in during the currently-open week (see
-- app/handlers/hr/trainee_reports/save_daily_log.php). Not locked
-- individually; the whole week locks at once when its trainee_reports row
-- is created (submit_report.php), which is also what builds that row's
-- report_content by concatenating these in date order.

CREATE TABLE IF NOT EXISTS `trainee_daily_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trainee_id` INT NOT NULL,
    `week_number` INT NOT NULL,
    `log_date` DATE NOT NULL,
    `day_name` VARCHAR(10) NOT NULL,
    `content` TEXT NOT NULL,
    `trainer_id` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_trainee_date` (`trainee_id`, `log_date`),
    KEY `idx_tdl_trainee_week` (`trainee_id`, `week_number`),
    CONSTRAINT `fk_tdl_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tdl_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
