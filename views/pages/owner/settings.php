<?php
use App\Core\Settings;

$initialData = [
    'test_mode' => Settings::isTestMode()
];
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$title = 'System Settings - Owner';
$pageTitle = 'System Settings';
$activePage = 'owner_settings';
$additional_js = '<script src="/ShelfSense/public/assets/js/owner/settings.js?v=20260919000000"></script>';

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-1"><i class="bi bi-toggle2-on text-yellow me-2"></i>Test Mode</h6>
            <p class="text-muted small mb-3">
                While on, no account is forced through the password-change / Face ID
                setup on first login, and POS cashier clock-in accepts password only
                (no face scan), for every role including trainees. For development and
                QA use only -- turn this back off before real use, since it removes the
                app\'s only identity re-check at POS clock-in.
            </p>
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch fs-4 m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="testModeToggle">
                </div>
                <span id="testModeStatus" class="fw-semibold"></span>
            </div>
            <div id="testModeMessage" class="mt-2"></div>
        </div>
    </div>
</div>
';

require_once __DIR__ . '/../../layouts/hr.php';
