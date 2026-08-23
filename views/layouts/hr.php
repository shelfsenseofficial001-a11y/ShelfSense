<?php
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'ShelfSense HR'; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css">
    <?php echo $additional_css ?? ''; ?>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="hr-sidebar" id="hrSidebar">
            <div class="sidebar-brand">
                <span class="brand-mark"></span>
                Shelf<span class="text-yellow">Sense</span>
                <span class="badge bg-primary ms-2">HR</span>
            </div>

            <div class="sidebar-user">
                <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-person-fill text-dark"></i>
                </div>
                <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'HR Staff'); ?></div>
                    <small class="text-muted"><?php echo getRoleName($_SESSION['role'] ?? 'hr_staff'); ?></small>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="?page=hr_dashboard" class="nav-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="?page=hr_applicants" class="nav-item <?php echo $activePage === 'applicants' ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Applicants
                    <span class="badge bg-danger ms-auto" id="pendingBadge">0</span>
                </a>
                <a href="?page=hr_interviews" class="nav-item <?php echo $activePage === 'interviews' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-event-fill"></i> Interviews
                </a>
                <a href="?page=hr_trainees" class="nav-item <?php echo $activePage === 'trainees' ? 'active' : ''; ?>">
                    <i class="bi bi-mortarboard-fill"></i> Trainees
                </a>
                <a href="?page=hr_contracts" class="nav-item <?php echo $activePage === 'contracts' ? 'active' : ''; ?>">
                    <i class="bi bi-file-text-fill"></i> Contracts
                </a>
                <hr>
                <a href="?page=hr_schedules" class="nav-item <?php echo $activePage === 'schedules' ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i> Schedules
                </a>
                <a href="?page=hr_attendance" class="nav-item <?php echo $activePage === 'attendance' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check-fill"></i> Attendance
                </a>
                <?php if (Auth::isHRHead() || Auth::isOwner()): ?>
                <a href="?page=hr_attendance_review" class="nav-item <?php echo $activePage === 'attendance_review' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-check"></i> Review
                </a>
                <?php endif; ?>
                <hr>
                <a href="?page=hr_payroll" class="nav-item <?php echo $activePage === 'payroll' ? 'active' : ''; ?>">
                    <i class="bi bi-cash-coin"></i> Payroll
                </a>
                <hr>
                <a href="?page=my_leaves" class="nav-item <?php echo $activePage === 'my_leaves' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar2-week"></i> My Leaves
                </a>
                <a href="?page=my_payslip" class="nav-item <?php echo $activePage === 'payslip' ? 'active' : ''; ?>">
                    <i class="bi bi-wallet2"></i> My Payslip
                </a>
                <hr>
                <a href="?page=logout" class="nav-item text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="hr-content flex-grow-1">
            <!-- Top Bar -->
            <div class="hr-topbar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><?php echo $pageTitle ?? 'HR Dashboard'; ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Toggle -->
                    <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="position-relative" id="notificationContainer">
                        <button class="btn btn-link text-dark position-relative" id="notificationBell">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="badge bg-danger rounded-pill position-absolute top-0 end-0" id="notificationBadge" style="font-size:0.5rem;display:none;">0</span>
                        </button>
                        <div class="notification-dropdown" id="notificationDropdown" style="display:none;">
                            <div class="notification-header">Notifications</div>
                            <div id="notificationList">
                                <div class="text-center text-muted small py-3">No notifications</div>
                            </div>
                        </div>
                    </div>

                    <!-- User -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'HR'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?page=profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?page=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="hr-page-content">
                <?php echo $content ?? ''; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="/ShelfSense/public/assets/js/app.js"></script>
    <?php echo $additional_js ?? ''; ?>

    <!-- Searchable Select Component -->
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js"></script>

    <style>
        /* ============================================
           ORIGINAL HR LAYOUT STYLES (NO TRAINEE)
           ============================================ */

        .hr-sidebar {
            width: 250px;
            min-height: 100vh;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }

        .hr-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .hr-sidebar .sidebar-brand .brand-mark {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--brand-yellow);
            border-radius: 3px;
            margin-right: 6px;
        }

        .hr-sidebar .sidebar-brand .text-yellow {
            color: var(--brand-yellow);
        }

        .hr-sidebar .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .hr-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }

        .hr-sidebar .sidebar-nav {
            padding: 16px 12px;
        }

        .hr-sidebar .sidebar-nav .nav-item {
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

        .hr-sidebar .sidebar-nav .nav-item:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }

        .hr-sidebar .sidebar-nav .nav-item.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }

        .hr-sidebar .sidebar-nav .nav-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .hr-sidebar .sidebar-nav .nav-item .badge {
            font-size: 0.6rem;
            padding: 2px 8px;
        }

        .hr-sidebar .sidebar-nav hr {
            margin: 12px 0;
            border-color: var(--border-color);
        }

        .hr-content {
            padding: 0;
            min-height: 100vh;
            background: var(--bg-body);
        }

        .hr-topbar {
            padding: 16px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .hr-topbar h5 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
        }

        .hr-page-content {
            padding: 24px;
        }

        .notification-dropdown {
            position: absolute;
            top: 40px;
            right: 0;
            width: 320px;
            max-height: 400px;
            overflow-y: auto;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            z-index: 1000;
        }

        .notification-dropdown .notification-header {
            padding: 12px 16px;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }

        .notification-dropdown .notification-item {
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }

        .notification-dropdown .notification-item:hover {
            background: var(--light-yellow-subtle);
        }

        .autocomplete-wrapper {
            position: relative;
            width: 100%;
        }

        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .autocomplete-dropdown.show {
            display: block;
        }

        .autocomplete-dropdown .item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        }

        .autocomplete-dropdown .item:hover {
            background: var(--light-yellow-subtle);
        }

        .autocomplete-dropdown .item.selected {
            background: var(--light-yellow-subtle);
        }

        .autocomplete-dropdown .item .item-name {
            font-weight: 500;
        }

        .autocomplete-dropdown .item .item-email {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .autocomplete-dropdown .no-results {
            padding: 12px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .hr-sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                z-index: 1050;
                transition: left 0.3s ease;
                background: var(--bg-card);
            }
            .hr-sidebar.open {
                left: 0;
            }
            .hr-content {
                margin-left: 0;
            }
            .hr-topbar {
                padding: 12px 16px;
            }
            .hr-page-content {
                padding: 16px;
            }
        }
    </style>

    <script>
        document.getElementById('notificationBell')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function() {
            document.getElementById('notificationDropdown').style.display = 'none';
        });

        document.addEventListener('DOMContentLoaded', function() {
            const topbar = document.querySelector('.hr-topbar');
            if (topbar && window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-link text-dark p-0 me-2';
                toggleBtn.innerHTML = '<i class="bi bi-list fs-4"></i>';
                toggleBtn.addEventListener('click', function() {
                    document.getElementById('hrSidebar').classList.toggle('open');
                });
                topbar.prepend(toggleBtn);
            }
        });
    </script>
</body>
</html>