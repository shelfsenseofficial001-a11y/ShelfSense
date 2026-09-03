<?php
$title = 'Finance Staff Dashboard';
$pageTitle = 'Finance Staff Dashboard';
$activePage = 'staff_dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/staff/dashboard.js"></script>';
$additional_js .= '
<script>
window.dashboardTourReadyEvent = "fn-staff-dashboard-rendered";
window.dashboardTourSteps = [
    {
        target: ".sidebar-nav",
        title: "Your navigation",
        desc: "Everything you need lives here -- Dashboard, Requisitions, Payment Requests, and Budget."
    },
    {
        target: ".fn-stats-grid",
        title: "Quick stats",
        desc: "A snapshot of your workload: requisitions to review, pending payment requests, available budget, and anything over budget."
    },
    {
        target: ".user-edit-btn",
        fallbackTarget: ".user-profile-link",
        title: "You’re all set!",
        desc: "You can turn this tour back on or off anytime -- click here to open your Profile, then look for \"Preferences\". Enjoy exploring the dashboard!"
    }
];
</script>
<script src="/ShelfSense/public/assets/js/shared/dashboard-tour.js?v=20260903100000"></script>';

$content = <<<'EOT'
<div id="dashboardContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading dashboard...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';
