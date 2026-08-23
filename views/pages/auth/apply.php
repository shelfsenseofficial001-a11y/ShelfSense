<?php
// views/pages/auth/apply.php
$title = 'Apply Now - ShelfSense';
$subtitle = 'Start Your Career with ShelfSense';

// Get role from URL parameter
$selectedRole = isset($_GET['role']) ? htmlspecialchars($_GET['role'], ENT_QUOTES, 'UTF-8') : '';

// Role display names
$roleDisplayNames = [
    'cashier' => 'Cashier',
    'hr_staff' => 'HR Staff',
    'finance_staff' => 'Finance Staff',
    'hr_head' => 'Head of Human Resources',
    'finance_head' => 'Head of Finance'
];

$displayRole = htmlspecialchars($roleDisplayNames[$selectedRole] ?? 'Position', ENT_QUOTES, 'UTF-8');

// Role-specific header messages
$roleMessages = [
    'cashier' => "Join our store team! We're looking for friendly and efficient cashiers.",
    'hr_staff' => "Help us build a great workplace! We're hiring HR professionals.",
    'finance_staff' => "Join our finance team and help us manage the numbers.",
    'hr_head' => "Lead our HR department and shape our company culture.",
    'finance_head' => "Lead our finance team and drive financial strategy."
];

$message = htmlspecialchars($roleMessages[$selectedRole] ?? "We're excited to receive your application.", ENT_QUOTES, 'UTF-8');

$content = '
<style>
    /* Theme Variables for Seamless Light/Dark Compatibility */
    :root {
        --card-bg: #ffffff;
        --card-border: #e5e7eb;
        --text-main: #111827;
        --text-subtle: #6b7280;
        --input-bg: #f9fafb;
        --input-border: #d1d5db;
        --input-text: #111827;
        --brand-accent: #f59e0b;
        --brand-accent-hover: #d97706;
        --alert-bg: #fffbeb;
        --alert-border: #fcd34d;
        --alert-title: #b45309;
        --alert-desc: #78350f;

        /* Visual Panel Variables (Light Mode) */
        --panel-bg: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        --panel-title: #1f2937;
        --panel-text: #4b5563;
        --panel-feature-text: #374151;
        --panel-badge-bg: rgba(217, 119, 6, 0.15);
        --panel-badge-border: rgba(217, 119, 6, 0.3);
        --panel-badge-text: #b45309;
        --panel-footer-text: #6b7280;
        --panel-divider: rgba(107, 114, 128, 0.2);
        --panel-glow: rgba(245, 158, 11, 0.35);
    }

    [data-bs-theme="dark"] {
        --card-bg: #181611;
        --card-border: #383321;
        --text-main: #f9fafb;
        --text-subtle: #9ca3af;
        --input-bg: #1f1c13;
        --input-border: #383321;
        --input-text: #f4f4f5;
        --alert-bg: rgba(245, 158, 11, 0.1);
        --alert-border: rgba(245, 158, 11, 0.3);
        --alert-title: #fbbf24;
        --alert-desc: #f3f4f6;

        /* Visual Panel Variables (Dark Mode) */
        --panel-bg: linear-gradient(135deg, #262018 0%, #18130e 100%);
        --panel-title: #ffffff;
        --panel-text: #d1d5db;
        --panel-feature-text: #9ca3af;
        --panel-badge-bg: rgba(245, 158, 11, 0.15);
        --panel-badge-border: rgba(245, 158, 11, 0.3);
        --panel-badge-text: #fbbf24;
        --panel-footer-text: #9ca3af;
        --panel-divider: rgba(255, 255, 255, 0.15);
        --panel-glow: rgba(245, 158, 11, 0.2);
    }

    /* Container Alignment */
    .auth-card {
        max-width: 920px !important;
        width: 100% !important;
        margin: 0 auto;
    }

    /* Wrapper Card */
    .two-column-wrapper {
        display: flex;
        flex-direction: row;
        width: 100%;
        min-height: 560px;
        border-radius: 16px;
        overflow: hidden;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    /* Adaptive Visual Panel */
    .left-visual-panel {
        flex: 0.85;
        background: var(--panel-bg);
        padding: 40px 32px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        transition: background 0.3s ease;
    }

    /* Decorative Glow Accent */
    .left-visual-panel::before {
        content: "";
        position: absolute;
        top: -20%;
        right: -20%;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, var(--panel-glow) 0%, rgba(0,0,0,0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .left-visual-panel .brand-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--panel-badge-bg);
        border: 1px solid var(--panel-badge-border);
        color: var(--panel-badge-text);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        width: fit-content;
    }

    .left-visual-panel h2 {
        font-family: "Space Grotesk", sans-serif;
        color: var(--panel-title);
        font-size: 1.75rem;
        font-weight: 700;
        margin-top: 16px;
        margin-bottom: 8px;
        line-height: 1.2;
    }

    .left-visual-panel p {
        color: var(--panel-text);
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.5;
    }

    .left-visual-panel .feature-list {
        list-style: none;
        padding: 0;
        margin: 24px 0 0 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .left-visual-panel .feature-list li {
        color: var(--panel-feature-text);
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .left-visual-panel .feature-list li i {
        color: #d97706;
    }

    [data-bs-theme="dark"] .left-visual-panel .feature-list li i {
        color: #f59e0b;
    }

    .left-visual-panel .panel-footer {
        margin-top: 24px;
        padding-top: 16px;
        border-top: 1px solid var(--panel-divider);
    }

    .left-visual-panel .panel-footer small {
        color: var(--panel-footer-text);
    }

    /* Right Form Panel */
    .right-form-panel {
        flex: 1.15;
        padding: 36px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: var(--text-main);
    }

    .form-header p {
        color: var(--text-subtle);
        font-size: 0.85rem;
        margin-bottom: 20px;
    }

    .form-label {
        color: var(--text-main);
        font-size: 0.85rem;
        margin-bottom: 4px;
    }

    /* Back Button Link */
    .back-btn-link {
        color: var(--brand-accent) !important;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 12px;
        transition: opacity 0.2s;
    }

    .back-btn-link:hover {
        opacity: 0.8;
    }

    /* Dynamic Theme Form Controls */
    .form-control,
    .form-select {
        background-color: var(--input-bg) !important;
        border-color: var(--input-border) !important;
        color: var(--input-text) !important;
        font-size: 0.9rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--brand-accent) !important;
        box-shadow: 0 0 0 0.25rem rgba(245, 158, 11, 0.2) !important;
    }

    .form-control::placeholder {
        color: var(--text-subtle) !important;
        opacity: 0.7;
    }

    /* Custom File Input */
    .custom-file-input::file-selector-button {
        background-color: var(--input-border);
        color: var(--input-text);
        border: none;
        padding: 0.375rem 0.75rem;
        margin-right: 0.75rem;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .custom-file-input::file-selector-button:hover {
        background-color: var(--brand-accent);
        color: #ffffff;
    }

    /* Theme Alert Banner */
    #roleAlertBanner {
        background-color: var(--alert-bg) !important;
        border: 1px solid var(--alert-border) !important;
        border-left: 4px solid var(--brand-accent) !important;
        border-radius: 8px;
    }

    #bannerRoleTitle {
        color: var(--alert-title) !important;
    }

    #bannerRoleDesc {
        color: var(--alert-desc) !important;
    }

    /* Submit Button */
    .btn-yellow-primary {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        color: #000000 !important;
        font-weight: 600;
        padding: 10px;
        transition: background-color 0.2s;
    }

    .btn-yellow-primary:hover {
        background-color: var(--brand-accent-hover) !important;
        border-color: var(--brand-accent-hover) !important;
        color: #ffffff !important;
    }

    @media (max-width: 768px) {
        .two-column-wrapper {
            flex-direction: column;
        }
        .left-visual-panel {
            min-height: auto;
            padding: 24px;
        }
        .left-visual-panel .feature-list {
            display: none;
        }
        .right-form-panel {
            padding: 24px;
        }
    }
</style>

<div class="two-column-wrapper">
    <!-- Left Column: Visual Brand Card -->
    <div class="left-visual-panel">
        <div>
            <div class="brand-badge">
                <i class="bi bi-box-seam-fill"></i> ShelfSense
            </div>
            <h2>Smart Retail & Career Hub</h2>
            <p>Empowering teams with intelligent inventory systems and seamless workflow tools.</p>
            
            <ul class="feature-list">
                <li><i class="bi bi-check-circle-fill"></i> Real-time operational management</li>
                <li><i class="bi bi-check-circle-fill"></i> Collaborative team environments</li>
                <li><i class="bi bi-check-circle-fill"></i> Continuous growth opportunities</li>
            </ul>
        </div>

        <div class="panel-footer">
            <small>&copy; ShelfSense Portal. All rights reserved.</small>
        </div>
    </div>

    <!-- Right Column: Application Form -->
    <div class="right-form-panel">
        <div>
            <a href="?page=home" class="back-btn-link text-decoration-none">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>

        <div class="form-header">
            <h3>Start Your Career</h3>
            <p>Fill in your details below to submit an application.</p>
        </div>

        <!-- Dynamic Banner Alert Container -->
        <div class="mb-3">
            <div class="alert py-2 px-3 m-0" id="roleAlertBanner" style="display: ' . ($selectedRole ? 'block' : 'none') . ';">
                <strong id="bannerRoleTitle">Applying for: ' . $displayRole . '</strong>
                <br>
                <small id="bannerRoleDesc">' . $message . '</small>
            </div>
        </div>

        <form method="POST" action="?page=api_apply" enctype="multipart/form-data" id="applyForm">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required maxlength="50">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required maxlength="50">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email *</label>
                    <input type="email" name="email" class="form-control" required maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" placeholder="09123456789" required maxlength="12" pattern="[0-9]{10,12}">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Position Applying For *</label>
                    <select name="target_role" id="targetRoleSelect" class="form-select" required>
                        <option value="" hidden>Select a position...</option>
                        <option value="hr_head" ' . ($selectedRole === 'hr_head' ? 'selected' : '') . '>Head of Human Resources</option>
                        <option value="finance_head" ' . ($selectedRole === 'finance_head' ? 'selected' : '') . '>Head of Finance</option>
                        <option value="cashier" ' . ($selectedRole === 'cashier' ? 'selected' : '') . '>Cashier</option>
                        <option value="hr_staff" ' . ($selectedRole === 'hr_staff' ? 'selected' : '') . '>HR Staff</option>
                        <option value="finance_staff" ' . ($selectedRole === 'finance_staff' ? 'selected' : '') . '>Finance Staff</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Upload Resume *</label>
                    <input type="file" 
                        name="resume" 
                        class="form-control custom-file-input" 
                        accept=".pdf,.doc,.docx" 
                        required>
                    <small class="text-muted d-block mt-1">PDF, DOC, DOCX files only. Max 5MB.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-yellow-primary w-100 mt-3 rounded-3">
                <i class="bi bi-send me-2"></i>Submit Application
            </button>
        </form>
    </div>
</div>

<script>
// ✅ Role mapping for backend (lowercase → display name)
const roleMapToDisplay = {
    "cashier": "Cashier",
    "hr_staff": "HR Staff",
    "finance_staff": "Finance Staff",
    "hr_head": "Head HR",
    "finance_head": "Head Finance"
};

// Role metadata for banner
const roleInfoMap = {
    "cashier": {
        title: "Cashier",
        message: "Join our store team! We\'re looking for friendly and efficient cashiers."
    },
    "hr_staff": {
        title: "HR Staff",
        message: "Help us build a great workplace! We\'re hiring HR professionals."
    },
    "finance_staff": {
        title: "Finance Staff",
        message: "Join our finance team and help us manage the numbers."
    },
    "hr_head": {
        title: "Head of Human Resources",
        message: "Lead our HR department and shape our company culture."
    },
    "finance_head": {
        title: "Head of Finance",
        message: "Lead our finance team and drive financial strategy."
    }
};

// Handle real-time alert updates on option selection
document.getElementById("targetRoleSelect").addEventListener("change", function() {
    const banner = document.getElementById("roleAlertBanner");
    const bannerTitle = document.getElementById("bannerRoleTitle");
    const bannerDesc = document.getElementById("bannerRoleDesc");
    const selected = this.value;

    if (selected && roleInfoMap[selected]) {
        bannerTitle.textContent = "Applying for: " + roleInfoMap[selected].title;
        bannerDesc.textContent = roleInfoMap[selected].message;
        banner.style.display = "block";
    } else {
        banner.style.display = "none";
    }
});

// ✅ Form Submission - Convert role to display name before sending
document.getElementById("applyForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector("button[type=submit]");
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm me-2"></span>Submitting...\';
    
    try {
        // ✅ Get form data and convert role
        const formData = new FormData(this);
        const roleValue = formData.get("target_role");
        
        // ✅ Convert lowercase role to display name for backend
        if (roleValue && roleMapToDisplay[roleValue]) {
            formData.set("target_role", roleMapToDisplay[roleValue]);
        }
        
        const response = await fetch(this.action, {
            method: "POST",
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: "success",
                title: "Application Submitted!",
                html: `
                    <p>Thank you, <strong>${formData.get("first_name")}</strong>!</p>
                    <p>Our HR team will review your application for the <strong>${formData.get("target_role")}</strong> position.</p>
                    <p class="text-muted mt-2">You will receive a confirmation email shortly.</p>
                `,
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = "?page=home";
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Submission Failed",
                text: result.message || "Please check your inputs and try again.",
                confirmButtonText: "OK"
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error("Submission error:", error);
        Swal.fire({
            icon: "error",
            title: "Connection Error",
            text: "Something went wrong. Please check your internet connection and try again.",
            confirmButtonText: "OK"
        });
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>
';

require_once __DIR__ . '/../../layouts/auth.php';