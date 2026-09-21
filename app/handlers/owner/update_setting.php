<?php
// app/handlers/owner/update_setting.php
// Deliberately allowlisted, one setting at a time -- system_settings is a
// generic key/value store but only Test Mode is exposed to the UI so far.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Settings.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\Settings;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isOwner() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Owner role required.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$key = $input['key'] ?? '';
$value = $input['value'] ?? null;

$allowedKeys = ['test_mode'];
if (!in_array($key, $allowedKeys, true)) {
    Response::error('Unknown setting.', 400);
}

if (!is_bool($value)) {
    Response::error('Invalid value.', 400);
}

try {
    Settings::set($key, $value ? '1' : '0', Auth::userId());
    Response::success(['key' => $key, 'value' => $value], 'Setting updated');
} catch (Exception $e) {
    error_log('update_setting.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
