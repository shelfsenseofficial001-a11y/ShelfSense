<?php
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'ShelfSense HR'; ?></title>
    <link rel="icon" type="image/png" href="/ShelfSense/public/assets/images/logo-black.png">

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
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260828222041">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/dashboard-theme.css?v=20260828222041">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/hr-theme.css?v=20260828222041">
    <?php echo $additional_css ?? ''; ?>
</head>
<body class="hr-theme dashboard-theme">
    <div class="dashboard-page">
    <div class="dashboard-shell">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="hr-sidebar" id="hrSidebar">
            <div class="sidebar-brand">
                <span>
                    <span class="brand-logo">
                        <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                        <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
                    </span>
                    <span class="brand-label">Shelf<span class="text-yellow">Sense</span></span>
                    <span class="badge bg-primary ms-2">HR</span>
                </span>
            </div>

            <!-- Profile block pinned to the top of the sidebar -->
            <div class="sidebar-user">
                <a href="?page=profile" class="user-profile-link" title="Profile">
                    <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-person-fill text-dark"></i>
                    </div>
                    <div class="user-info">
                        <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'HR Staff'); ?></div>
                        <small class="text-muted"><?php echo getRoleName($_SESSION['role'] ?? 'hr_staff'); ?></small>
                    </div>
                </a>
                <a href="?page=logout" class="user-logout-btn" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>

            <!-- Standalone collapse toggle — its own row, like a nav item -->
            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" type="button" title="Collapse">
                <span class="nav-icon-wrap"><i class="bi bi-chevron-left"></i></span>
                <span class="nav-label">Collapse</span>
            </button>

            <nav class="sidebar-nav">
                <div class="sidebar-divider sidebar-divider-first"><span class="sidebar-divider-label">Main</span></div>
                <a href="?page=hr_dashboard" class="nav-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" title="Dashboard">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">Dashboard</span>
                </a>
                <a href="?page=hr_applicants" class="nav-item <?php echo $activePage === 'applicants' ? 'active' : ''; ?>" title="Applicants">
                    <span class="nav-icon-wrap"><i class="bi bi-people-fill"></i><span class="badge bg-danger nav-badge" id="pendingBadge">0</span></span> <span class="nav-label">Applicants</span>
                </a>
                <a href="?page=hr_interviews" class="nav-item <?php echo $activePage === 'interviews' ? 'active' : ''; ?>" title="Interviews">
                    <span class="nav-icon-wrap"><i class="bi bi-calendar-event-fill"></i></span> <span class="nav-label">Interviews</span>
                </a>
                <a href="?page=hr_trainees" class="nav-item <?php echo $activePage === 'trainees' ? 'active' : ''; ?>" title="Trainees">
                    <span class="nav-icon-wrap"><i class="bi bi-mortarboard-fill"></i></span> <span class="nav-label">Trainees</span>
                </a>
                <a href="?page=hr_contracts" class="nav-item <?php echo $activePage === 'contracts' ? 'active' : ''; ?>" title="Contracts">
                    <span class="nav-icon-wrap"><i class="bi bi-file-text-fill"></i></span> <span class="nav-label">Contracts</span>
                </a>
                <a href="?page=hr_job_postings" class="nav-item <?php echo $activePage === 'job_postings' ? 'active' : ''; ?>" title="Job Postings">
                    <span class="nav-icon-wrap"><i class="bi bi-megaphone-fill"></i></span> <span class="nav-label">Job Postings</span>
                </a>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Attendance</span></div>
                <a href="?page=hr_schedules" class="nav-item <?php echo $activePage === 'schedules' ? 'active' : ''; ?>" title="Schedules">
                    <span class="nav-icon-wrap"><i class="bi bi-clock-history"></i></span> <span class="nav-label">Schedules</span>
                </a>
                <a href="?page=hr_attendance" class="nav-item <?php echo $activePage === 'attendance' ? 'active' : ''; ?>" title="Attendance">
                    <span class="nav-icon-wrap"><i class="bi bi-calendar-check-fill"></i></span> <span class="nav-label">Attendance</span>
                </a>
                <?php if (Auth::isHRHead() || Auth::isOwner()): ?>
                <a href="?page=hr_attendance_review" class="nav-item <?php echo $activePage === 'attendance_review' ? 'active' : ''; ?>" title="Review">
                    <span class="nav-icon-wrap"><i class="bi bi-clipboard-check"></i></span> <span class="nav-label">Review</span>
                </a>
                <?php endif; ?>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Payroll</span></div>
                <a href="?page=hr_payroll" class="nav-item <?php echo $activePage === 'payroll' ? 'active' : ''; ?>" title="Payroll">
                    <span class="nav-icon-wrap"><i class="bi bi-cash-coin"></i></span> <span class="nav-label">Payroll</span>
                </a>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Personal</span></div>
                <a href="?page=my_leaves" class="nav-item <?php echo $activePage === 'my_leaves' ? 'active' : ''; ?>" title="My Leaves">
                    <span class="nav-icon-wrap"><i class="bi bi-calendar2-week"></i></span> <span class="nav-label">My Leaves</span>
                </a>
                <a href="?page=my_payslip" class="nav-item <?php echo $activePage === 'payslip' ? 'active' : ''; ?>" title="My Payslip">
                    <span class="nav-icon-wrap"><i class="bi bi-wallet2"></i></span> <span class="nav-label">My Payslip</span>
                </a>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Account</span></div>
                <a href="?page=logout" class="nav-item text-danger" title="Logout">
                    <span class="nav-icon-wrap"><i class="bi bi-box-arrow-right"></i></span> <span class="nav-label">Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="hr-content flex-grow-1">
            <!-- Top Bar -->
            <div class="hr-topbar d-flex justify-content-between align-items-center">
                <div>
                    <div class="topbar-greeting">Hello, <span class="text-yellow"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'there'); ?></span>!</div>
                    <div class="topbar-subtitle">
                        <span class="topbar-page-label"><?php echo $pageTitle ?? 'HR Dashboard'; ?></span>
                        <span class="topbar-dot">•</span>
                        <span id="topbarDateTime"></span>
                    </div>
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
                </div>
            </div>

            <!-- Page Content -->
            <div class="hr-page-content">
                <?php echo $content ?? ''; ?>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="/ShelfSense/public/assets/js/app.js?v=20260828222041"></script>
    <?php echo $additional_js ?? ''; ?>

    <!-- Searchable Select Component -->
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260828222041"></script>

    <style>
        /* ============================================
           ORIGINAL HR LAYOUT STYLES (NO TRAINEE)
           ============================================ */

        .hr-sidebar {
            width: 250px;
            min-height: 100%;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }

        .hr-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 14px;
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
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .hr-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }

        .hr-sidebar .sidebar-user .user-profile-link {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
            text-decoration: none;
            color: inherit;
        }

        .hr-sidebar .sidebar-user .user-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .hr-sidebar .sidebar-user .user-info .fw-semibold {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hr-sidebar .sidebar-nav {
            padding: 10px 12px;
            flex: 1 1 auto;
        }

        .hr-sidebar .sidebar-nav .nav-item {
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

        .hr-sidebar .sidebar-nav hr {
            margin: 12px 0;
            border-color: var(--border-color);
        }

        .hr-content {
            padding: 0;
            min-height: 100%;
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
        // Live date/time in the topbar greeting
        (function() {
            const el = document.getElementById('topbarDateTime');
            if (!el) return;
            function tick() {
                const now = new Date();
                const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
                const timeStr = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
                el.textContent = dateStr + ' — ' + timeStr;
            }
            tick();
            setInterval(tick, 1000);
        })();

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