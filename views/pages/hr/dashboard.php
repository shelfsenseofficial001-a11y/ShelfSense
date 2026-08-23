<?php
$title = 'HR Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$content = '
<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Total Applicants</small>
                    <h3 class="mb-0" id="statTotal">0</h3>
                </div>
                <div class="icon-box-sm bg-light-yellow rounded">
                    <i class="bi bi-people text-yellow"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Pending Review</small>
                    <h3 class="mb-0 text-warning" id="statPending">0</h3>
                </div>
                <div class="icon-box-sm bg-light-yellow rounded">
                    <i class="bi bi-clock-history text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Interview Scheduled</small>
                    <h3 class="mb-0 text-info" id="statScheduled">0</h3>
                </div>
                <div class="icon-box-sm bg-light-yellow rounded">
                    <i class="bi bi-calendar-event text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="modern-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">Hired</small>
                    <h3 class="mb-0 text-success" id="statHired">0</h3>
                </div>
                <div class="icon-box-sm bg-light-yellow rounded">
                    <i class="bi bi-person-check text-success"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=hr_applicants&filter=pending" class="btn btn-yellow-primary btn-sm">📋 Review Pending</a>
                <a href="?page=hr_applicants" class="btn btn-yellow-outline btn-sm">👥 View All Applicants</a>
                <a href="?page=hr_interviews" class="btn btn-yellow-outline btn-sm">📅 View Interviews</a>
                <a href="?page=hr_trainees" class="btn btn-yellow-outline btn-sm">🎓 View Trainees</a>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3">
    <div class="col-lg-8">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-yellow me-2"></i>Monthly Applications</h6>
            <div style="height: 250px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-yellow me-2"></i>Pipeline</h6>
            <div style="height: 250px;">
                <canvas id="pipelineChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Interviews -->
<div class="row mt-3">
    <div class="col-12">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 text-yellow me-2"></i>Upcoming Interviews</h6>
            <div id="upcomingInterviews">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading interviews...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/ShelfSense/public/assets/js/hr/dashboard.js"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';