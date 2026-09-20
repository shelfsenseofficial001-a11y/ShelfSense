-- System-wide key/value settings, editable live from the Owner Settings
-- page instead of requiring a code/file change. Currently just backs the
-- Test Mode toggle (skips forced first-login password change + Face ID
-- app-wide) but is a plain key/value store so future toggles can reuse it.
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_system_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO system_settings (setting_key, setting_value)
VALUES ('test_mode', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
