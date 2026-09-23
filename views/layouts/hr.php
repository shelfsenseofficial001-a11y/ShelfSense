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
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260918270000">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/dashboard-theme.css?v=20260921110000">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/hr-theme.css?v=20260923160000">
    <?php echo $additional_css ?? ''; ?>
</head>
<body class="hr-theme dashboard-theme">
    <div class="dashboard-page">
    <div class="dashboard-shell">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="hr-sidebar" id="hrSidebar">
            <div class="sidebar-brand">
                <span class="brand-logo">
                    <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                    <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
                </span>
                <span class="brand-label">Shelf<span class="text-yellow">Sense</span></span>
                <span class="badge bg-primary ms-2"><?php echo Auth::isOwner() ? 'Owner' : 'HR'; ?></span>
            </div>

            <!-- Standalone collapse toggle — its own row, like a nav item -->
            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" type="button" title="Collapse">
                <span class="nav-icon-wrap"><i class="bi bi-chevron-left"></i></span>
                <span class="nav-label">Collapse</span>
            </button>

            <nav class="sidebar-nav">
                <div class="sidebar-divider sidebar-divider-first"><span class="sidebar-divider-label">Main</span></div>
                <a href="<?php echo Auth::isOwner() ? '?page=owner_dashboard' : '?page=hr_dashboard'; ?>" class="nav-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" title="Dashboard">
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
                <?php $jpGroupActive = in_array($activePage, ['job_postings', 'job_posting_approvals'], true); ?>
                <?php if (Auth::isHRHead() || Auth::isOwner()): ?>
                <div class="nav-group <?php echo $jpGroupActive ? 'nav-group-open' : ''; ?>">
                    <button type="button" class="nav-item nav-group-toggle <?php echo $jpGroupActive ? 'active' : ''; ?>" title="Job Postings">
                        <span class="nav-icon-wrap"><i class="bi bi-megaphone-fill"></i></span>
                        <span class="nav-label">Job Postings</span>
                        <span class="nav-group-chevron"><i class="bi bi-chevron-down"></i></span>
                    </button>
                    <div class="nav-submenu">
                        <a href="?page=hr_job_postings" class="nav-subitem <?php echo $activePage === 'job_postings' ? 'active' : ''; ?>">
                            <i class="bi bi-list-ul"></i> All Postings
                        </a>
                        <a href="?page=hr_job_posting_approvals" class="nav-subitem <?php echo $activePage === 'job_posting_approvals' ? 'active' : ''; ?>">
                            <i class="bi bi-patch-check-fill"></i> Approvals
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="?page=hr_job_postings" class="nav-item <?php echo $activePage === 'job_postings' ? 'active' : ''; ?>" title="Job Postings">
                    <span class="nav-icon-wrap"><i class="bi bi-megaphone-fill"></i></span> <span class="nav-label">Job Postings</span>
                </a>
                <?php endif; ?>
                <a href="?page=hr_recruitment_calendar" class="nav-item <?php echo $activePage === 'recruitment_calendar' ? 'active' : ''; ?>" title="Recruitment Calendar">
                    <span class="nav-icon-wrap"><i class="bi bi-calendar3"></i></span> <span class="nav-label">Recruitment Calendar</span>
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
                <?php if (Auth::isOwner()): ?>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Owner</span></div>
                <a href="?page=avatar_approvals" class="nav-item <?php echo $activePage === 'avatar_approvals' ? 'active' : ''; ?>" title="Profile Picture Approvals">
                    <span class="nav-icon-wrap"><i class="bi bi-person-check-fill"></i></span> <span class="nav-label">Photo Approvals</span>
                </a>
                <a href="?page=owner_pos_accounts" class="nav-item <?php echo $activePage === 'pos_accounts' ? 'active' : ''; ?>" title="POS Accounts">
                    <span class="nav-icon-wrap"><i class="bi bi-credit-card-2-front"></i></span> <span class="nav-label">POS Accounts</span>
                </a>
                <a href="?page=owner_product_proposals" class="nav-item <?php echo $activePage === 'product_proposals' ? 'active' : ''; ?>" title="Product Proposals">
                    <span class="nav-icon-wrap"><i class="bi bi-lightbulb"></i></span> <span class="nav-label">Product Proposals</span>
                </a>
                <a href="?page=owner_settings" class="nav-item <?php echo $activePage === 'owner_settings' ? 'active' : ''; ?>" title="System Settings">
                    <span class="nav-icon-wrap"><i class="bi bi-gear-fill"></i></span> <span class="nav-label">Settings</span>
                </a>
                <?php endif; ?>
            </nav>

            <!-- Profile block pinned to the bottom of the sidebar -- click
                 the up-chevron to reveal My Leaves / My Payslip / Edit
                 Profile / Logout in a menu that opens upward above it. -->
            <div class="sidebar-user" id="sidebarUserBlock">
                <a href="?page=profile" class="user-profile-link" title="Profile">
                    <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                        <?php if (!empty($_SESSION['profile_pic'])): ?>
                        <img src="/ShelfSense/public/<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" alt="Profile">
                        <?php else: ?>
                        <i class="bi bi-person-fill text-dark"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Staff'); ?></div>
                        <small class="text-muted"><?php echo getRoleName($_SESSION['role'] ?? (Auth::isOwner() ? 'owner' : 'hr_staff')); ?></small>
                    </div>
                </a>
                <button type="button" class="user-menu-toggle" id="sidebarUserMenuToggle" title="More">
                    <i class="bi bi-chevron-up"></i>
                </button>
                <div class="sidebar-user-menu" id="sidebarUserMenu">
                    <a href="?page=my_leaves" class="sidebar-user-menu-item <?php echo $activePage === 'my_leaves' ? 'active' : ''; ?>">
                        <i class="bi bi-calendar2-week"></i> My Leaves
                    </a>
                    <a href="?page=profile" class="sidebar-user-menu-item">
                        <i class="bi bi-pencil-square"></i> Edit Profile
                    </a>
                    <div class="sidebar-user-menu-divider"></div>
                    <a href="?page=logout" class="sidebar-user-menu-item text-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="hr-content flex-grow-1<?php echo $activePage === 'dashboard' ? ' hr-content-fit' : ''; ?>">
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
                    <?php if ($activePage === 'dashboard'): ?>
                    <!-- Dashboard Edit Mode -->
                    <span class="dash-edit-hint" id="dashEditHint">
                        Press <kbd class="dash-kbd dash-kbd-enter">Enter&nbsp;&#9166;</kbd> to save, <kbd class="dash-kbd">Esc</kbd> to close
                    </span>
                    <button class="dash-edit-btn" id="dashEditModeBtn" aria-label="Rearrange dashboard widgets" type="button">
                        <i class="bi bi-pencil-fill"></i>
                        <span class="dash-edit-label">Edit UI</span>
                    </button>
                    <?php endif; ?>

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
                            <div class="notification-header">Notifications <a href="#" id="notificationMarkAllRead" class="float-end small">Mark all read</a></div>
                            <div class="notification-filter-tabs" id="notificationFilterTabs">
                                <button type="button" class="notif-filter-btn active" data-filter="all">All</button>
                                <button type="button" class="notif-filter-btn" data-filter="unread">Unread</button>
                                <button type="button" class="notif-filter-btn" data-filter="read">Read</button>
                            </div>
                            <div id="notificationList">
                                <div class="text-center text-muted small py-3">No notifications</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="hr-page-content<?php echo $activePage === 'dashboard' ? ' hr-page-content-fit' : ''; ?>">
                <?php echo $content ?? ''; ?>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Dashboard "Saved!" toast, shown bottom-center (same spot as the
         Keep/Revert prompt above) when edit mode is turned off -->
    <div class="dash-saved-toast-container">
        <div id="dashSavedToast" class="toast align-items-center border-0 dash-saved-toast" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="1800">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i>Saved!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Dashboard "Keep changes?" confirmation, shown when exiting edit
         mode -- like Windows' "Keep these display settings?" prompt.
         5-second countdown; if unanswered, the change is KEPT. -->
    <div class="dash-revert-confirm" id="dashRevertConfirm" role="alertdialog" aria-live="assertive">
        <div class="dash-revert-text">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <span>Keep the new dashboard layout?</span>
        </div>
        <div class="dash-revert-actions">
            <button type="button" class="dash-revert-btn dash-revert-undo">Revert</button>
            <button type="button" class="dash-revert-btn dash-revert-keep">
                Keep Changes <span class="dash-revert-countdown">5</span>
            </button>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="/ShelfSense/public/assets/js/app.js?v=20260910100000"></script>
    <?php echo $additional_js ?? ''; ?>

    <!-- Searchable Select Component -->
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260908530000"></script>

    <!-- Date Picker Modal Component -->
    <script src="/ShelfSense/public/assets/js/components/date-picker-modal.js?v=20260920000000"></script>

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

        /* Lets .sidebar-nav's flex:1 1 auto (below) actually push the
           profile block (margin-top:auto) down to the bottom of the
           sidebar -- if the nav list is ever taller than the viewport,
           the whole sidebar (already overflow-y:auto, position:fixed;
           height:100vh via dashboard-theme.css) just scrolls as one
           unit, so the profile block still ends up reachable at the
           very bottom of that scroll. */
        .hr-sidebar {
            display: flex;
            flex-direction: column;
        }

        .hr-sidebar .sidebar-user {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            margin-top: auto;
            flex-shrink: 0;
            border-top: 1px solid var(--border-color);
        }

        .hr-sidebar .user-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-muted);
            transition: background 0.15s ease, color 0.15s ease, transform 0.2s ease;
        }
        .hr-sidebar .user-menu-toggle:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }
        .hr-sidebar .sidebar-user.open .user-menu-toggle {
            transform: rotate(180deg);
        }

        .hr-sidebar .sidebar-user-menu {
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: calc(100% + 8px);
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            padding: 6px;
            display: none;
            flex-direction: column;
            gap: 1px;
            z-index: 60;
        }
        .hr-sidebar .sidebar-user.open .sidebar-user-menu {
            display: flex;
        }

        .hr-sidebar .sidebar-user-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .hr-sidebar .sidebar-user-menu-item:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }
        .hr-sidebar .sidebar-user-menu-item.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }
        .hr-sidebar .sidebar-user-menu-divider {
            margin: 4px 6px;
            border-top: 1px solid var(--border-color);
        }

        /* Collapsed sidebar: icon-only, so the menu toggle (which would
           have no room next to a label-less avatar) hides too -- expand
           the sidebar to reach My Leaves/My Payslip/Edit Profile/Logout.
           (justify-content/padding/profile-link sizing for this state
           already come from the shared collapsed-sidebar rules.) */
        body.dashboard-theme .hr-sidebar.collapsed .sidebar-user .user-menu-toggle {
            display: none;
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

        /* Job Postings / Approvals grouped as a collapsible dropdown --
           "Job Postings" toggles a submenu instead of navigating directly */
        .hr-sidebar .sidebar-nav .nav-group-toggle {
            width: 100%;
            background: none;
            border: none;
            font: inherit;
            cursor: pointer;
        }

        .hr-sidebar .sidebar-nav .nav-group-chevron {
            margin-left: auto;
            font-size: 0.75rem;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .hr-sidebar .sidebar-nav .nav-group.nav-group-open .nav-group-chevron {
            transform: rotate(180deg);
        }

        .hr-sidebar .sidebar-nav .nav-submenu {
            display: none;
            flex-direction: column;
            padding-left: 34px;
            margin: 2px 0 4px;
        }

        .hr-sidebar .sidebar-nav .nav-group.nav-group-open .nav-submenu {
            display: flex;
        }

        .hr-sidebar .sidebar-nav .nav-subitem {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.86rem;
            transition: all 0.2s;
            margin-bottom: 1px;
        }

        .hr-sidebar .sidebar-nav .nav-subitem i {
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
        }

        .hr-sidebar .sidebar-nav .nav-subitem:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }

        .hr-sidebar .sidebar-nav .nav-subitem.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }

        /* Collapsed sidebar shows icons only -- no room for a submenu, so
           it stays hidden and the toggle button falls back to a plain
           link to the Job Postings list (see the click handler below). */
        .hr-sidebar.collapsed .sidebar-nav .nav-submenu {
            display: none !important;
        }
        .hr-sidebar.collapsed .sidebar-nav .nav-group-chevron {
            display: none;
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

        // Bell open/close, fetching, and rendering is now handled globally by app.js.

        document.addEventListener('DOMContentLoaded', function() {
            // Job Postings / Approvals dropdown -- toggles the submenu open
            // when the sidebar is expanded; when collapsed (icon-only,
            // nowhere to show a submenu) it falls back to a plain link.
            document.querySelectorAll('.nav-group-toggle').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const sidebar = document.getElementById('hrSidebar');
                    if (sidebar && sidebar.classList.contains('collapsed')) {
                        window.location.href = '?page=hr_job_postings';
                        return;
                    }
                    btn.closest('.nav-group').classList.toggle('nav-group-open');
                });
            });

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

            // Profile "upbar" -- opens the My Leaves / My Payslip / Edit
            // Profile / Logout menu above the bottom-pinned profile block.
            const userBlock = document.getElementById('sidebarUserBlock');
            const menuToggle = document.getElementById('sidebarUserMenuToggle');
            const menu = document.getElementById('sidebarUserMenu');
            if (userBlock && menuToggle && menu) {
                menuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userBlock.classList.toggle('open');
                });
                document.addEventListener('click', function(e) {
                    if (!userBlock.contains(e.target)) userBlock.classList.remove('open');
                });
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') userBlock.classList.remove('open');
                });
            }
        });
    </script>
</body>
</html>