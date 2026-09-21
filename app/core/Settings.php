<?php
namespace App\Core;

// Live, DB-backed key/value settings (system_settings table) -- unlike
// app/config/features.php, these can be flipped from the Owner Settings
// page without touching a file. Cached per-request so a page that checks
// the same setting multiple times doesn't re-query.
class Settings
{
    private static $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        $value = $row ? $row['setting_value'] : $default;
        self::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, string $value, ?int $updatedBy = null): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)
        ");
        $stmt->execute([$key, $value, $updatedBy]);
        self::$cache[$key] = $value;
    }

    public static function isTestMode(): bool
    {
        return self::get('test_mode', '0') === '1';
    }
}
