<?php
// app/handlers/api_apply.php

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../core/PsgcClient.php';
require_once __DIR__ . '/../models/JobPosting.php';

use App\Core\Database;
use App\Core\Mailer;
use App\Core\PsgcClient;
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

$birthdate = trim($_POST['birthdate'] ?? '');
$provinceCode = trim($_POST['province_code'] ?? '');
$cityCode = trim($_POST['city_municipality_code'] ?? '');
$barangayCode = trim($_POST['barangay_code'] ?? '');
// Free-text lines of the address. strip_tags() first since these are never
// meant to carry markup; htmlspecialchars() happens at render time
// (consistent with escape() elsewhere in this app), not at save time.
$houseBlockLot = trim(strip_tags($_POST['house_block_lot'] ?? ''));
$street = trim(strip_tags($_POST['street'] ?? ''));
$subdivision = trim(strip_tags($_POST['subdivision'] ?? ''));
$postalCode = trim($_POST['postal_code'] ?? '');

// Raw client-submitted ratings, e.g. {"pos_operation":"4","cash_handling":"5"}.
// Never trusted as-is -- validated below against the questionnaire the
// server itself derives for this job posting, not whatever keys the client sent.
$skillRatingsRaw = json_decode($_POST['skill_ratings'] ?? '', true);
if (!is_array($skillRatingsRaw)) {
    $skillRatingsRaw = [];
}

// ============================================
// VALIDATION
// ============================================

if (
    empty($firstName) || empty($lastName) || empty($email) || empty($phone) || $jobPostingId <= 0
    || empty($birthdate) || empty($provinceCode) || empty($cityCode) || empty($barangayCode)
    || empty($houseBlockLot) || empty($street) || empty($postalCode)
) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled, including a position and complete address.']);
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

if (!validateDate($birthdate)) {
    echo json_encode(['success' => false, 'message' => 'Invalid birthdate.']);
    exit;
}

$birthdateObj = \DateTime::createFromFormat('Y-m-d', $birthdate);
$birthdateObj->setTime(0, 0, 0);
if ($birthdateObj > new \DateTime('today')) {
    echo json_encode(['success' => false, 'message' => 'Birthdate cannot be in the future.']);
    exit;
}

if (!preg_match('/^[0-9]{4}$/', $postalCode)) {
    echo json_encode(['success' => false, 'message' => 'Postal code must be exactly 4 digits.']);
    exit;
}

// Re-derive the address's display names from the live/cached PSGC hierarchy
// using only the submitted codes -- never trust client-supplied names, and
// reject the whole submission if the codes don't form a real PSGC chain
// (guards against a tampered request or a stale dropdown from a cache that
// has since been refreshed).
try {
    $provinces = PsgcClient::getProvinces();
    $province = PsgcClient::findName($provinces, $provinceCode);
    $cities = $province ? PsgcClient::getCitiesByProvince($provinceCode) : [];
    $cityMunicipality = PsgcClient::findName($cities, $cityCode);
    $barangays = $cityMunicipality ? PsgcClient::getBarangaysByCity($cityCode) : [];
    $barangay = PsgcClient::findName($barangays, $barangayCode);
} catch (Exception $e) {
    error_log('api_apply.php: PSGC lookup failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Philippine location data is temporarily unavailable. Please try again shortly.']);
    exit;
}

if (!$province || !$cityMunicipality || !$barangay) {
    echo json_encode(['success' => false, 'message' => 'Please re-select your Province, City/Municipality, and Barangay -- the previous selection is no longer valid.']);
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
// SKILLS QUESTIONNAIRE VALIDATION
// ============================================
// The questionnaire (if any) is derived from the job posting itself, never
// from a client-supplied key -- so a tampered request can't attach answers
// from a different questionnaire, and a posting with no matching set (e.g.
// Supplier) simply requires none.

$questionnaireKey = jobPostingToQuestionnaireKey($job['department'], $job['role'] ?? null);
$questionnaireSkills = $questionnaireKey ? (SKILL_QUESTIONNAIRES[$questionnaireKey] ?? []) : [];

$skillRatings = []; // skill_key => validated 1-5 int, only for this posting's questionnaire
foreach ($questionnaireSkills as $skill) {
    $value = $skillRatingsRaw[$skill['key']] ?? null;
    if (!is_numeric($value) || (int)$value < 1 || (int)$value > 5) {
        echo json_encode(['success' => false, 'message' => 'Please rate every skill in the assessment before submitting.']);
        exit;
    }
    $skillRatings[$skill['key']] = (int)$value;
}

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
        INSERT INTO applicants (
            first_name, last_name, middle_name, email, phone, birthdate,
            province, province_code, city_municipality, city_municipality_code,
            barangay, barangay_code, house_block_lot, street, subdivision, postal_code, country,
            target_role, job_posting_id, resume_path
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Philippines', ?, ?, ?)
    ");

    $result = $stmt->execute([
        $firstName,
        $lastName,
        $middleName,
        $email,
        $phone,
        $birthdate,
        $province,
        $provinceCode,
        $cityMunicipality,
        $cityCode,
        $barangay,
        $barangayCode,
        $houseBlockLot,
        $street,
        $subdivision !== '' ? $subdivision : null,
        $postalCode,
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
    // SAVE SKILLS QUESTIONNAIRE ANSWERS
    // ============================================
    if (!empty($skillRatings)) {
        $ratingStmt = $db->prepare(
            "INSERT INTO applicant_skill_ratings (applicant_id, skill_key, rating) VALUES (?, ?, ?)"
        );
        foreach ($skillRatings as $skillKey => $rating) {
            $ratingStmt->execute([$applicantId, $skillKey, $rating]);
        }
    }

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
