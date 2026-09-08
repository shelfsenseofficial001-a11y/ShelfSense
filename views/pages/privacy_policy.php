<?php
// views/pages/privacy_policy.php
// Public, no-login page explaining what personal and biometric data
// ShelfSense collects and why -- linked from the landing page footer,
// login, apply, and the POS attendance QR modal.

$title = 'Privacy Policy - ShelfSense';
$subtitle = 'Privacy Policy';

$content = '
<style>.auth-card { max-width: 760px !important; }</style>
<div class="privacy-policy-page">
    <div class="mb-3">
        <a href="?page=home" class="back-nav-btn">
            <i class="bi bi-arrow-left"></i>Back to home
        </a>
    </div>

    <h3 class="mb-1">Privacy Policy</h3>
    <p class="text-muted small mb-4">Last updated: September 2026</p>

    <p>ShelfSense is an internal retail operations platform used by employees, trainees, suppliers, and applicants.
    This page explains what personal information we collect, why, and how it is protected. It applies to every
    part of the system, including the applicant portal, staff accounts, and the point-of-sale register.</p>

    <h5 class="mt-4">What we collect</h5>
    <ul>
        <li><strong>Account details:</strong> name, email, employee number, role, and hashed password (we never store passwords in plain text).</li>
        <li><strong>Employment records:</strong> attendance, schedules, payroll, leave balances, and performance data, used to run HR and payroll processes.</li>
        <li><strong>Application data:</strong> resumes, contact details, and skill self-assessments submitted through the Apply page.</li>
        <li><strong>Profile photo:</strong> optional, shown across the portal and subject to approval.</li>
        <li><strong>Face ID (biometric) data:</strong> if you choose to enroll, we store numeric face measurements ("descriptors") derived from your camera image, plus a verification photo taken each time you clock in or out. This is used only to confirm attendance at the register and replaces manual sign-in sheets. Enrollment is optional, requires your explicit consent before any camera access, and you can remove it at any time from your Profile.</li>
    </ul>

    <h5 class="mt-4">How attendance face verification works</h5>
    <p>When a cashier is selected at a register, a face scan is required before the shift starts. This can happen on
    your own phone (by scanning a QR code shown at the register) or on the register itself. Either way:</p>
    <ul>
        <li>You are shown a consent screen before the camera is activated.</li>
        <li>Your browser computes face measurements locally; the comparison against your enrolled Face ID happens on our server, not in the browser.</li>
        <li>A short-lived scan link (QR code) expires after 5 minutes and can only be used once.</li>
        <li>Verification photos and match results are visible only to HR, for attendance recordkeeping and dispute resolution.</li>
    </ul>

    <h5 class="mt-4">Who can see your data</h5>
    <p>Access is role-restricted: HR staff can see employment and attendance records for HR purposes; Owners and
    Finance can see payroll and financial data relevant to their role; Store Managers and Suppliers see only what is
    relevant to inventory and procurement. Biometric data is visible only to HR and is never shared outside
    ShelfSense.</p>

    <h5 class="mt-4">Your choices</h5>
    <ul>
        <li>Face ID enrollment is optional and can be removed anytime from Profile &rarr; Attendance Face ID.</li>
        <li>You can request a copy or correction of your personal data by contacting HR.</li>
        <li>Applicants may withdraw an application by contacting HR before it is processed.</li>
    </ul>

    <h5 class="mt-4">Data retention & security</h5>
    <p>Data is stored on ShelfSense\'s own database and is not sold or shared with third parties. Attendance
    verification photos and face descriptors are retained only as long as needed for attendance recordkeeping or
    until you remove your enrollment. Passwords are hashed; sensitive actions require authentication.</p>

    <h5 class="mt-4">Contact</h5>
    <p>Questions about this policy or your data can be directed to HR at
    <a href="mailto:shelfsenseofficial001@gmail.com">shelfsenseofficial001@gmail.com</a> or
    <a href="tel:+639264550078">0926 455 0078</a>.</p>

    <p class="text-muted small mt-4">This is a student project (ShelfSense) built for academic purposes. This
    policy describes how the prototype is designed to handle data.</p>
</div>

<style>
.privacy-policy-page { max-width: 720px; margin: 0 auto; }
.privacy-policy-page h5 { font-weight: 700; }
.privacy-policy-page ul { padding-left: 1.2rem; }
.privacy-policy-page li { margin-bottom: 6px; }
</style>
';

require_once __DIR__ . '/../layouts/auth.php';
