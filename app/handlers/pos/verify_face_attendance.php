<?php
// app/handlers/pos/verify_face_attendance.php
// Called from the phone that scanned the QR (public, token-authenticated,
// no staff login) or from the register's own fallback camera. Compares
// captured face descriptors against the employee's enrolled descriptors
// and, on a match, auto-records the attendance punch -- no manual HR
// entry. Never touches POS session state directly: the confirming device
// is frequently not the register's own browser session, so the register
// picks up the result by polling get_attendance_qr_status.php.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/Attendance.php';

use App\Core\Database;
use App\Core\Response;
use App\Models\Attendance;

header('Content-Type: application/json');

// A descriptor pair from the same person typically lands well under 0.5;
// different people are usually well above 0.6. 0.5 favors avoiding
// false-positive clock-ins over convenience.
const FACE_MATCH_THRESHOLD = 0.5;

$input = json_decode(file_get_contents('php://input'), true);
$token = isset($input['token']) ? (string)$input['token'] : '';
$descriptors = $input['descriptors'] ?? null;
$photoDataUrl = isset($input['photo']) ? (string)$input['photo'] : '';

if ($token === '' || !is_array($descriptors) || count($descriptors) < 1) {
    Response::error('Missing face data.', 400);
}

foreach ($descriptors as $d) {
    if (!is_array($d) || count($d) !== 128) {
        Response::error('Invalid face data.', 400);
    }
}

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT * FROM attendance_qr_sessions WHERE token = ?");
    $stmt->execute([$token]);
    $session = $stmt->fetch();

    if (!$session) {
        Response::error('Attendance session not found.', 404);
    }

    if ($session['status'] !== 'pending') {
        Response::error('This attendance session is no longer active.', 400);
    }

    if (strtotime($session['expires_at']) < time()) {
        $db->prepare("UPDATE attendance_qr_sessions SET status = 'expired' WHERE id = ?")->execute([$session['id']]);
        Response::error('This attendance session has expired. Please select the cashier again at the register.', 400);
    }

    $stmt = $db->prepare("SELECT descriptors FROM face_enrollments WHERE user_id = ?");
    $stmt->execute([$session['user_id']]);
    $enrollment = $stmt->fetch();

    if (!$enrollment) {
        $db->prepare("UPDATE attendance_qr_sessions SET status = 'failed', fail_reason = 'not_enrolled' WHERE id = ?")->execute([$session['id']]);
        Response::error('This employee has not enrolled Face ID yet. Ask HR to enroll them from their Profile.', 400);
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
        // Left as 'pending' -- the phone can retry immediately within the
        // same 5-minute window instead of forcing a fresh QR code.
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
            $filename = 'att_' . $session['user_id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (file_put_contents($baseDir . $filename, $binary) !== false) {
                $photoPath = 'uploads/attendance/' . $filename;
            }
        }
    }

    $attendanceModel = new Attendance();
    $action = $attendanceModel->recordFaceClock((int)$session['user_id'], $photoPath, $bestDistance);

    $db->prepare("
        UPDATE attendance_qr_sessions
        SET status = 'confirmed', match_distance = ?, captured_photo = ?, attendance_action = ?, confirmed_at = NOW()
        WHERE id = ?
    ")->execute([$bestDistance, $photoPath, $action, $session['id']]);

    Response::success(['action' => $action], 'Attendance recorded');

} catch (Exception $e) {
    error_log('verify_face_attendance.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
