<?php
// app/handlers/api_login.php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$identifier = $input['identifier'] ?? ($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email/employee number and password are required']);
    exit;
}

$result = Auth::login($identifier, $password);

if ($result) {
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => [
                'user_id' => $result['user_id'],
                'role' => $result['role'],
                'fullname' => $result['fullname'],
                'is_first_login' => $result['is_first_login']
            ],
            'redirect' => '?page=dashboard'
        ],
        'message' => 'Login successful'
    ]);
    exit;
}

// Hidden POS unlock: the same identifier/password fields double as a
// register's POS ID + 4-digit PIN, deliberately undocumented in the UI --
// Store Managers tell their cashiers the terminal's POS ID directly rather
// than this being discoverable on the page itself.
if (preg_match('/^\d{4}$/', $password)) {
    $register = Auth::posLogin($identifier, $password);
    if ($register) {
        echo json_encode([
            'success' => true,
            'data' => [
                'redirect' => '?page=pos_select_cashier'
            ],
            'message' => 'Register unlocked'
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
exit;