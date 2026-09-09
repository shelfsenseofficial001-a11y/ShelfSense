-- Attendance now happens directly at the register's own camera -- no more
-- QR handoff to a phone, so the session table backing that flow is dead.
DROP TABLE IF EXISTS attendance_qr_sessions;
