<?php
$title = 'Store Manager Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/dashboard.js"></script>';

$content = <<<'EOT'
<div id="dashboardContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading dashboard...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
