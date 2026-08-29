-- Adds per-user, per-dashboard widget ordering for the drag-to-reorder
-- "edit mode" feature. Purely additive: one new table, no changes to
-- existing schema.
CREATE TABLE IF NOT EXISTS user_dashboard_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dashboard_key VARCHAR(50) NOT NULL,
    widget_order TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_dashboard (user_id, dashboard_key),
    CONSTRAINT fk_user_dashboard_layouts_user
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
