-- Per-cutoff schedule changes. The standing `schedules` table (one row per
-- user per day_of_week) stays exactly as-is -- the recurring baseline,
-- still synced from the employee's contract. This table only holds actual
-- deviations from that baseline for one specific cutoff period (e.g. an
-- employee swapping their Monday rest day for a specific half-month), so
-- most users/periods have zero rows here. The effective schedule for a
-- given (user, period, day) is this override if one exists, else the
-- baseline row. Period keys use the same "YYYY-MM-H1"/"YYYY-MM-H2" format
-- as Budget's App\Core\CutoffPeriod, reused rather than duplicated.
CREATE TABLE IF NOT EXISTS schedule_overrides (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    period_key VARCHAR(10) NOT NULL,
    day_of_week ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    time_in TIME DEFAULT NULL,
    time_out TIME DEFAULT NULL,
    is_rest_day TINYINT(1) NOT NULL DEFAULT 0,
    reason VARCHAR(255) DEFAULT NULL,
    changed_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_period_day (user_id, period_key, day_of_week),
    KEY idx_period_key (period_key),
    CONSTRAINT schedule_overrides_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT schedule_overrides_ibfk_2 FOREIGN KEY (changed_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
