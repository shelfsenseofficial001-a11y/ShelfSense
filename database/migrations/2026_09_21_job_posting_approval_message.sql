-- HR Head can now leave an optional moderation message when approving a job
-- posting (mirrors the existing rejection_reason, which stays required on
-- reject). See review_job_posting.php / JobPosting::approve().

ALTER TABLE `job_postings`
    ADD COLUMN `approval_message` TEXT NULL DEFAULT NULL AFTER `rejection_reason`;
