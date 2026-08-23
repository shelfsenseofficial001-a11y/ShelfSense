<?php
// app/handlers/api_apply.php

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
// Mailer is disabled for now

use App\Core\Database;

header('Content-Type: application/json');

// ============================================
// GET POST DATA
// ============================================

$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$middleName = $_POST['middle_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$targetRole = $_POST['target_role'] ?? '';

// ✅ ROLE MAPPING - Convert lowercase to display names
$roleMap = [
    'cashier' => 'Cashier',
    'hr_staff' => 'HR Staff',
    'finance_staff' => 'Finance Staff',
    'hr_head' => 'Head HR',
    'finance_head' => 'Head Finance'
];

// If the role is in lowercase format, convert it
$targetRole = $roleMap[$targetRole] ?? $targetRole;

// ============================================
// VALIDATION
// ============================================

if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($targetRole)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if (!preg_match('/^[0-9]{10,12}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number. Must be 10-12 digits.']);
    exit;
}

// ============================================
// FILE UPLOAD
// ============================================

if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please upload a resume']);
    exit;
}

// ✅ Create upload directory if it doesn't exist
$uploadDir = __DIR__ . '/../../public/uploads/resumes/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// ✅ Upload file using helper
$uploadResult = uploadFile($_FILES['resume'], $uploadDir, ['pdf', 'doc', 'docx']);

if (!$uploadResult['success']) {
    echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
    exit;
}

// ============================================
// SAVE TO DATABASE
// ============================================

try {
    $db = Database::getInstance()->getConnection();

    // ✅ Check if email already exists
    $stmt = $db->prepare("SELECT id FROM applicants WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An application with this email already exists.']);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO applicants (first_name, last_name, middle_name, email, phone, target_role, resume_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $firstName,
        $lastName,
        $middleName,
        $email,
        $phone,
        $targetRole,
        $uploadResult['path']
    ]);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to save application to database']);
        exit;
    }

    $applicantId = $db->lastInsertId();

    // ============================================
    // SEND CONFIRMATION EMAIL (DISABLED FOR NOW)
    // ============================================
    error_log("📧 Application received: $firstName $lastName <$email> for $targetRole");

    // ============================================
    // NOTIFY HR
    // ============================================
    $stmt = $db->prepare("SELECT user_id FROM users WHERE role IN ('hr_head', 'hr_staff') AND is_active = 1");
    $stmt->execute();
    $hrUsers = $stmt->fetchAll();

    foreach ($hrUsers as $hr) {
        createNotification(
            $hr['user_id'],
            'new_application',
            "New application from {$firstName} {$lastName} for {$targetRole} position",
            "?page=hr_applicants"
        );
    }

    // ============================================
    // SUCCESS RESPONSE
    // ============================================
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! Our HR team will review your application.'
    ]);

} catch (Exception $e) {
    error_log('api_apply.php error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your application. Please try again.'
    ]);
}