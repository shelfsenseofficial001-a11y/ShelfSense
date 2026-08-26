<?php
// app/handlers/api_apply.php

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../models/JobPosting.php';

use App\Core\Database;
use App\Core\Mailer;
use App\Models\JobPosting;

header('Content-Type: application/json');

// ============================================
// GET POST DATA
// ============================================

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$jobPostingId = isset($_POST['job_posting_id']) ? intval($_POST['job_posting_id']) : 0;

// ============================================
// VALIDATION
// ============================================

if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || $jobPostingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled, including a position.']);
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
// AUTHORITATIVE JOB-POSTING RE-CHECK
// ============================================
// Never trust the job title/department/salary/slots the browser last
// rendered -- another applicant may have been hired (consuming the last
// slot) or HR may have closed/archived the posting since the page loaded.
// This re-runs the exact same eligibility rule the public listing used.

$jobPostingModel = new JobPosting();
$job = $jobPostingModel->getEligibleJobById($jobPostingId);

if (!$job) {
    echo json_encode(['success' => false, 'message' => 'This position is no longer accepting applications. Please choose another position.']);
    exit;
}

// target_role is derived from the job posting's controlled department value,
// never taken from the browser, so it always matches a real role the rest
// of the system (trainer lookup, trainee creation, contract activation) knows.
$targetRole = jobPostingDepartmentToTargetRole($job['department']);

// ============================================
// DUPLICATE-EMAIL CHECK
// ============================================
// Checked before the resume is even uploaded, so a rejected duplicate
// submission never leaves an orphaned file on disk.

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM applicants WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An application with this email already exists.']);
        exit;
    }
} catch (Exception $e) {
    error_log('api_apply.php: duplicate-email check failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your application. Please try again.']);
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

// ✅ Upload file using helper (validates extension, real MIME type, size,
// and writes to a fully random filename -- see app/helpers/functions.php)
$uploadResult = uploadFile($_FILES['resume'], $uploadDir, ['pdf', 'doc', 'docx']);

if (!$uploadResult['success']) {
    echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
    exit;
}

// Stored relative to public/ so it matches how HR's resume_url is built
// (get_applicant.php / get_applicants.php prepend '/ShelfSense/public/').
$resumeRelativePath = 'uploads/resumes/' . $uploadResult['filename'];

// ============================================
// SAVE TO DATABASE
// ============================================

try {
    $db = Database::getInstance()->getConnection();

    // Re-check for a duplicate email one more time immediately before the
    // insert -- guards the narrow race between two concurrent submissions
    // with the same email that both passed the earlier check.
    $stmt = $db->prepare("SELECT id FROM applicants WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An application with this email already exists.']);
        exit;
    }

    // Re-check slot availability one more time immediately before the
    // insert, closest possible to the write, to shrink the race window
    // further without needing a row lock for what is a rare event (a hire
    // completing at the exact moment someone else submits).
    $job = $jobPostingModel->getEligibleJobById($jobPostingId);
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'This position is no longer accepting applications. Please choose another position.']);
        exit;
    }

    $stmt = $db->prepare("
        INSERT INTO applicants (first_name, last_name, middle_name, email, phone, target_role, job_posting_id, resume_path)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $firstName,
        $lastName,
        $middleName,
        $email,
        $phone,
        $targetRole,
        $jobPostingId,
        $resumeRelativePath
    ]);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to save application to database']);
        exit;
    }

    $applicantId = $db->lastInsertId();

    // ============================================
    // SEND CONFIRMATION EMAIL
    // ============================================
    // A fresh INSERT above guarantees this runs at most once per application
    // (the duplicate-email check above already rejects a resubmission), so a
    // page refresh cannot trigger a second confirmation email.
    try {
        $mailer = new Mailer();
        $mailer->sendApplicantStatusUpdate(
            ['email' => $email, 'first_name' => $firstName, 'last_name' => $lastName],
            'application_received',
            "Thank you for applying for the {$job['title']} position at ShelfSense. Our HR team will review your application and contact you regarding next steps."
        );
    } catch (Exception $e) {
        error_log('api_apply.php: confirmation email failed: ' . $e->getMessage());
    }

    logRecruitmentEvent('applicant', $applicantId, 'application_received', [
        'new_status' => 'pending',
        'notes' => 'source: public application form (job_posting_id ' . $jobPostingId . ')'
    ]);

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
            "New application from {$firstName} {$lastName} for {$job['title']} position",
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
