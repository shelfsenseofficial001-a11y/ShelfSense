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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css">
    <?= $additional_css ?? '' ?>
</head>
<body>
    <div class="d-flex">
        <div class="trainee-sidebar" id="traineeSidebar">
            <div class="sidebar-brand">
                <span class="brand-mark"></span>
                Shelf<span class="text-yellow">Sense</span>
                <span class="badge bg-warning ms-2 text-dark">Trainee</span>
            </div>

            <div class="sidebar-user">
                <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-person-fill text-dark"></i>
                </div>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Trainee') ?></div>
                    <small class="text-muted">Training for: <?= ucfirst(str_replace('_', ' ', $targetRole ?? 'N/A')) ?></small>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="?page=trainee_dashboard" class="nav-item <?= $activePage === 'trainee_dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> My Dashboard
                </a>

                <?php if ($isHrTrainee): ?>
                <a href="?page=hr_dashboard" class="nav-item <?= $activePage === 'hr_dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> HR Dashboard
                </a>
                <a href="?page=hr_applicants" class="nav-item <?= $activePage === 'hr_applicants' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i> Applicants
                    <span class="badge bg-danger ms-auto" id="pendingBadge">0</span>
                </a>
                <a href="?page=hr_interviews" class="nav-item <?= $activePage === 'hr_interviews' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-event-fill"></i> Interviews
                </a>
                <a href="?page=hr_trainees" class="nav-item <?= $activePage === 'hr_trainees' ? 'active' : '' ?>">
                    <i class="bi bi-mortarboard-fill"></i> Trainees
                </a>
                <a href="?page=hr_contracts" class="nav-item <?= $activePage === 'hr_contracts' ? 'active' : '' ?>">
                    <i class="bi bi-file-text-fill"></i> Contracts
                </a>
                <hr>
                <a href="?page=hr_schedules" class="nav-item <?= $activePage === 'hr_schedules' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> Schedules
                </a>
                <a href="?page=hr_attendance" class="nav-item <?= $activePage === 'hr_attendance' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-check-fill"></i> Attendance
                </a>
                <?php if ($targetRole === 'hr_head'): ?>
                <a href="?page=hr_attendance_review" class="nav-item <?= $activePage === 'hr_attendance_review' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i> Review
                </a>
                <?php endif; ?>
                <hr>
                <a href="?page=hr_payroll" class="nav-item <?= $activePage === 'hr_payroll' ? 'active' : '' ?>">
                    <i class="bi bi-cash-coin"></i> Payroll
                </a>
                <?php endif; ?>

                <?php if ($isCashierTrainee): ?>
                <a href="?page=pos_dashboard" class="nav-item <?= $activePage === 'pos_dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> POS Dashboard
                </a>
                <a href="?page=pos_checkout" class="nav-item <?= $activePage === 'pos_checkout' ? 'active' : '' ?>">
                    <i class="bi bi-cart-plus-fill"></i> Checkout
                </a>
                <a href="?page=pos_orders" class="nav-item <?= $activePage === 'pos_orders' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> Order History
                </a>
                <?php endif; ?>

                <?php if ($isFinanceTrainee): ?>
                <?php $financeDashboardPage = $targetRole === 'finance_head' ? 'finance_head_dashboard' : 'finance_staff_dashboard'; ?>
                <a href="?page=<?= $financeDashboardPage ?>" class="nav-item <?= $activePage === $financeDashboardPage ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Finance Dashboard
                </a>
                <?php endif; ?>

                <hr>
                <a href="?page=logout" class="nav-item text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
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
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['first_name'] ?? 'Trainee') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?page=profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?page=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/ShelfSense/public/assets/js/app.js"></script>
    <?= $additional_js ?? '' ?>

    <style>
        .trainee-sidebar {
            width: 250px;
            min-height: 100vh;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }
        .trainee-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 20px;
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
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .trainee-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }
        .trainee-sidebar .sidebar-nav {
            padding: 16px 12px;
        }
        .trainee-sidebar .sidebar-nav .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 2px;
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
            min-height: 100vh;
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