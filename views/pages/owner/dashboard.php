<?php
$title = 'Owner Dashboard - ShelfSense';
$pageTitle = 'Owner Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/owner/dashboard.js"></script>';

$content = '
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-flask"></i>
    <span><strong>Prototype:</strong> this Owner dashboard is an early, minimal overview for testing the Owner role end-to-end. Use the sidebar to access the full HR, recruitment, and training modules.</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <small class="text-muted">Total Applicants</small>
            <h3 class="mb-0" id="ownerStatApplicants">0</h3>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <small class="text-muted">Active Trainees</small>
            <h3 class="mb-0 text-warning" id="ownerStatTrainees">0</h3>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <small class="text-muted">Employees Hired</small>
            <h3 class="mb-0 text-success" id="ownerStatHired">0</h3>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <small class="text-muted">Job Postings Awaiting Approval</small>
            <h3 class="mb-0 text-primary" id="ownerStatPendingPostings">0</h3>
        </div>
    </div>
</div>

<div class="modern-card">
    <div class="card-header">
        <strong><i class="bi bi-calendar-check"></i> Final Interviews Requiring Your Presence</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Applicant</th><th>Role</th><th>Date &amp; Time</th><th>Meet Link</th></tr></thead>
                <tbody id="ownerFinalInterviewsBody">
                    <tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
';

require_once __DIR__ . '/../../layouts/hr.php';
