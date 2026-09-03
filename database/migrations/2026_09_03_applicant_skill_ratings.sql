-- Stores an applicant's self-rated 1-5 Likert answers to the position-specific
-- skills questionnaire shown on the public Apply page (see SKILL_QUESTIONNAIRES
-- in app/helpers/functions.php for the question sets and jobPostingToQuestionnaireKey()
-- for how a job posting picks one). One row per skill per applicant, so HR's
-- applicant detail view can chart a candidate's self-assessed profile
-- (e.g. a radar chart) skill-by-skill.
--
-- skill_key is a stable identifier (not the display label) so the question
-- wording can be edited later without orphaning historical answers.

CREATE TABLE IF NOT EXISTS `applicant_skill_ratings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `applicant_id` INT NOT NULL,
    `skill_key` VARCHAR(100) NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_applicant_skill` (`applicant_id`, `skill_key`),
    CONSTRAINT `fk_skill_rating_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
