<?php
// views/pages/first_login_setup.php
// Forced gate for a brand-new account or one just promoted to a new role
// (both stamp is_first_login=1 in the DB) -- must set a real password
// before doing anything else, and cashier-facing roles without an
// existing Face ID must enroll one too. public/index.php redirects here
// for any authenticated page load while is_first_login is still true.

use App\Core\Auth;
use App\Core\Database;

require_once __DIR__ . '/../../app/core/Database.php';

$db = Database::getInstance()->getConnection();

$role = Auth::role();
$needsFaceStep = false;
if (in_array($role, ['employee', 'trainee'], true)) {
    $stmt = $db->prepare("SELECT 1 FROM face_enrollments WHERE user_id = ?");
    $stmt->execute([Auth::userId()]);
    $needsFaceStep = !$stmt->fetch();
}

$title = 'Set Up Your Account - ShelfSense';
$firstName = htmlspecialchars(Auth::role() ? ($_SESSION['first_name'] ?? '') : '', ENT_QUOTES);
$needsFaceStepJs = $needsFaceStep ? 'true' : 'false';

$content = '
<div class="brand">
    <h1><span class="brand-mark"></span>Shelf<span>Sense</span></h1>
    <small>Account Setup</small>
</div>

<div class="form-header text-center mb-3">
    <h3>Welcome' . ($firstName !== '' ? ', ' . $firstName : '') . '!</h3>
    <p class="text-muted small">Before you continue, let\'s secure your account' . ($needsFaceStep ? ' and set up Face ID for attendance' : '') . '.</p>
</div>

<div class="setup-steps mb-3">
    <div class="setup-step active" id="stepIndicator1"><span>1</span> Password</div>
    ' . ($needsFaceStep ? '<div class="setup-step" id="stepIndicator2"><span>2</span> Face ID</div>' : '') . '
</div>

<div id="passwordStep">
    <div class="mb-3">
        <label class="form-label fw-semibold">New password</label>
        <input type="password" id="newPassword" class="form-control" placeholder="At least 8 characters" autocomplete="new-password">
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Confirm new password</label>
        <input type="password" id="confirmPassword" class="form-control" placeholder="Re-enter your new password" autocomplete="new-password">
    </div>
    <div id="passwordError" class="text-danger small mb-2" style="display:none;"></div>
    <button type="button" class="btn btn-yellow-primary w-100 rounded-3" id="passwordSubmitBtn">
        <i class="bi bi-shield-lock me-2"></i>Continue
    </button>
</div>

<div id="faceStep" style="display:none;">
    <div class="text-center mb-3">
        <i class="bi bi-camera" style="font-size:2rem;color:#ff6b35;"></i>
        <p class="text-muted small mt-2">Face ID lets the register confirm it\'s you instead of typing a reference number every shift. This is required for your role.</p>
    </div>
    <div id="faceError" class="text-danger small mb-2" style="display:none;"></div>
    <button type="button" class="btn btn-yellow-primary w-100 rounded-3" id="faceEnrollBtn">
        <i class="bi bi-camera me-2"></i>Enroll Face ID
    </button>
    <p class="text-muted small text-center mt-3 mb-0">
        See our <a href="?page=privacy_policy" target="_blank">Privacy Policy</a> for how biometric data is handled.
    </p>
</div>

<style>
.setup-steps { display:flex; gap:10px; justify-content:center; }
.setup-step { display:flex; align-items:center; gap:6px; font-size:0.8rem; color:var(--text-muted); }
.setup-step span { width:20px; height:20px; border-radius:50%; background:var(--bg-card-subtle); display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; }
.setup-step.active { color:var(--text-main); font-weight:600; }
.setup-step.active span { background:#ff6b35; color:#fff; }
.setup-step.done span { background:#2fb380; color:#fff; }
</style>

<script>
const needsFaceStep = ' . $needsFaceStepJs . ';

document.getElementById("passwordSubmitBtn").addEventListener("click", function() {
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const errEl = document.getElementById("passwordError");
    errEl.style.display = "none";

    if (!newPassword || !confirmPassword) {
        errEl.textContent = "Please fill in both fields.";
        errEl.style.display = "block";
        return;
    }

    const btn = this;
    btn.disabled = true;

    fetch("?page=api_first_login_change_password", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ new_password: newPassword, confirm_password: confirmPassword })
    })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            if (!res.success) {
                errEl.textContent = res.message || "Something went wrong.";
                errEl.style.display = "block";
                return;
            }
            if (res.data.requires_face) {
                document.getElementById("passwordStep").style.display = "none";
                document.getElementById("faceStep").style.display = "block";
                document.getElementById("stepIndicator1").classList.remove("active");
                document.getElementById("stepIndicator1").classList.add("done");
                document.getElementById("stepIndicator2").classList.add("active");
            } else {
                window.location.href = "?page=dashboard";
            }
        })
        .catch(() => {
            btn.disabled = false;
            errEl.textContent = "Something went wrong. Please try again.";
            errEl.style.display = "block";
        });
});

const faceEnrollBtn = document.getElementById("faceEnrollBtn");
if (faceEnrollBtn) {
    faceEnrollBtn.addEventListener("click", function() {
        const btn = this;
        const errEl = document.getElementById("faceError");
        errEl.style.display = "none";
        btn.disabled = true;

        window.ShelfFaceCapture.run({
            angles: ["Look straight at the camera", "Slowly turn your head slightly left", "Slowly turn your head slightly right"]
        }).then(function(result) {
            return fetch("?page=api_enroll_face", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ descriptors: result.descriptors, consent: true, blink_verified: result.blinkVerified })
            }).then(function(r) { return r.json(); });
        }).then(function(res) {
            btn.disabled = false;
            if (res.success) {
                window.location.href = "?page=dashboard";
            } else {
                errEl.textContent = res.message || "Enrollment failed. Please try again.";
                errEl.style.display = "block";
            }
        }).catch(function(err) {
            btn.disabled = false;
            if (err && err.message !== "cancelled") {
                errEl.textContent = err.message || "Something went wrong.";
                errEl.style.display = "block";
            }
        });
    });
}
</script>
';

if ($needsFaceStep) {
    $content .= '
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script src="/ShelfSense/public/assets/js/shared/face-capture.js?v=20260909100000"></script>';
}

require_once __DIR__ . '/../layouts/auth.php';
