<?php
$title = 'HR Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$content = '
<!-- Dashboard Canvas: each row below is its own drag-reorderable zone
     (stats / mini-tables / charts). Order is user-customizable (see
     dashboard-layout.js) and persisted per account via
     api_save_dashboard_layout / api_get_dashboard_layout. -->

<div class="row g-3 mb-4 dash-canvas-row" id="dashCanvasStats" data-widget-group="stats">

    <div class="col-6 col-lg-3 dash-widget" data-widget-id="stat_total">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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

    <div class="col-6 col-lg-3 dash-widget" data-widget-id="stat_pending">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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

    <div class="col-6 col-lg-3 dash-widget" data-widget-id="stat_scheduled">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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

    <div class="col-6 col-lg-3 dash-widget" data-widget-id="stat_hired">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
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

<div class="row g-3 mb-4 dash-canvas-row" id="dashCanvasTables" data-widget-group="content">

    <div class="col-lg-4 dash-widget" data-widget-id="table_applicants">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 d-flex align-items-center">
                    <i class="bi bi-people-fill text-yellow me-2"></i>Applicants
                    <span class="badge bg-danger rounded-pill card-title-badge" id="dashApplicantsCountBadge" style="display:none;">0</span>
                </h6>
                <a href="?page=hr_applicants" class="btn btn-yellow-outline btn-sm">View All</a>
            </div>
            <div class="mini-table-scroll">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Name</th><th>Role</th><th>Status</th></tr>
                    </thead>
                    <tbody id="dashApplicantsBody">
                        <tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4 dash-widget" data-widget-id="table_interviews">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 d-flex align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-calendar-event-fill text-yellow me-2"></i>Interviews</span>
                    <span class="badge card-title-note-badge" id="dashInterviewsDueBadge" style="display:none;"></span>
                </h6>
                <a href="?page=hr_interviews" class="btn btn-yellow-outline btn-sm">View All</a>
            </div>
            <div class="mini-table-scroll">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Applicant</th><th>Type</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody id="dashInterviewsBody">
                        <tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4 dash-widget" data-widget-id="table_trainees">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-mortarboard-fill text-yellow me-2"></i>Trainees</h6>
                <a href="?page=hr_trainees" class="btn btn-yellow-outline btn-sm">View All</a>
            </div>
            <div class="mini-table-scroll">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Trainee</th><th>Trainer</th><th>Status</th></tr>
                    </thead>
                    <tbody id="dashTraineesBody">
                        <tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8 d-flex dash-widget" data-widget-id="chart_monthly">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
        <div class="modern-card p-3 h-100 w-100 d-flex flex-column">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-yellow me-2"></i>Monthly Applications</h6>
            <div class="chart-wrap">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 d-flex dash-widget" data-widget-id="chart_pipeline">
        <span class="dash-widget-handle"><i class="bi bi-grip-vertical"></i></span>
        <div class="modern-card p-3 h-100 w-100 d-flex flex-column">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-yellow me-2"></i>Pipeline</h6>
            <div class="chart-wrap">
                <canvas id="pipelineChart"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="/ShelfSense/public/assets/js/hr/dashboard.js?v=20260830122553"></script>
<script src="/ShelfSense/public/assets/js/hr/dashboard-layout.js?v=20260830123737"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';
