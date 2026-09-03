<?php
$title = 'Store Manager Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/dashboard.js?v=20260902215115"></script>
<script src="/ShelfSense/public/assets/js/store_manager/dashboard-layout.js?v=20260902215937"></script>
<script>
window.dashboardTourReadyEvent = "sm-dashboard-rendered";
window.dashboardTourSteps = [
    {
        target: ".sidebar-nav",
        title: "Your navigation",
        desc: "Everything you need lives here -- Dashboard, Requisitions, Inventory, Budget, plus your personal Leaves and Payslip pages."
    },
    {
        target: "#smDashCanvasStats",
        title: "Quick stats",
        desc: "A snapshot of your store: total requisitions, what’s pending with the supplier, what’s awaiting finance, and any low-stock items."
    },
    {
        target: "#smDashCanvasContent",
        title: "Requisitions & charts",
        desc: "Live tables and charts covering your requisitions, low stock, and trends over time -- all in one place, no need to click into another page."
    },
    {
        target: "#dashEditModeBtn",
        title: "Make it yours",
        desc: "Click \"Edit UI\" to drag and reorder every card above into whatever layout works best for you. Your arrangement is saved automatically."
    },
    {
        target: ".sm-fab",
        title: "New Requisition",
        desc: "This button is always here in the corner -- click it anytime to jump straight into creating a new requisition."
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

$content = <<<EOT
<div id="dashboardContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading dashboard...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
