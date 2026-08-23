<?php
// app/handlers/api_login.php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

use App\Core\Auth;
use App\Core\Response;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$result = Auth::login($email, $password);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

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