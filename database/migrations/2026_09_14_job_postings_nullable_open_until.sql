-- Closing Date (Timeline) is no longer required to save a job posting as a
-- draft -- only required before it can be submitted for approval (see
-- submit_job_posting.php). NOT NULL + this DB's strict sql_mode
-- (NO_ZERO_DATE) would otherwise reject an empty string outright the
-- moment a draft is saved with no closing date picked yet.

ALTER TABLE `job_postings`
    MODIFY COLUMN `open_until` DATE NULL DEFAULT NULL;
