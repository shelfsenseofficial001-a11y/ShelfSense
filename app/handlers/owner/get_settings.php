<?php
// app/handlers/owner/get_settings.php

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

try {
    Response::success([
        'test_mode' => Settings::isTestMode()
    ], 'Settings fetched');
} catch (Exception $e) {
    error_log('get_settings.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
