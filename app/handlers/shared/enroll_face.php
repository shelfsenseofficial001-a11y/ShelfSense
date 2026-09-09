<?php
// app/handlers/shared/enroll_face.php
// Stores the current user's face descriptors (from face-capture.js) for
// later attendance verification. Descriptors are 128-float numeric face
// measurements produced client-side -- the raw camera video never leaves
// the browser.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$input = json_decode(file_get_contents('php://input'), true);
$descriptors = $input['descriptors'] ?? null;
$consent = $input['consent'] ?? false;
$blinkVerified = !empty($input['blink_verified']);

if (!$consent) {
    Response::error('Consent is required to enroll Face ID.', 400);
}

if (!$blinkVerified) {
    Response::error('Liveness check (blink) was not completed.', 400);
}

if (!is_array($descriptors) || count($descriptors) < 1 || count($descriptors) > 10) {
    Response::error('Invalid face data captured. Please try again.', 400);
}

foreach ($descriptors as $d) {
    if (!is_array($d) || count($d) !== 128) {
        Response::error('Invalid face data captured. Please try again.', 400);
    }
    foreach ($d as $v) {
        if (!is_numeric($v)) {
            Response::error('Invalid face data captured. Please try again.', 400);
        }
    }
}

$userId = Auth::userId();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    INSERT INTO face_enrollments (user_id, descriptors, consent_at, enrolled_at)
    VALUES (?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        descriptors = VALUES(descriptors),
        consent_at = VALUES(consent_at),
        updated_at = NOW()
");
$stmt->execute([$userId, json_encode($descriptors)]);

// Completes the forced first-login flow, if this enrollment was that
// flow's step 2 -- harmless no-op if it wasn't (already 0).
$db->prepare("UPDATE users SET is_first_login = 0 WHERE user_id = ? AND is_first_login = 1")->execute([$userId]);
$_SESSION['is_first_login'] = 0;

Response::success([], 'Face ID enrolled successfully');
