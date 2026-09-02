<?php
$title = 'Store Manager Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/dashboard.js?v=20260902215115"></script>
<script src="/ShelfSense/public/assets/js/store_manager/dashboard-layout.js?v=20260902215937"></script>
<script src="/ShelfSense/public/assets/js/store_manager/dashboard-tour.js?v=20260902234130"></script>';

$content = <<<EOT
<div id="dashboardContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading dashboard...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
