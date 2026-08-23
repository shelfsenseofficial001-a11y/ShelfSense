<?php
// views/pages/auth/forgot_password.php

$title = 'Forgot Password - ShelfSense';
$subtitle = 'Reset Your Password';

$content = '
<style>
    .auth-card {
        max-width: 420px !important;
        width: 100% !important;
        padding: 20px !important;
    }
    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 24px;
    }
    .step-dot {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }
    .step-dot.active {
        background: var(--brand-yellow);
        color: var(--brand-yellow-btn-text);
    }
    .step-dot.completed {
        background: #d1fae5;
        color: #065f46;
    }
    .step-dot.inactive {
        background: var(--bg-card-subtle);
        color: var(--text-muted);
    }
    .step-line {
        flex: 1;
        height: 2px;
        background: var(--border-color);
        margin: auto 0;
        max-width: 40px;
    }
    .step-line.completed {
        background: #059669;
    }
    .otp-input {
        font-size: 1.5rem;
        font-weight: 600;
        letter-spacing: 8px;
        text-align: center;
        padding: 12px;
        font-family: monospace;
    }
    .otp-input:focus {
        letter-spacing: 8px;
    }
    .timer-text {
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    .timer-text .time-remaining {
        font-weight: 600;
        color: var(--brand-yellow-hover);
    }
    .resend-btn {
        font-size: 0.85rem;
        padding: 4px 0;
    }
    .form-step {
        display: none;
    }
    .form-step.active {
        display: block;
    }
</style>

<div class="two-column-wrapper" style="flex-direction:column;align-items:center;">
    <div style="width:100%;max-width:420px;">
        <!-- Back Button -->
        <a href="?page=login" class="auth-link text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Back to Login
        </a>
    </div>

    <div class="auth-card" style="max-width:420px;">
        <div class="brand">
            <h1><span class="brand-mark"></span>Shelf<span>Sense</span></h1>
            <small>Reset Your Password</small>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step-dot active" id="step1Dot">1</div>
            <div class="step-line" id="step1Line"></div>
            <div class="step-dot inactive" id="step2Dot">2</div>
            <div class="step-line" id="step2Line"></div>
            <div class="step-dot inactive" id="step3Dot">3</div>
        </div>

        <!-- Step 1: Enter Email -->
        <div class="form-step active" id="step1">
            <p class="text-muted small text-center mb-3">Enter your email address to receive a 6-digit OTP.</p>
            <form id="forgotPasswordForm">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" id="resetEmail" class="form-control" placeholder="you@company.com" required>
                </div>
                <button type="submit" class="btn btn-yellow-primary w-100 rounded-3">
                    <i class="bi bi-envelope me-2"></i> Send OTP
                </button>
            </form>
            <p class="text-center mt-3 text-muted small" id="resetInfo"></p>
        </div>

        <!-- Step 2: Enter OTP -->
        <div class="form-step" id="step2" style="display:none;">
            <p class="text-muted small text-center mb-3">Enter the 6-digit OTP sent to your email.</p>
            <form id="verifyOtpForm">
                <div class="mb-3">
                    <label class="form-label fw-semibold">OTP Code</label>
                    <input type="text" name="otp" id="otpInput" class="form-control otp-input" placeholder="000000" maxlength="6" required autocomplete="one-time-code">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="timer-text">OTP expires in <span class="time-remaining" id="timerDisplay">15:00</span></span>
                    <button type="button" class="btn btn-link resend-btn text-decoration-none" id="resendOtpBtn">Resend OTP</button>
                </div>
                <input type="hidden" id="resetUserId" value="">
                <input type="hidden" id="resetId" value="">
                <button type="submit" class="btn btn-yellow-primary w-100 rounded-3">
                    <i class="bi bi-check-circle me-2"></i> Verify OTP
                </button>
            </form>
        </div>

        <!-- Step 3: Reset Password -->
        <div class="form-step" id="step3" style="display:none;">
            <p class="text-muted small text-center mb-3">Enter your new password.</p>
            <form id="resetPasswordForm">
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="newPassword" class="form-control" placeholder="New password" required minlength="8">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(\'newPassword\')">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">At least 8 characters, with uppercase, lowercase, and number.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Confirm password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(\'confirmPassword\')">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" id="resetIdFinal" value="">
                <button type="submit" class="btn btn-yellow-primary w-100 rounded-3">
                    <i class="bi bi-key me-2"></i> Reset Password
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let timerInterval = null;

function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = input.parentElement.querySelector(\'button i\');
    if (input.type === \'password\') {
        input.type = \'text\';
        icon.className = \'bi bi-eye-slash\';
    } else {
        input.type = \'password\';
        icon.className = \'bi bi-eye\';
    }
}

// Step 1: Request OTP
document.getElementById("forgotPasswordForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const email = document.getElementById("resetEmail").value.trim();
    const submitBtn = this.querySelector(\'button[type="submit"]\');
    submitBtn.disabled = true;
    submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm me-2"></span>Sending...\';

    fetch("?page=api_request_password_reset", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = \'<i class="bi bi-envelope me-2"></i> Send OTP\';

        if (data.success) {
            document.getElementById("resetUserId").value = data.data.user_id || \'\';
            document.getElementById("resetInfo").textContent = "OTP sent to your email. Please check your inbox.";
            document.getElementById("resetInfo").className = "text-center mt-3 text-success small";
            goToStep(2);
            startTimer();
        } else {
            document.getElementById("resetInfo").textContent = data.message || \'Failed to send OTP. Please try again.\';
            document.getElementById("resetInfo").className = "text-center mt-3 text-danger small";
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = \'<i class="bi bi-envelope me-2"></i> Send OTP\';
        document.getElementById("resetInfo").textContent = \'An error occurred. Please try again.\';
        document.getElementById("resetInfo").className = "text-center mt-3 text-danger small";
    });
});

// Step 2: Verify OTP
document.getElementById("verifyOtpForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const otp = document.getElementById("otpInput").value.trim();
    const userId = document.getElementById("resetUserId").value;
    const submitBtn = this.querySelector(\'button[type="submit"]\');
    submitBtn.disabled = true;
    submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm me-2"></span>Verifying...\';

    fetch("?page=api_verify_otp", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ otp: otp, user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = \'<i class="bi bi-check-circle me-2"></i> Verify OTP\';

        if (data.success) {
            document.getElementById("resetId").value = data.data.reset_id || \'\';
            document.getElementById("resetIdFinal").value = data.data.reset_id || \'\';
            goToStep(3);
        } else {
            Swal.fire({
                icon: \'error\',
                title: \'Invalid OTP\',
                text: data.message || \'Please try again.\'
            });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = \'<i class="bi bi-check-circle me-2"></i> Verify OTP\';
        Swal.fire({
            icon: \'error\',
            title: \'Error\',
            text: \'Something went wrong. Please try again.\'
        });
    });
});

// Step 3: Reset Password
document.getElementById("resetPasswordForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const password = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const resetId = document.getElementById("resetIdFinal").value;
    const submitBtn = this.querySelector(\'button[type="submit"]\');
    submitBtn.disabled = true;
    submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm me-2"></span>Resetting...\';

    fetch("?page=api_reset_password", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            reset_id: resetId,
            password: password,
            confirm_password: confirmPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = \'<i class="bi bi-key me-2"></i> Reset Password\';

        if (data.success) {
            Swal.fire({
                icon: \'success\',
                title: \'Password Reset Successfully!\',
                text: \'You can now login with your new password.\',
                confirmButtonText: \'Go to Login\'
            }).then(() => {
                window.location.href = \'?page=login\';
            });
        } else {
            Swal.fire({
                icon: \'error\',
                title: \'Reset Failed\',
                text: data.message || \'Please try again.\'
            });
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = \'<i class="bi bi-key me-2"></i> Reset Password\';
        Swal.fire({
            icon: \'error\',
            title: \'Error\',
            text: \'Something went wrong. Please try again.\'
        });
    });
});

// Resend OTP
document.getElementById("resendOtpBtn").addEventListener("click", function() {
    const email = document.getElementById("resetEmail").value.trim();
    const btn = this;
    btn.disabled = true;
    btn.textContent = \'Sending...\';

    fetch("?page=api_request_password_reset", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = \'Resend OTP\';
        if (data.success) {
            Swal.fire({
                icon: \'success\',
                title: \'OTP Resent!\',
                text: \'Please check your email for the new OTP.\',
                timer: 2000,
                showConfirmButton: false
            });
            startTimer();
        } else {
            Swal.fire({
                icon: \'error\',
                title: \'Failed to Resend\',
                text: data.message || \'Please try again.\'
            });
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.textContent = \'Resend OTP\';
    });
});

function goToStep(step) {
    document.querySelectorAll(\'.form-step\').forEach(el => {
        el.style.display = \'none\';
        el.classList.remove(\'active\');
    });
    document.getElementById(\'step\' + step).style.display = \'block\';
    document.getElementById(\'step\' + step).classList.add(\'active\');

    for (let i = 1; i <= 3; i++) {
        const dot = document.getElementById(\'step\' + i + \'Dot\');
        const line = document.getElementById(\'step\' + i + \'Line\');
        if (i < step) {
            dot.className = \'step-dot completed\';
            dot.textContent = \'✓\';
            if (line) line.className = \'step-line completed\';
        } else if (i === step) {
            dot.className = \'step-dot active\';
            dot.textContent = i;
        } else {
            dot.className = \'step-dot inactive\';
            dot.textContent = i;
            if (line) line.className = \'step-line\';
        }
    }
}

function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    let seconds = 900;
    const display = document.getElementById(\'timerDisplay\');

    timerInterval = setInterval(() => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        display.textContent = String(mins).padStart(2, \'0\') + \':\' + String(secs).padStart(2, \'0\');
        seconds--;

        if (seconds < 0) {
            clearInterval(timerInterval);
            display.textContent = \'Expired\';
            display.style.color = \'#dc2626\';
        }
    }, 1000);
}

// OTP input - auto focus and auto submit
document.getElementById(\'otpInput\').addEventListener(\'input\', function() {
    if (this.value.length === 6) {
        document.getElementById(\'verifyOtpForm\').querySelector(\'button[type="submit"]\').click();
    }
});
</script>
';

require_once __DIR__ . '/../../layouts/auth.php';