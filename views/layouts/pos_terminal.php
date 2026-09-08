<?php
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ShelfSense POS Terminal' ?></title>
    <link rel="icon" type="image/png" href="/ShelfSense/public/assets/images/logo-black.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="/ShelfSense/public/assets/css/app.css?v=20260908520000">
    <link rel="stylesheet" href="/ShelfSense/public/assets/css/dashboard-theme.css?v=20260908380000">
    <?= $additional_css ?? '' ?>
</head>
<body class="dashboard-theme">
    <?php require __DIR__ . '/../shared/splash_screen.php'; ?>
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
                <div class="avatar-sm bg-yellow rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-shop text-dark"></i>
                </div>
                <div class="user-info">
                    <div class="fw-semibold"><?= htmlspecialchars(Auth::posRegisterName() ?? 'Register') ?></div>
                    <small class="text-muted"><?= htmlspecialchars(Auth::posCashierName() ?? 'No cashier selected') ?></small>
                </div>
            </div>

            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" type="button" title="Collapse">
                <span class="nav-icon-wrap"><i class="bi bi-chevron-left"></i></span>
                <span class="nav-label">Collapse</span>
            </button>

            <nav class="sidebar-nav">
                <div class="sidebar-divider sidebar-divider-first"><span class="sidebar-divider-label">Main</span></div>
                <a href="?page=pos_checkout" class="nav-item <?= $activePage === 'checkout' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-cart-plus-fill"></i></span> <span class="nav-label">Checkout</span>
                </a>
                <a href="?page=pos_budget" class="nav-item <?= $activePage === 'budget' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-cash-stack"></i></span> <span class="nav-label">Budget</span>
                </a>
                <a href="?page=pos_orders" class="nav-item <?= $activePage === 'orders' ? 'active' : '' ?>">
                    <span class="nav-icon-wrap"><i class="bi bi-clock-history"></i></span> <span class="nav-label">Recent Transactions</span>
                </a>
                <div class="sidebar-divider"><hr><span class="sidebar-divider-label">Session</span></div>
                <a href="?page=pos_select_cashier" class="nav-item" id="posSwitchCashierLink">
                    <span class="nav-icon-wrap"><i class="bi bi-person-badge"></i></span> <span class="nav-label">Switch Cashier</span>
                </a>
                <a href="?page=pos_logout" class="nav-item text-danger" id="posLeaveRegisterLink">
                    <span class="nav-icon-wrap"><i class="bi bi-box-arrow-right"></i></span> <span class="nav-label">Leave Register</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="pos-content flex-grow-1">
            <!-- Top Bar -->
            <div class="pos-topbar d-flex justify-content-between align-items-center">
                <div>
                    <div class="topbar-greeting"><span class="text-yellow"><?= htmlspecialchars(Auth::posRegisterName() ?? 'Register') ?></span></div>
                    <div class="topbar-subtitle">
                        <span class="topbar-page-label"><?= $pageTitle ?? 'POS Terminal' ?></span>
                        <span class="topbar-dot">•</span>
                        <span id="topbarDateTime"></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative" id="posNotifContainer">
                        <button class="pos-notif-bell-btn position-relative" id="posNotifBell" aria-label="Notifications">
                            <i class="bi bi-bell-fill"></i>
                            <span class="pos-notif-badge" id="posNotifBadge" style="display:none;">0</span>
                        </button>
                        <div class="notification-dropdown" id="posNotifDropdown" style="display:none;">
                            <div class="notification-header d-flex justify-content-between align-items-center">
                                <span>Notifications</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="posNotifClearBtn">Clear all</button>
                            </div>
                            <div id="posNotifList">
                                <div class="text-center text-muted small py-3">No notifications</div>
                            </div>
                        </div>
                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/ShelfSense/public/assets/js/app.js?v=20260908400000"></script>
    <script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260908530000"></script>
    <script src="/ShelfSense/public/assets/js/pos/pos-notifications.js?v=20260905070000"></script>

    <?= $additional_js ?? '' ?>

    <style>
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
        .pos-sidebar .sidebar-brand .text-yellow { color: var(--brand-yellow); }
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
        .pos-sidebar .sidebar-nav { padding: 10px 12px; }
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
        .pos-sidebar .sidebar-nav .nav-item:hover { background: var(--light-yellow-subtle); color: var(--text-main); }
        .pos-sidebar .sidebar-nav .nav-item.active { background: var(--light-yellow-subtle); color: var(--brand-yellow-hover); font-weight: 600; }
        .pos-sidebar .sidebar-nav .nav-item i { font-size: 1.1rem; width: 24px; text-align: center; }
        .pos-sidebar .sidebar-nav hr { margin: 12px 0; border-color: var(--border-color); }
        /* min-width: 0 -- .pos-sidebar is position:fixed (out of flow), so
           .pos-content ends up the sole flex item of its row-direction
           parent; without this it defaults to min-width:auto and sizes
           itself to its widest descendant's content instead of the
           viewport, dragging the whole page into horizontal scroll (see
           the matching .pos-checkout-left-col fix in app.css). */
        .pos-content { padding: 0; min-height: 100%; min-width: 0; background: var(--bg-body); display: flex; flex-direction: column; }
        .pos-topbar {
            padding: 12px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-shrink: 0;
        }
        .pos-topbar .topbar-greeting {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: var(--text-main);
            line-height: 1.2;
        }
        .pos-topbar .topbar-subtitle { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
        .pos-topbar .topbar-subtitle .topbar-page-label { font-weight: 600; color: var(--brand-yellow); }
        .pos-topbar .topbar-subtitle .topbar-dot { opacity: 0.5; }
        .pos-page-content { padding: 20px 24px; flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; }
        /* Order Summary panel (Checkout page) stays in view while the product
           grid scrolls -- offset by the sticky topbar's own height (~72px)
           plus the page's top padding (20px) so it docks just under it
           instead of being covered by it (topbar has the higher z-index). */
        /* overflow-y: auto (not hidden) matters here -- on a short viewport
           the cart list + full Payment Summary can exceed max-height. Without
           this, that overflow used to spill silently past the card's rounded
           border with no clipping and no scrollbar, so the buttons looked
           like they were floating, unstyled, jammed against the screen edge.
           Scrolling the panel itself keeps them reachable and inside the card. */
        .pos-order-summary-sticky {
            position: sticky;
            top: calc(72px + 20px);
            max-height: calc(100vh - 72px - 40px);
            overflow-y: auto;
        }
        @media (max-width: 991px) {
            .pos-order-summary-sticky { position: static; max-height: none; overflow: visible; }
        }
        .flash-message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .flash-message.success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .flash-message.error { background: #f8d7da; color: #842029; border: 1px solid #f5c6cb; }
        .flash-message.warning { background: #fff3cd; color: #664d03; border: 1px solid #ffecb5; }
        .flash-message.info { background: #cff4fc; color: #055160; border: 1px solid #b6effb; }
        @media (max-width: 768px) {
            .pos-sidebar { position: fixed; left: -250px; top: 0; bottom: 0; z-index: 1050; transition: left 0.3s ease; background: var(--bg-card); }
            .pos-sidebar.open { left: 0; }
            .pos-content { margin-left: 0; }
            .pos-topbar { padding: 10px 16px; }
            .pos-page-content { padding: 12px 16px; }
        }
    </style>

    <script>
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

        // Switching cashiers only changes who's attributed for sales -- it
        // doesn't touch the register's budget/checkout state -- but it's
        // still a deliberate action worth a confirmation, since whoever's
        // signed in on screen right now will stop being credited for sales.
        document.addEventListener('DOMContentLoaded', function() {
            const switchLink = document.getElementById('posSwitchCashierLink');
            if (!switchLink) return;

            switchLink.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (!window.Swal) {
                    window.location.href = href;
                    return;
                }
                Swal.fire({
                    icon: 'question',
                    title: 'Switch cashier?',
                    text: 'Sales rung up after this will be credited to the new cashier instead.',
                    showCancelButton: true,
                    confirmButtonText: 'Switch Cashier',
                    confirmButtonColor: '#eeab1a',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });

        // Whoever is on this register must cash out before leaving it --
        // "leaving" means logging the register itself out (POS logout), not
        // just switching which cashier is attributed to sales.
        document.addEventListener('DOMContentLoaded', function() {
            const leaveLink = document.getElementById('posLeaveRegisterLink');
            if (!leaveLink) return;

            leaveLink.addEventListener('click', function (e) {
                e.preventDefault();
                fetch('?page=api_pos_get_budget_status')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.data.allocation) {
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Cash out required',
                                    text: 'This register still has an active budget. Cash out before leaving.',
                                    confirmButtonText: 'Go to Budget',
                                    confirmButtonColor: '#eeab1a'
                                }).then(result => {
                                    if (result.isConfirmed) {
                                        window.location.href = '?page=pos_budget';
                                    }
                                });
                            } else {
                                alert('This register still has an active budget. Cash out before leaving.');
                                window.location.href = '?page=pos_budget';
                            }
                        } else {
                            window.location.href = '?page=pos_logout';
                        }
                    })
                    .catch(() => {
                        window.location.href = '?page=pos_logout';
                    });
            });
        });
    </script>
</body>
</html>
