<?php
$title = 'Trainee Dashboard - ShelfSense';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$additional_js = '<script src="/ShelfSense/public/assets/js/trainee/dashboard.js"></script>';
$additional_js .= '
<script>
window.dashboardTourReadyEvent = "trainee-dashboard-rendered";
window.dashboardTourSteps = [
    {
        target: ".sidebar-nav",
        title: "Your navigation",
        desc: "Everything you need lives here -- Dashboard, your training module, Leaves, and Payslip."
    },
    {
        target: "#traineeStatsRow",
        title: "Your progress",
        desc: "Days remaining in training, report progress, your trainer, and your schedule -- all at a glance."
    },
    {
        target: "#traineeModuleCard",
        title: "Your training module",
        desc: "Jump straight into your current training module from here."
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
$additional_css = '
<style>
    .trainee-stat-card {
        padding: 16px 20px;
        border-radius: 12px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
    }
    .trainee-stat-card:hover {
        border-color: var(--brand-yellow);
        transform: translateY(-2px);
    }
    .trainee-stat-card .stat-icon {
        font-size: 1.8rem;
    }
    .trainee-stat-card .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
    }
    .trainee-stat-card .stat-label {
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .trainee-stat-card .stat-number.success { color: #059669; }
    .trainee-stat-card .stat-number.warning { color: #d97706; }
    .trainee-stat-card .stat-number.danger { color: #dc2626; }
    .trainee-stat-card .stat-number.primary { color: #2563eb; }
    
    .trainee-progress {
        background: var(--bg-card-subtle);
        border-radius: 8px;
        padding: 4px;
    }
    .trainee-progress .progress-bar {
        height: 8px;
        border-radius: 4px;
        background: var(--brand-yellow);
        transition: width 0.6s ease;
    }
    .report-item {
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .report-item.completed {
        border-color: #059669;
        background: #d1fae5;
    }
    .report-item.pending {
        border-color: #d97706;
        background: #fef3c7;
    }
    [data-bs-theme="dark"] .report-item.completed {
        background: #064e3b;
        border-color: #059669;
    }
    [data-bs-theme="dark"] .report-item.pending {
        background: #78350f;
        border-color: #d97706;
    }
    .leave-balance-mini {
        font-size: 0.9rem;
    }
    .leave-balance-mini .badge {
        font-size: 0.7rem;
    }
    .module-card {
        border-left: 4px solid var(--brand-yellow);
    }
</style>
';

$content = <<<'EOT'
<div id="dashboardContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading your dashboard...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/trainee.php';