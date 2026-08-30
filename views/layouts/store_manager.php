<?php
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ShelfSense - Store Manager' ?></title>
    <link rel="icon" type="image/png" href="/ShelfSense/public/assets/images/logo-black.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260830122553">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/dashboard-theme.css?v=20260830122553">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/store_manager.css">
    <?= $additional_css ?? '' ?>
</head>
<body class="dashboard-theme">
    <div class="dashboard-page">
    <div class="dashboard-shell">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="store-manager-sidebar" id="storeManagerSidebar">
            <div class="sidebar-brand">
                <span class="brand-logo">
                    <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                    <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
                </span>
                <span class="brand-label">Shelf<span class="text-yellow">Sense</span></span>
                <span class="badge bg-primary ms-2">Store</span>
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
                        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Store Manager') ?></div>
                        <small class="text-muted"><?= getRoleName($_SESSION['role'] ?? 'store_manager') ?></small>
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
                <a href="?page=store_manager_dashboard" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">Dashboard</span>
                </a>
                <a href="?page=store_manager_requisitions" class="nav-item <?= $activePage === 'requisitions' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-cart-check"></i></span> <span class="nav-label">Requisitions</span>
                </a>
                <a href="?page=store_manager_inventory" class="nav-item <?= $activePage === 'inventory' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-box-seam"></i></span> <span class="nav-label">Inventory</span>
                </a>
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
        <div class="store-manager-content flex-grow-1">
            <div class="store-manager-topbar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><?= $pageTitle ?? 'Store Manager Dashboard' ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="store-manager-page-content">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/ShelfSense/public/assets/js/app.js?v=20260830122553"></script>
    <script src="/ShelfSense/public/assets/js/store_manager/shared.js"></script>
    <?= $additional_js ?? '' ?>
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260830122211"></script>

    <style>
        .store-manager-sidebar {
            width: 250px;
            min-height: 100%;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }
        .store-manager-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .store-manager-sidebar .sidebar-brand .brand-mark {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--brand-yellow);
            border-radius: 3px;
            margin-right: 6px;
        }
        .store-manager-sidebar .sidebar-brand .text-yellow {
            color: var(--brand-yellow);
        }
        .store-manager-sidebar .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .store-manager-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }
        .store-manager-sidebar .sidebar-nav {
            padding: 10px 12px;
        }
        .store-manager-sidebar .sidebar-nav .nav-item {
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
        .store-manager-sidebar .sidebar-nav .nav-item:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }
        .store-manager-sidebar .sidebar-nav .nav-item.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }
        .store-manager-sidebar .sidebar-nav .nav-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        .store-manager-sidebar .sidebar-nav hr {
            margin: 12px 0;
            border-color: var(--border-color);
        }
        .store-manager-content {
            padding: 0;
            min-height: 100%;
            background: var(--bg-body);
        }
        .store-manager-topbar {
            padding: 16px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .store-manager-topbar h5 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
        }
        .store-manager-page-content {
            padding: 24px;
        }
        @media (max-width: 768px) {
            .store-manager-sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                z-index: 1050;
                transition: left 0.3s ease;
                background: var(--bg-card);
            }
            .store-manager-sidebar.open {
                left: 0;
            }
            .store-manager-content {
                margin-left: 0;
            }
            .store-manager-topbar {
                padding: 12px 16px;
            }
            .store-manager-page-content {
                padding: 16px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const topbar = document.querySelector('.store-manager-topbar');
            if (topbar && window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-link text-dark p-0 me-2';
                toggleBtn.innerHTML = '<i class="bi bi-list fs-4"></i>';
                toggleBtn.addEventListener('click', function() {
                    document.getElementById('storeManagerSidebar').classList.toggle('open');
                });
                topbar.prepend(toggleBtn);
            }
        });
    </script>
</body>
</html>