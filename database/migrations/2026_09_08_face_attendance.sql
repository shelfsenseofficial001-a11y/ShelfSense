-- Face-recognition attendance: QR handoff from POS to a phone (or the
-- register's own camera as fallback) which captures a face, extracts a
-- descriptor client-side, and has the server verify it against the
-- employee's enrolled descriptors before auto-recording attendance.

CREATE TABLE IF NOT EXISTS face_enrollments (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    descriptors JSON NOT NULL COMMENT 'Array of 128-float face descriptor arrays, one per captured angle',
    consent_at DATETIME NOT NULL COMMENT 'When the employee accepted the biometric-data notice for this enrollment',
    enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_face_enrollment_user (user_id),
    CONSTRAINT face_enrollments_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS attendance_qr_sessions (
    id INT NOT NULL AUTO_INCREMENT,
    token VARCHAR(64) NOT NULL,
    register_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending','confirmed','failed','expired') NOT NULL DEFAULT 'pending',
    fail_reason VARCHAR(255) DEFAULT NULL,
    match_distance DECIMAL(6,4) DEFAULT NULL,
    captured_photo VARCHAR(255) DEFAULT NULL,
    attendance_action ENUM('time_in','time_out') DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_qr_token (token),
    KEY idx_qr_register (register_id),
    CONSTRAINT attendance_qr_sessions_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE attendance
    ADD COLUMN verification_method ENUM('manual','face') NOT NULL DEFAULT 'manual' AFTER verified_by,
    ADD COLUMN verification_photo VARCHAR(255) DEFAULT NULL AFTER verification_method,
    ADD COLUMN match_distance DECIMAL(6,4) DEFAULT NULL AFTER verification_photo;
