<?php
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ShelfSense - Supplier' ?></title>
    <link rel="icon" type="image/png" href="/ShelfSense/public/assets/images/logo-black.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260908520000">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/dashboard-theme.css?v=20260908380000">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/supplier.css?v=20260905370000">
    <?= $additional_css ?? '' ?>
</head>
<body class="dashboard-theme">
    <?php require __DIR__ . '/../shared/splash_screen.php'; ?>
    <div class="dashboard-page">
    <div class="dashboard-shell">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="supplier-sidebar" id="supplierSidebar">
            <div class="sidebar-brand">
                <span class="brand-logo">
                    <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                    <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
                </span>
                <span class="brand-label">Shelf<span class="text-yellow">Sense</span></span>
                <span class="badge bg-secondary ms-2">Supplier</span>
            </div>

            <div class="sidebar-user">
                <a href="?page=profile" class="user-profile-link" title="Profile">
                    <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                        <?php if (!empty($_SESSION['profile_pic'])): ?>
                        <img src="/ShelfSense/public/<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" alt="Profile">
                        <?php else: ?>
                        <i class="bi bi-building text-dark"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Supplier') ?></div>
                        <small class="text-muted"><?= getRoleName($_SESSION['role'] ?? 'supplier') ?></small>
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
                <a href="?page=supplier_dashboard" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">Dashboard</span>
                </a>
                <a href="?page=supplier_requisitions" class="nav-item <?= $activePage === 'requisitions' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-clipboard-check"></i></span> <span class="nav-label">Purchase Orders</span>
                </a>
                <a href="?page=supplier_invoices" class="nav-item <?= $activePage === 'invoices' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-receipt"></i></span> <span class="nav-label">Invoices</span>
                </a>
                <a href="?page=supplier_products" class="nav-item <?= $activePage === 'products' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-box-seam"></i></span> <span class="nav-label">Products</span>
                </a>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Account</span></div>
                <a href="?page=logout" class="nav-item text-danger">
                    <span class="nav-icon-wrap"><i class="bi bi-box-arrow-right"></i></span> <span class="nav-label">Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="supplier-content flex-grow-1">
            <div class="supplier-topbar d-flex justify-content-between align-items-center">
                <div>
                    <div class="topbar-greeting">Hello, <span class="text-yellow"><?= htmlspecialchars($_SESSION['first_name'] ?? 'there') ?></span>!</div>
                    <div class="topbar-subtitle">
                        <span class="topbar-page-label"><?= $pageTitle ?? 'Supplier Dashboard' ?></span>
                        <span class="topbar-dot">•</span>
                        <span id="topbarDateTime"></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($activePage === 'dashboard'): ?>
                    <!-- Dashboard Edit Mode -->
                    <button class="dash-edit-btn" id="dashEditModeBtn" aria-label="Rearrange dashboard widgets" type="button">
                        <i class="bi bi-pencil-fill"></i>
                        <span class="dash-edit-label">Edit UI</span>
                    </button>
                    <?php endif; ?>

                    <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>

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

            <div class="supplier-page-content">
                <?= $content ?? '' ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/ShelfSense/public/assets/js/app.js?v=20260908400000"></script>
    <script src="/ShelfSense/public/assets/js/supplier/shared.js"></script>
    <?= $additional_js ?? '' ?>
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260908530000"></script>

    <style>
        .supplier-sidebar {
            width: 250px;
            min-height: 100%;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }
        .supplier-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .supplier-sidebar .sidebar-brand .brand-mark {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--brand-yellow);
            border-radius: 3px;
            margin-right: 6px;
        }
        .supplier-sidebar .sidebar-brand .text-yellow {
            color: var(--brand-yellow);
        }
        .supplier-sidebar .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .supplier-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }
        .supplier-sidebar .sidebar-nav {
            padding: 10px 12px;
        }
        .supplier-sidebar .sidebar-nav .nav-item {
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
        .supplier-sidebar .sidebar-nav .nav-item:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }
        .supplier-sidebar .sidebar-nav .nav-item.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }
        .supplier-sidebar .sidebar-nav .nav-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        .supplier-sidebar .sidebar-nav hr {
            margin: 12px 0;
            border-color: var(--border-color);
        }
        .supplier-content {
            padding: 0;
            min-height: 100%;
            background: var(--bg-body);
            display: flex;
            flex-direction: column;
        }
        .supplier-topbar {
            padding: 16px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .supplier-topbar h5 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
        }
        .supplier-topbar .topbar-greeting {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: var(--text-main);
            line-height: 1.2;
        }
        .supplier-topbar .topbar-greeting .text-yellow {
            background: linear-gradient(135deg, var(--brand-yellow), var(--brand-yellow-hover));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .supplier-topbar .topbar-subtitle {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .supplier-topbar .topbar-subtitle .topbar-page-label {
            font-weight: 600;
            color: var(--brand-yellow);
        }
        .supplier-topbar .topbar-subtitle .topbar-dot {
            opacity: 0.5;
        }
        .supplier-page-content {
            padding: 24px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        /* Lets the dashboard's own content (#dashboardContent) stretch to
           fill this column instead of stopping at its own content height --
           see the bento-card rules in supplier.css. Other pages just get an
           extra flex context here, which is a no-op for their normal
           block-flow content. */
        .supplier-page-content > * {
            min-height: 0;
        }
        @media (max-width: 768px) {
            .supplier-sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                z-index: 1050;
                transition: left 0.3s ease;
                background: var(--bg-card);
            }
            .supplier-sidebar.open {
                left: 0;
            }
            .supplier-content {
                margin-left: 0;
            }
            .supplier-topbar {
                padding: 12px 16px;
            }
            .supplier-page-content {
                padding: 16px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const topbar = document.querySelector('.supplier-topbar');
            if (topbar && window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-link text-dark p-0 me-2';
                toggleBtn.innerHTML = '<i class="bi bi-list fs-4"></i>';
                toggleBtn.addEventListener('click', function() {
                    document.getElementById('supplierSidebar').classList.toggle('open');
                });
                topbar.prepend(toggleBtn);
            }
        });

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
    </script>
</body>
</html>