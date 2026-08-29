<?php
use App\Core\Auth;

$isFinanceHead = Auth::isFinanceHead();
$isFinanceStaff = Auth::isFinanceStaff();
$isSuperAdmin = Auth::isSuperAdmin();
$role = Auth::role();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ShelfSense - Finance' ?></title>
    <link rel="icon" type="image/png" href="/ShelfSense/public/assets/images/logo-black.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260830020000">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/dashboard-theme.css?v=20260830000000">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/finance.css">
    <?= $additional_css ?? '' ?>
</head>
<body class="dashboard-theme">
    <div class="dashboard-page">
    <div class="dashboard-shell">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="finance-sidebar" id="financeSidebar">
            <div class="sidebar-brand">
                <span class="brand-logo">
                    <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                    <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
                </span>
                <span class="brand-label">Shelf<span class="text-yellow">Sense</span></span>
                <span class="badge bg-success ms-2">Finance</span>
            </div>

            <div class="sidebar-user">
                <a href="?page=profile" class="user-profile-link" title="Profile">
                    <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                        <?php if (!empty($_SESSION['profile_pic'])): ?>
                        <img src="/ShelfSense/public/<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" alt="Profile">
                        <?php else: ?>
                        <i class="bi bi-person-fill text-dark"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Finance Staff') ?></div>
                        <small class="text-muted"><?= getRoleName($_SESSION['role'] ?? 'finance_staff') ?></small>
                    </div>
                </a>
                <a href="?page=profile" class="user-edit-btn" title="Edit Profile">
                    <i class="bi bi-pencil-square"></i>
                </a>
            </div>

            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" type="button" title="Collapse">
                <span class="nav-icon-wrap"><i class="bi bi-chevron-left"></i></span>
                <span class="nav-label">Collapse</span>
            </button>

            <nav class="sidebar-nav">
                <?php if ($isFinanceStaff || $isSuperAdmin): ?>
                <div class="sidebar-divider sidebar-divider-first"><span class="sidebar-divider-label">Main</span></div>
                <a href="?page=finance_staff_dashboard" class="nav-item <?= $activePage === 'staff_dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">Dashboard</span>
                </a>
                <a href="?page=finance_staff_requisitions" class="nav-item <?= $activePage === 'staff_requisitions' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-clipboard-check"></i><span class="badge bg-danger nav-badge" id="pendingBadge">0</span></span> <span class="nav-label">Pending Requisitions</span>
                </a>
                <a href="?page=finance_staff_payment_requests" class="nav-item <?= $activePage === 'staff_payment_requests' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-cash"></i></span> <span class="nav-label">My Payment Requests</span>
                </a>
                <a href="?page=finance_staff_budget" class="nav-item <?= $activePage === 'staff_budget' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-pie-chart"></i></span> <span class="nav-label">Budget View</span>
                </a>
                <?php endif; ?>

                <?php if ($isFinanceHead || $isSuperAdmin): ?>
                <div class="sidebar-divider<?= (!$isFinanceStaff && !$isSuperAdmin) ? ' sidebar-divider-first' : '' ?>"><?= (!$isFinanceStaff && !$isSuperAdmin) ? '' : '<hr>' ?><span class="sidebar-divider-label">Management</span></div>
                <a href="?page=finance_head_dashboard" class="nav-item <?= $activePage === 'head_dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">Head Dashboard</span>
                </a>
                <a href="?page=finance_head_payment_requests" class="nav-item <?= $activePage === 'head_payment_requests' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-check-circle"></i><span class="badge bg-danger nav-badge" id="headPendingBadge">0</span></span> <span class="nav-label">Approve Payments</span>
                </a>
                <a href="?page=finance_head_budget" class="nav-item <?= $activePage === 'head_budget' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-pie-chart"></i></span> <span class="nav-label">Budget</span>
                </a>
                <?php endif; ?>

                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Personal</span></div>
                <a href="?page=my_leaves" class="nav-item <?= $activePage === 'my_leaves' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-calendar2-week"></i></span> <span class="nav-label">My Leaves</span>
                </a>
                <a href="?page=my_payslip" class="nav-item <?= $activePage === 'payslip' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-wallet2"></i></span> <span class="nav-label">My Payslip</span>
                </a>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Account</span></div>
                <a href="?page=logout" class="nav-item text-danger">
                    <span class="nav-icon-wrap"><i class="bi bi-box-arrow-right"></i></span> <span class="nav-label">Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="finance-content flex-grow-1">
            <div class="finance-topbar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><?= $pageTitle ?? 'Finance Dashboard' ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="finance-page-content">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/ShelfSense/public/assets/js/app.js?v=20260830000000"></script>
    <script src="/ShelfSense/public/assets/js/finance/staff/shared.js"></script>
    <?= $additional_js ?? '' ?>
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260830000000"></script>

    <style>
        .finance-sidebar {
            width: 250px;
            min-height: 100%;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }
        .finance-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .finance-sidebar .sidebar-brand .brand-mark {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--brand-yellow);
            border-radius: 3px;
            margin-right: 6px;
        }
        .finance-sidebar .sidebar-brand .text-yellow {
            color: var(--brand-yellow);
        }
        .finance-sidebar .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .finance-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }
        .finance-sidebar .sidebar-nav {
            padding: 10px 12px;
        }
        .finance-sidebar .sidebar-nav .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 7px 14px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 1px;
        }
        .finance-sidebar .sidebar-nav .nav-item:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }
        .finance-sidebar .sidebar-nav .nav-item.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }
        .finance-sidebar .sidebar-nav .nav-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        .finance-sidebar .sidebar-nav hr {
            margin: 12px 0;
            border-color: var(--border-color);
        }
        .finance-content {
            padding: 0;
            min-height: 100%;
            background: var(--bg-body);
        }
        .finance-topbar {
            padding: 16px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .finance-topbar h5 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
        }
        .finance-page-content {
            padding: 24px;
        }

        /* Same stat cards as HR */
        .stat-card {
            padding: 16px 20px;
            border-radius: 12px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            cursor: default;
        }
        .stat-card:hover {
            border-color: var(--brand-yellow);
            transform: translateY(-2px);
        }
        .stat-card .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .stat-card .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .stat-card .stat-icon {
            font-size: 1.8rem;
        }
        .stat-card .stat-number.warning { color: #d97706; }
        .stat-card .stat-number.success { color: #059669; }
        .stat-card .stat-number.danger { color: #dc2626; }
        .stat-card .stat-number.primary { color: #2563eb; }

        /* Same status badges as other modules */
        .status-badge {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 12px;
        }
        .status-badge.awaiting_finance_staff { background: #dbeafe; color: #1e40af; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.approved { background: #d1fae5; color: #065f46; }
        .status-badge.rejected { background: #fecaca; color: #991b1b; }
        .status-badge.finance_approved { background: #d1fae5; color: #065f46; }
        .status-badge.finance_rejected { background: #fecaca; color: #991b1b; }
        .status-badge.paid { background: #d1fae5; color: #065f46; }
        .status-badge.completed { background: #d1fae5; color: #065f46; }

        [data-bs-theme="dark"] .status-badge.awaiting_finance_staff { background: #1e3a5f; color: #93c5fd; }
        [data-bs-theme="dark"] .status-badge.pending { background: #78350f; color: #fcd34d; }
        [data-bs-theme="dark"] .status-badge.approved { background: #064e3b; color: #6ee7b7; }
        [data-bs-theme="dark"] .status-badge.rejected { background: #7f1d1d; color: #fca5a5; }

        @media (max-width: 768px) {
            .finance-sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                z-index: 1050;
                transition: left 0.3s ease;
                background: var(--bg-card);
            }
            .finance-sidebar.open {
                left: 0;
            }
            .finance-content {
                margin-left: 0;
            }
            .finance-topbar {
                padding: 12px 16px;
            }
            .finance-page-content {
                padding: 16px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const topbar = document.querySelector('.finance-topbar');
            if (topbar && window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-link text-dark p-0 me-2';
                toggleBtn.innerHTML = '<i class="bi bi-list fs-4"></i>';
                toggleBtn.addEventListener('click', function() {
                    document.getElementById('financeSidebar').classList.toggle('open');
                });
                topbar.prepend(toggleBtn);
            }
        });
    </script>
</body>
</html>