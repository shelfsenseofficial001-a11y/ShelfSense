<?php
use App\Core\Auth;

$targetRole = Auth::getNormalizedTargetRole();
$isHrTrainee = in_array($targetRole, ['hr_head', 'hr_staff']);
$isCashierTrainee = $targetRole === 'cashier';
$isFinanceTrainee = in_array($targetRole, ['finance_head', 'finance_staff']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ShelfSense - Trainee' ?></title>
    <link rel="icon" type="image/png" href="/ShelfSense/public/assets/images/logo-black.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260830122553">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/dashboard-theme.css?v=20260830122553">
    <?= $additional_css ?? '' ?>
</head>
<body class="dashboard-theme">
    <div class="dashboard-page">
    <div class="dashboard-shell">
    <div class="d-flex">
        <div class="trainee-sidebar" id="traineeSidebar">
            <div class="sidebar-brand">
                <span class="brand-logo">
                    <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                    <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
                </span>
                <span class="brand-label">Shelf<span class="text-yellow">Sense</span></span>
                <span class="badge bg-warning ms-2 text-dark">Trainee</span>
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
                        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Trainee') ?></div>
                        <small class="text-muted">Training for: <?= ucfirst(str_replace('_', ' ', $targetRole ?? 'N/A')) ?></small>
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
                <div class="sidebar-divider sidebar-divider-first"><span class="sidebar-divider-label">Main</span></div>
                <a href="?page=trainee_dashboard" class="nav-item <?= $activePage === 'trainee_dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">My Dashboard</span>
                </a>

                <?php if ($isHrTrainee): ?>
                <a href="?page=hr_dashboard" class="nav-item <?= $activePage === 'hr_dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">HR Dashboard</span>
                </a>
                <a href="?page=hr_applicants" class="nav-item <?= $activePage === 'hr_applicants' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-people-fill"></i><span class="badge bg-danger nav-badge" id="pendingBadge">0</span></span> <span class="nav-label">Applicants</span>
                </a>
                <a href="?page=hr_interviews" class="nav-item <?= $activePage === 'hr_interviews' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-calendar-event-fill"></i></span> <span class="nav-label">Interviews</span>
                </a>
                <a href="?page=hr_trainees" class="nav-item <?= $activePage === 'hr_trainees' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-mortarboard-fill"></i></span> <span class="nav-label">Trainees</span>
                </a>
                <a href="?page=hr_contracts" class="nav-item <?= $activePage === 'hr_contracts' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-file-text-fill"></i></span> <span class="nav-label">Contracts</span>
                </a>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Attendance</span></div>
                <a href="?page=hr_schedules" class="nav-item <?= $activePage === 'hr_schedules' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-clock-history"></i></span> <span class="nav-label">Schedules</span>
                </a>
                <a href="?page=hr_attendance" class="nav-item <?= $activePage === 'hr_attendance' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-calendar-check-fill"></i></span> <span class="nav-label">Attendance</span>
                </a>
                <?php if ($targetRole === 'hr_head'): ?>
                <a href="?page=hr_attendance_review" class="nav-item <?= $activePage === 'hr_attendance_review' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-clipboard-check"></i></span> <span class="nav-label">Review</span>
                </a>
                <?php endif; ?>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Payroll</span></div>
                <a href="?page=hr_payroll" class="nav-item <?= $activePage === 'hr_payroll' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-cash-coin"></i></span> <span class="nav-label">Payroll</span>
                </a>
                <?php endif; ?>

                <?php if ($isCashierTrainee): ?>
                <a href="?page=pos_dashboard" class="nav-item <?= $activePage === 'pos_dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">POS Dashboard</span>
                </a>
                <a href="?page=pos_checkout" class="nav-item <?= $activePage === 'pos_checkout' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-cart-plus-fill"></i></span> <span class="nav-label">Checkout</span>
                </a>
                <a href="?page=pos_orders" class="nav-item <?= $activePage === 'pos_orders' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-clock-history"></i></span> <span class="nav-label">Order History</span>
                </a>
                <?php endif; ?>

                <?php if ($isFinanceTrainee): ?>
                <?php $financeDashboardPage = $targetRole === 'finance_head' ? 'finance_head_dashboard' : 'finance_staff_dashboard'; ?>
                <a href="?page=<?= $financeDashboardPage ?>" class="nav-item <?= $activePage === $financeDashboardPage ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">Finance Dashboard</span>
                </a>
                <?php endif; ?>

                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Account</span></div>
                <a href="?page=logout" class="nav-item text-danger">
                    <span class="nav-icon-wrap"><i class="bi bi-box-arrow-right"></i></span> <span class="nav-label">Logout</span>
                </a>
            </nav>
        </div>

        <div class="trainee-content flex-grow-1">
            <div class="trainee-topbar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><?= $pageTitle ?? 'Trainee Dashboard' ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="trainee-page-content">
                <?= $content ?? '' ?>
            </div>

            <?php $flash = getFlash(); ?>
            <?php if ($flash): ?>
                <div class="flash-message <?= $flash['type'] ?> mt-3">
                    <?= escape($flash['message']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/ShelfSense/public/assets/js/app.js?v=20260830122553"></script>
    <?= $additional_js ?? '' ?>

    <style>
        .trainee-sidebar {
            width: 250px;
            min-height: 100%;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }
        .trainee-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .trainee-sidebar .sidebar-brand .brand-mark {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--brand-yellow);
            border-radius: 3px;
            margin-right: 6px;
        }
        .trainee-sidebar .sidebar-brand .text-yellow {
            color: var(--brand-yellow);
        }
        .trainee-sidebar .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .trainee-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }
        .trainee-sidebar .sidebar-nav {
            padding: 10px 12px;
        }
        .trainee-sidebar .sidebar-nav .nav-item {
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
        .trainee-sidebar .sidebar-nav .nav-item:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }
        .trainee-sidebar .sidebar-nav .nav-item.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }
        .trainee-sidebar .sidebar-nav .nav-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        .trainee-sidebar .sidebar-nav hr {
            margin: 12px 0;
            border-color: var(--border-color);
        }
        .trainee-content {
            padding: 0;
            min-height: 100%;
            background: var(--bg-body);
        }
        .trainee-topbar {
            padding: 12px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .trainee-topbar h5 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
        }
        .trainee-page-content {
            padding: 20px 24px;
        }
        .flash-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .flash-message.success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .flash-message.error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c6cb;
        }
        .flash-message.warning {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
        }
        .flash-message.info {
            background: #cff4fc;
            color: #055160;
            border: 1px solid #b6effb;
        }
        @media (max-width: 768px) {
            .trainee-sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                z-index: 1050;
                transition: left 0.3s ease;
                background: var(--bg-card);
            }
            .trainee-sidebar.open {
                left: 0;
            }
            .trainee-content {
                margin-left: 0;
            }
            .trainee-topbar {
                padding: 10px 16px;
            }
            .trainee-page-content {
                padding: 12px 16px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const topbar = document.querySelector('.trainee-topbar');
            if (topbar && window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-link text-dark p-0 me-2';
                toggleBtn.innerHTML = '<i class="bi bi-list fs-4"></i>';
                toggleBtn.addEventListener('click', function() {
                    document.getElementById('traineeSidebar').classList.toggle('open');
                });
                topbar.prepend(toggleBtn);
            }
        });
    </script>
</body>
</html>