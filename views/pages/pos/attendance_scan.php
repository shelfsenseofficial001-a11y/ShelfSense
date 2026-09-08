<?php
// views/pages/pos/attendance_scan.php
// Public page opened by scanning the QR shown at the register. No staff
// login required -- the token itself is the (short-lived, single-use)
// credential. Runs the same face-capture.js flow as enrollment.

use App\Core\Database;

require_once __DIR__ . '/../../../app/core/Database.php';

$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
$title = 'Attendance Scan - ShelfSense';

$cashierName = null;
$registerName = null;
$validSession = false;

if ($token !== '') {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT s.status, s.expires_at, u.first_name, u.last_name, r.name AS register_name
        FROM attendance_qr_sessions s
        JOIN users u ON u.user_id = s.user_id
        LEFT JOIN registers r ON r.id = s.register_id
        WHERE s.token = ?
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        $cashierName = $row['first_name'] . ' ' . $row['last_name'];
        $registerName = $row['register_name'];
        $validSession = ($row['status'] === 'pending' && strtotime($row['expires_at']) >= time());
    }
}

$tokenAttr = htmlspecialchars($token, ENT_QUOTES);
$cashierNameEsc = htmlspecialchars($cashierName ?? '', ENT_QUOTES);
$registerNameEsc = htmlspecialchars($registerName ?? 'the register', ENT_QUOTES);

if (!$token || !$cashierName) {
    $content = '
    <div class="text-center py-4">
        <i class="bi bi-qr-code-scan" style="font-size:2.2rem;color:var(--text-muted);"></i>
        <h4 class="mt-3">Invalid attendance link</h4>
        <p class="text-muted">This link is missing or no longer valid. Ask the register to show the QR code again.</p>
    </div>';
} elseif (!$validSession) {
    $content = '
    <div class="text-center py-4">
        <i class="bi bi-clock-history" style="font-size:2.2rem;color:var(--text-muted);"></i>
        <h4 class="mt-3">This link has expired</h4>
        <p class="text-muted">Ask the register to show a fresh QR code and scan again.</p>
    </div>';
} else {
    $content = '
    <div class="text-center py-2">
        <i class="bi bi-person-badge" style="font-size:2rem;color:#ff6b35;"></i>
        <h4 class="mt-3 mb-1">Hi, ' . $cashierNameEsc . '</h4>
        <p class="text-muted mb-4">Scan your face to record your attendance for <strong>' . $registerNameEsc . '</strong>.</p>
        <div id="scanResult"></div>
        <button type="button" class="btn btn-yellow-primary w-100 rounded-3" id="startScanBtn">
            <i class="bi bi-camera me-2"></i>Start Face Scan
        </button>
        <p class="text-muted small mt-3">By continuing you agree to a quick face scan for attendance verification. See the biometric notice for details.</p>
    </div>

    <script>
    var attendanceToken = "' . $tokenAttr . '";
    document.getElementById("startScanBtn").addEventListener("click", function() {
        var btn = this;
        btn.disabled = true;
        window.ShelfFaceCapture.run({
            angles: ["Look straight at the camera", "Slowly turn your head slightly left", "Slowly turn your head slightly right"]
        }).then(function(result) {
            return fetch("?page=api_verify_face_attendance", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ token: attendanceToken, descriptors: result.descriptors, photo: result.photo })
            }).then(function(r) { return r.json(); });
        }).then(function(res) {
            var resultEl = document.getElementById("scanResult");
            if (res.success) {
                var msg = res.data.action === "time_out" ? "You\'re clocked out. Thanks!" :
                           res.data.action === "already_recorded" ? "Attendance already recorded for today." :
                           "You\'re clocked in. You may return to the register.";
                resultEl.innerHTML = \'<div class="alert alert-success"><i class="bi bi-check-circle-fill me-1"></i>\' + msg + \'</div>\';
                btn.style.display = "none";
            } else {
                resultEl.innerHTML = \'<div class="alert alert-danger">\' + (res.message || "Verification failed.") + \'</div>\';
                btn.disabled = false;
            }
        }).catch(function(err) {
            btn.disabled = false;
            if (err && err.message !== "cancelled") {
                document.getElementById("scanResult").innerHTML = \'<div class="alert alert-danger">\' + (err.message || "Something went wrong.") + \'</div>\';
            }
        });
    });
    </script>';
}

if ($token && $cashierName && $validSession) {
    $content .= '
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="/ShelfSense/public/assets/js/shared/face-capture.js?v=20260908100000"></script>';
}

require_once __DIR__ . '/../../layouts/auth.php';
