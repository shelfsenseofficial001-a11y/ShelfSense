<?php
// public/debug_api.php

// Load environment
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';

// Simple autoloader for models
spl_autoload_register(function ($class) {
    $prefix = 'App\\Models\\';
    $base_dir = __DIR__ . '/../app/models/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Auth;
use App\Core\Response;
use App\Models\Applicant;

echo "<h2>Debug: Testing API</h2>";

// Test database connection
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test getting applicants
try {
    $applicantModel = new Applicant();
    $result = $applicantModel->getAll(1, 15, ['status' => 'all']);
    
    echo "<p>✅ Found " . count($result['applicants']) . " applicants</p>";
    echo "<p>Total records: " . $result['pagination']['totalRecords'] . "</p>";
    
    echo "<h3>Sample Applicants:</h3>";
    echo "<pre>";
    print_r(array_slice($result['applicants'], 0, 3));
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Error getting applicants: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
}