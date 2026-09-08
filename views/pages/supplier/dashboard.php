<?php
use App\Core\Auth;
use App\Core\Database;

define('SHELFSENSE_INTERNAL_INCLUDE', true);
require_once __DIR__ . '/../../../app/handlers/supplier/get_dashboard_stats.php';
$initialData = supplier_dashboard_build_data(Database::getInstance()->getConnection(), Auth::userId());
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$title = 'Supplier Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/supplier/dashboard.js?v=20260908600000"></script>'
    . '<script src="/ShelfSense/public/assets/js/supplier/dashboard-layout.js?v=20260905310000"></script>';
$additional_js .= '
<script>
window.dashboardTourReadyEvent = "sp-dashboard-rendered";
window.dashboardTourSteps = [
    {
        target: ".sidebar-nav",
        title: "Your navigation",
        desc: "Everything you need lives here -- Dashboard, Requisitions, Invoices, and Products."
    },
    {
        target: ".sp-stats-grid",
        title: "Quick stats",
        desc: "A snapshot of your business: pending requisitions, invoiced orders, orders ready to ship, and this month\'s revenue."
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

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>
<div id="dashboardContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading dashboard...</p>
    </div>
</div>';

require_once __DIR__ . '/../../layouts/supplier.php';
