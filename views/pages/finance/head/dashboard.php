<?php
$title = 'Finance Head Dashboard';
$pageTitle = 'Head Dashboard';
$activePage = 'head_dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/head/dashboard.js?v=20260901010000"></script>';
$additional_js .= '
<script>
window.dashboardTourReadyEvent = "fn-head-dashboard-rendered";
window.dashboardTourSteps = [
    {
        target: ".sidebar-nav",
        title: "Your navigation",
        desc: "Everything you need lives here -- Dashboard, Payment Requests, Budget, and Approval History."
    },
    {
        target: "#fnHeadStatsRow",
        title: "Quick stats",
        desc: "A snapshot of finance: pending payment requests, what\'s been approved or rejected this month, and overall budget usage."
    },
    {
        target: "#fnHeadBudgetCard",
        title: "Budget overview",
        desc: "See every department\'s budget usage at a glance, with warnings when a department is nearing its limit."
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