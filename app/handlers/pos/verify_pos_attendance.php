<?php
// app/handlers/pos/verify_pos_attendance.php
// Face verification now happens directly on the register's own camera --
// no QR handoff to a phone. pos_select_cashier.php stamps who just passed
// the password check into this POS session ($_SESSION['pos_pending_*']);
// this endpoint only accepts a face capture for that exact pending
// cashier, within a short window, so a client can't just submit an
// arbitrary user_id and skip the password step.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Attendance.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Models\Attendance;

header('Content-Type: application/json');

// A descriptor pair from the same person typically lands well under 0.5;
// different people are usually well above 0.6. 0.5 favors avoiding
// false-positive clock-ins over convenience.
const FACE_MATCH_THRESHOLD = 0.5;

if (!Auth::posCheck()) {
    Response::unauthorized('Please log in to a register first.');
}

$pendingUserId = $_SESSION['pos_pending_cashier_id'] ?? null;
$pendingExpires = $_SESSION['pos_pending_cashier_expires'] ?? 0;
$pendingName = $_SESSION['pos_pending_cashier_name'] ?? null;

if (!$pendingUserId || time() > $pendingExpires) {
    unset($_SESSION['pos_pending_cashier_id'], $_SESSION['pos_pending_cashier_name'], $_SESSION['pos_pending_cashier_expires']);
    Response::error('Your session to verify has expired. Please select the cashier again.', 400);
}

$input = json_decode(file_get_contents('php://input'), true);
$descriptors = $input['descriptors'] ?? null;
$photoDataUrl = isset($input['photo']) ? (string)$input['photo'] : '';
$blinkVerified = !empty($input['blink_verified']);

if (!is_array($descriptors) || count($descriptors) < 1) {
    Response::error('Missing face data.', 400);
}

foreach ($descriptors as $d) {
    if (!is_array($d) || count($d) !== 128) {
        Response::error('Invalid face data.', 400);
    }
}

if (!$blinkVerified) {
    Response::error('Liveness check (blink) was not completed.', 400);
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT descriptors FROM face_enrollments WHERE user_id = ?");
    $stmt->execute([$pendingUserId]);
    $enrollment = $stmt->fetch();

    if (!$enrollment) {
        Response::error('This employee has not enrolled Face ID yet. Ask HR to check their account.', 400);
    }

    $enrolledDescriptors = json_decode($enrollment['descriptors'], true);

    $bestDistance = null;
    foreach ($descriptors as $captured) {
        foreach ($enrolledDescriptors as $enrolled) {
            $dist = 0.0;
            for ($i = 0; $i < 128; $i++) {
                $diff = (float)$captured[$i] - (float)$enrolled[$i];
                $dist += $diff * $diff;
            }
            $dist = sqrt($dist);
            if ($bestDistance === null || $dist < $bestDistance) {
                $bestDistance = $dist;
            }
        }
    }

    if ($bestDistance === null || $bestDistance > FACE_MATCH_THRESHOLD) {
        // Pending state is left intact -- the cashier can retry immediately
        // within the same window instead of re-entering their password.
        Response::error('Face not recognized. Please try again with clear lighting.', 401);
    }

    $photoPath = null;
    if ($photoDataUrl && preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $photoDataUrl, $m)) {
        $ext = $m[1] === 'png' ? 'png' : 'jpg';
        $binary = base64_decode($m[2]);
        if ($binary !== false && strlen($binary) <= 5 * 1024 * 1024) {
            $baseDir = __DIR__ . '/../../../public/uploads/attendance/';
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0777, true);
            }
            $filename = 'att_' . $pendingUserId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (file_put_contents($baseDir . $filename, $binary) !== false) {
                $photoPath = 'uploads/attendance/' . $filename;
            }
        }
    }

    $attendanceModel = new Attendance();
    $action = $attendanceModel->recordFaceClock((int)$pendingUserId, $photoPath, $bestDistance);

    Auth::posSetCashier((int)$pendingUserId, $pendingName);
    unset($_SESSION['pos_pending_cashier_id'], $_SESSION['pos_pending_cashier_name'], $_SESSION['pos_pending_cashier_expires']);

    Response::success([
        'action' => $action,
        'redirect' => '?page=pos_checkout'
    ], 'Attendance recorded');

} catch (Exception $e) {
    error_log('verify_pos_attendance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
