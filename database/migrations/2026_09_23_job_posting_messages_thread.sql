-- Moderation-message thread for job postings: HR Head can now leave more
-- than one message over time (like a running conversation), not just a
-- single approval_message/rejection_reason per decision. Existing single
-- messages are backfilled as the first entries in each posting's thread.

CREATE TABLE IF NOT EXISTS `job_posting_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_posting_id` INT NOT NULL,
    `author_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `action` VARCHAR(20) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_jpm_posting` (`job_posting_id`),
    CONSTRAINT `fk_jpm_posting` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jpm_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
);

INSERT INTO job_posting_messages (job_posting_id, author_id, message, action, created_at)
SELECT id, approved_by, approval_message, 'approved', approved_at
FROM job_postings
WHERE approval_message IS NOT NULL AND approval_message != '' AND approved_by IS NOT NULL;

INSERT INTO job_posting_messages (job_posting_id, author_id, message, action, created_at)
SELECT id, rejected_by, rejection_reason, 'rejected', rejected_at
FROM job_postings
WHERE rejection_reason IS NOT NULL AND rejection_reason != '' AND rejected_by IS NOT NULL;
