<?php
// views/pages/auth/login.php
$title = 'Staff Login - ShelfSense';
$subtitle = 'Staff Portal Login';

$content = '
<style>
    /* Two-column container setup preserving layout structure */
    .auth-card {
        max-width: 900px !important;
        width: 100% !important;
        padding: 16px !important;
        border-radius: 24px !important;
    }

    /* Hide default layout header inside container */
    .auth-card > .brand {
        display: none !important;
    }

    .two-column-wrapper {
        display: flex;
        flex-direction: row;
        width: 100%;
        min-height: 540px;
        border-radius: 18px;
        overflow: hidden;
    }

    /* Left Visual Panel - Light Mode Default */
    .visual-panel {
        flex: 1;
                background: radial-gradient(circle at 50% 0%, #fcd34d 0%, #fbbf24 35%, #f59e0b 70%, #d97706 100%);
        border-radius: 16px;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    /* Left Visual Panel - Dark Mode Override */
    [data-bs-theme="dark"] .visual-panel,
    .dark-mode .visual-panel {
        background: radial-gradient(circle at 50% 0%, #d97706 0%, #78350f 35%, #180d05 70%, #0d0703 100%);
    }

    .visual-panel::before {
        content: "";
        position: absolute;
        top: -60px;
        left: 50%;
        transform: translateX(-50%);
        width: 220px;
        height: 120px;
        background: #f59e0b;
        filter: blur(50px);
        opacity: 0.8;
        border-radius: 50%;
    }

    .visual-panel h2 {
        font-family: "Space Grotesk", sans-serif;
        color: #ffffff;
        font-size: 1.65rem;
        font-weight: 700;
        margin-bottom: 8px;
        z-index: 1;
    }

    .visual-panel p {
        color: #f3f4f6;
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.9;
        z-index: 1;
    }

    /* Right Form Panel */
    .form-panel {
        flex: 1;
        padding: 40px 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .form-header p {
        color: var(--text-muted, #9ca3af);
        font-size: 0.875rem;
        margin-bottom: 24px;
    }

    /* Input Field Styling using CSS Variables */
    .form-panel .form-control,
    .form-panel .input-group-text,
    .form-panel .btn-outline-secondary {
        background-color: var(--bg-card-subtle, #f4f4f5) !important;
        border-color: var(--border-color, #e4e4e7) !important;
        color: var(--text-primary, #09090b) !important;
    }

    /* Dark Mode Specific Overrides for Inputs */
    [data-bs-theme="dark"] .form-panel .form-control,
    [data-bs-theme="dark"] .form-panel .input-group-text,
    [data-bs-theme="dark"] .form-panel .btn-outline-secondary,
    .dark-mode .form-panel .form-control,
    .dark-mode .form-panel .input-group-text,
    .dark-mode .form-panel .btn-outline-secondary {
        background-color: #1f1c13 !important;
        border-color: #383321 !important;
        color: #f4f4f5 !important;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .two-column-wrapper {
            flex-direction: column;
        }
        .visual-panel {
            min-height: 200px;
            padding: 30px 20px;
        }
        .form-panel {
            padding: 28px 20px;
        }
    }
</style>

<div class="two-column-wrapper">
    <!-- Left Section: Visual Panel -->
    <div class="visual-panel">
        <h2>ShelfSense Portal</h2>
        <p>Smart inventory control at your fingertips.</p>
    </div>

    <!-- Right Section: Form Content -->
    <div class="form-panel">
        <div class="mb-3">
            <a href="?page=home" class="auth-link text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>

        <div class="form-header">
            <h3>Staff Login</h3>
            <p>Enter your account details to access the portal.</p>
        </div>

        <form method="POST" action="?page=api_login" id="loginForm">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@company.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-yellow-primary w-100 rounded-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </form>

        <p class="text-center mt-3 text-muted small mb-0">
            Default credentials: <strong>maria.santos@shelfsense.com</strong> / <strong>Password123!</strong>
        </p>
    </div>
</div>

<script>
document.getElementById("togglePassword").addEventListener("click", function() {
    const input = document.getElementById("password");
    const icon = this.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
});

document.getElementById("loginForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    const submitBtn = form.querySelector("button[type=submit]");
    submitBtn.disabled = true;
    submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm me-2"></span>Logging in...\';
    
    try {
        const response = await fetch(form.action, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
            window.location.href = "?page=dashboard";
        } else {
            Swal.fire({
                icon: "error",
                title: "Login Failed",
                text: result.message || "Invalid credentials"
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = \'<i class="bi bi-box-arrow-in-right me-2"></i>Login\';
        }
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Something went wrong. Please try again."
        });
        submitBtn.disabled = false;
        submitBtn.innerHTML = \'<i class="bi bi-box-arrow-in-right me-2"></i>Login\';
    }
});
</script>
';

require_once __DIR__ . '/../../layouts/auth.php';