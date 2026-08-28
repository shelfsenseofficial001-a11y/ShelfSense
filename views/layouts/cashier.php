<?php
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ShelfSense POS' ?></title>
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
    <?= $additional_css ?? '' ?>
</head>
<body class="dashboard-theme">
    <div class="dashboard-page">
    <div class="dashboard-shell">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="pos-sidebar" id="posSidebar">
            <div class="sidebar-brand">
                <span class="brand-logo">
                    <img src="/ShelfSense/public/assets/images/logo-black.png" class="logo-light" alt="ShelfSense" width="22" height="22">
                    <img src="/ShelfSense/public/assets/images/logo-white.png" class="logo-dark" alt="ShelfSense" width="22" height="22">
                </span>
                <span class="brand-label">Shelf<span class="text-yellow">Sense</span></span>
                <span class="badge bg-success ms-2">POS</span>
            </div>

            <div class="sidebar-user">
                <a href="?page=profile" class="user-profile-link" title="Profile">
                    <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                        <i class="bi bi-person-fill text-dark"></i>
                    </div>
                    <div class="user-info">
                        <div class="fw-semibold"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Cashier') ?></div>
                        <small class="text-muted"><?= getRoleName($_SESSION['role'] ?? 'cashier') ?></small>
                    </div>
                </a>
                <a href="?page=logout" class="user-logout-btn" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>

            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" type="button" title="Collapse">
                <span class="nav-icon-wrap"><i class="bi bi-chevron-left"></i></span>
                <span class="nav-label">Collapse</span>
            </button>

            <nav class="sidebar-nav">
                <div class="sidebar-divider sidebar-divider-first"><span class="sidebar-divider-label">Main</span></div>
                <a href="?page=pos_dashboard" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></span> <span class="nav-label">Dashboard</span>
                </a>
                <a href="?page=pos_checkout" class="nav-item <?= $activePage === 'checkout' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-cart-plus-fill"></i></span> <span class="nav-label">Checkout</span>
                </a>
                <a href="?page=pos_orders" class="nav-item <?= $activePage === 'orders' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-clock-history"></i></span> <span class="nav-label">Order History</span>
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
        <div class="pos-content flex-grow-1">
            <!-- Top Bar -->
            <div class="pos-topbar d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><?= $pageTitle ?? 'POS Dashboard' ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Toggle -->
                    <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle Dark Mode">
                        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                    </button>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="pos-page-content">
                <?= $content ?? '' ?>
            </div>
            
            <!-- Flash Messages -->
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script src="/ShelfSense/public/assets/js/app.js?v=20260828222041"></script>
    
    <!-- Searchable Select Component -->
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260828222041"></script>
    
    <?= $additional_js ?? '' ?>
    
    <style>
        /* ============================================
           POS LAYOUT STYLES
           ============================================ */
        
        .pos-sidebar {
            width: 250px;
            min-height: 100%;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            flex-shrink: 0;
        }
        
        .pos-sidebar .sidebar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 0 20px 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }
        
        .pos-sidebar .sidebar-brand .brand-mark {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--brand-yellow);
            border-radius: 3px;
            margin-right: 6px;
        }
        
        .pos-sidebar .sidebar-brand .text-yellow {
            color: var(--brand-yellow);
        }
        
        .pos-sidebar .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .pos-sidebar .sidebar-user .avatar-sm {
            width: 36px;
            height: 36px;
            background: var(--light-yellow-accent);
            color: var(--brand-yellow-btn-text);
        }
        
        .pos-sidebar .sidebar-nav {
            padding: 10px 12px;
        }

        .pos-sidebar .sidebar-nav .nav-item {
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
        
        .pos-sidebar .sidebar-nav .nav-item:hover {
            background: var(--light-yellow-subtle);
            color: var(--text-main);
        }
        
        .pos-sidebar .sidebar-nav .nav-item.active {
            background: var(--light-yellow-subtle);
            color: var(--brand-yellow-hover);
            font-weight: 600;
        }
        
        .pos-sidebar .sidebar-nav .nav-item i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        
        .pos-sidebar .sidebar-nav hr {
            margin: 12px 0;
            border-color: var(--border-color);
        }
        
        .pos-content {
            padding: 0;
            min-height: 100%;
            background: var(--bg-body);
        }
        
        .pos-topbar {
            padding: 12px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .pos-topbar h5 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
        }
        
        .pos-page-content {
            padding: 20px 24px;
        }
        
        /* Flash messages */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .pos-sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                z-index: 1050;
                transition: left 0.3s ease;
                background: var(--bg-card);
            }
            
            .pos-sidebar.open {
                left: 0;
            }
            
            .pos-content {
                margin-left: 0;
            }
            
            .pos-topbar {
                padding: 10px 16px;
            }
            
            .pos-page-content {
                padding: 12px 16px;
            }
        }
    </style>
    
    <script>
        // Sidebar toggle for mobile
        document.addEventListener('DOMContentLoaded', function() {
            const topbar = document.querySelector('.pos-topbar');
            if (topbar && window.innerWidth <= 768) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-link text-dark p-0 me-2';
                toggleBtn.innerHTML = '<i class="bi bi-list fs-4"></i>';
                toggleBtn.addEventListener('click', function() {
                    document.getElementById('posSidebar').classList.toggle('open');
                });
                topbar.prepend(toggleBtn);
            }
        });
    </script>
</body>
</html>