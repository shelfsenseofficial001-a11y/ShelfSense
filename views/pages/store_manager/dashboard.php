<?php
$title = 'Store Manager Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/dashboard.js?v=20260831440000"></script>';

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
$firstName = htmlspecialchars($_SESSION['first_name'] ?? 'there');
$today = date('d M Y');

$content = <<<EOT
<div class="sm-dash-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">{$greeting}, {$firstName}! <span>👋</span></h4>
        <p class="text-muted mb-0">Here's what's happening with your store today.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="sm-date-pill"><i class="bi bi-calendar3 me-1"></i> {$today}</div>
        <a href="?page=store_manager_requisitions&tab=create" class="btn btn-yellow-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New Requisition
        </a>
    </div>
</div>

<div id="dashboardContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading dashboard...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
