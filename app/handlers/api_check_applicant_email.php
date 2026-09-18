<?php
// app/handlers/api_check_applicant_email.php
// Lightweight duplicate-email lookup for the public Apply form, so a
// taken email is caught right after the applicant types it instead of
// only at final submission (after they've filled every other step).

require_once __DIR__ . '/../core/Database.php';

use App\Core\Database;

header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => true, 'data' => ['exists' => false]]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM applicants WHERE email = ?");
    $stmt->execute([$email]);
    echo json_encode(['success' => true, 'data' => ['exists' => (bool)$stmt->fetch()]]);
} catch (Exception $e) {
    error_log('api_check_applicant_email.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error checking email']);
}
