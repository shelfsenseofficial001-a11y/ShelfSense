ALTER TABLE `trainee_reports`
    ADD COLUMN `hr_staff_id` INT(11) DEFAULT NULL AFTER `forwarded_at`,
    ADD COLUMN `hr_staff_notes` TEXT DEFAULT NULL AFTER `hr_staff_id`,
    ADD COLUMN `hr_staff_reviewed_at` DATETIME DEFAULT NULL AFTER `hr_staff_notes`,
    MODIFY `status` ENUM('submitted','forwarded','hr_staff_reviewed','hr_reviewed') NOT NULL DEFAULT 'submitted';

ALTER TABLE `trainee_reports`
    ADD CONSTRAINT `fk_trainee_reports_hr_staff` FOREIGN KEY (`hr_staff_id`) REFERENCES `users` (`user_id`);
