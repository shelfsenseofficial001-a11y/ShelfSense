<?php
// views/shared/profile.php

$title = 'Edit Profile - ShelfSense';
$pageTitle = 'Edit Profile';
$activePage = 'profile';

$role = $_SESSION['role'] ?? 'employee';
$layout = 'cashier';
if (in_array($role, ['hr_head', 'hr_staff', 'owner'])) {
    $layout = 'hr';
} elseif ($role === 'store_manager') {
    $layout = 'store_manager';
} elseif (in_array($role, ['finance_head', 'finance_staff'])) {
    $layout = 'finance';
} elseif ($role === 'supplier') {
    $layout = 'supplier';
} elseif ($role === 'trainee') {
    $layout = 'trainee';
}

$additional_js = '<script src="/ShelfSense/public/assets/js/shared/profile.js?v=20260829200500"></script>';
$additional_js .= '<script src="/ShelfSense/public/assets/js/shared/profile-settings-nav.js?v=20260903000000"></script>';

// Experimental, Store Manager Dashboard only -- see public/assets/js/store_manager/dashboard-tour.js
$prefsNavItems = '';
$prefsPanels = '';
if ($role === 'store_manager') {
    $additional_js .= '<script src="/ShelfSense/public/assets/js/store_manager/profile-tour-toggle.js?v=20260902233500"></script>';
    $additional_js .= '<script src="/ShelfSense/public/assets/js/store_manager/profile-view-prefs.js?v=20260902235500"></script>';

    $prefsNavItems = '
        <div class="profile-settings-group-label">Preferences</div>
        <button type="button" class="profile-settings-nav-item" data-panel="panel-dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </button>
        <button type="button" class="profile-settings-nav-item" data-panel="panel-listview">
            <i class="bi bi-grid-3x3-gap"></i> List view
        </button>';

    $prefsPanels = '
<div class="profile-settings-panel" id="panel-dashboard">
    <div class="modern-card p-4">
        <div class="profile-section-title">Dashboard</div>
        <div class="profile-section-sub">Personalize how the dashboard behaves for you.</div>
        <div class="sm-tour-toggle-row">
            <div>
                <div class="fw-semibold">Dashboard walkthrough</div>
                <div class="text-muted small">Show the spotlight tour when you open the Store Manager Dashboard.</div>
            </div>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="dashboardTourToggle">
            </div>
        </div>
    </div>
</div>

<div class="profile-settings-panel" id="panel-listview">
    <div class="modern-card p-4">
        <div class="profile-section-title">List view</div>
        <div class="profile-section-sub">Choose your preferred layout for each page that shows a product or requisition list.</div>

        <div class="sm-viewpref-group">
            <div class="sm-viewpref-label">Requisitions page</div>
            <div class="sm-viewpref-options" data-pref-key="sm_requisitions_view">
                <label class="sm-viewpref-card" data-value="grid">
                    <input type="radio" name="smViewPref_requisitions" value="grid">
                    <div class="sm-viewpref-preview">
                        <span class="sm-viewpref-grid-item"></span>
                        <span class="sm-viewpref-grid-item"></span>
                        <span class="sm-viewpref-grid-item"></span>
                        <span class="sm-viewpref-grid-item"></span>
                    </div>
                    <div class="sm-viewpref-choice"><i class="bi bi-circle"></i><i class="bi bi-check-circle-fill"></i> Grid</div>
                </label>
                <label class="sm-viewpref-card" data-value="rows">
                    <input type="radio" name="smViewPref_requisitions" value="rows">
                    <div class="sm-viewpref-preview">
                        <span class="sm-viewpref-row-item"></span>
                        <span class="sm-viewpref-row-item"></span>
                        <span class="sm-viewpref-row-item"></span>
                    </div>
                    <div class="sm-viewpref-choice"><i class="bi bi-circle"></i><i class="bi bi-check-circle-fill"></i> Rows</div>
                </label>
            </div>
        </div>

        <div class="sm-viewpref-group">
            <div class="sm-viewpref-label">Inventory page</div>
            <div class="sm-viewpref-options" data-pref-key="sm_inventory_view">
                <label class="sm-viewpref-card" data-value="grid">
                    <input type="radio" name="smViewPref_inventory" value="grid">
                    <div class="sm-viewpref-preview">
                        <span class="sm-viewpref-grid-item"></span>
                        <span class="sm-viewpref-grid-item"></span>
                        <span class="sm-viewpref-grid-item"></span>
                        <span class="sm-viewpref-grid-item"></span>
                    </div>
                    <div class="sm-viewpref-choice"><i class="bi bi-circle"></i><i class="bi bi-check-circle-fill"></i> Grid</div>
                </label>
                <label class="sm-viewpref-card" data-value="rows">
                    <input type="radio" name="smViewPref_inventory" value="rows">
                    <div class="sm-viewpref-preview">
                        <span class="sm-viewpref-row-item"></span>
                        <span class="sm-viewpref-row-item"></span>
                        <span class="sm-viewpref-row-item"></span>
                    </div>
                    <div class="sm-viewpref-choice"><i class="bi bi-circle"></i><i class="bi bi-check-circle-fill"></i> Rows</div>
                </label>
            </div>
        </div>
    </div>
</div>';
}
$additional_css = '
<style>
    /* ---------- Modrinth-style settings layout: sidebar + panel ---------- */
    .profile-settings-layout {
        display: flex;
        align-items: flex-start;
        gap: 24px;
    }
    .profile-settings-nav {
        flex: 0 0 220px;
        position: sticky;
        top: 20px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 12px;
    }
    .profile-settings-group-label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 10px 10px 4px;
    }
    .profile-settings-group-label:first-child {
        padding-top: 2px;
    }
    .profile-settings-nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        border: none;
        background: transparent;
        color: var(--text-main);
        font-size: 0.88rem;
        font-weight: 600;
        text-align: left;
        padding: 9px 10px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .profile-settings-nav-item i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        color: var(--text-muted);
    }
    .profile-settings-nav-item:hover {
        background: var(--bg-card-subtle);
    }
    .profile-settings-nav-item.active {
        background: var(--brand-yellow);
        color: #1a1a1a;
    }
    .profile-settings-nav-item.active i {
        color: #1a1a1a;
    }
    .profile-settings-content {
        flex: 1 1 auto;
        min-width: 0;
    }
    .profile-settings-panel {
        display: none;
    }
    .profile-settings-panel.active {
        display: block;
    }
    @media (max-width: 767.98px) {
        .profile-settings-layout {
            flex-direction: column;
        }
        .profile-settings-nav {
            flex-direction: row;
            flex-wrap: wrap;
            width: 100%;
            position: static;
        }
        .profile-settings-group-label {
            width: 100%;
        }
    }
    .profile-avatar-wrap {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        overflow: hidden;
        background: var(--light-yellow-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .profile-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .profile-avatar-wrap i {
        font-size: 2.25rem;
        color: var(--brand-yellow);
    }
    .profile-section-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .profile-section-sub {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-bottom: 16px;
    }
    .profile-info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .profile-info-row:last-child { border-bottom: none; }
    .profile-info-row .label { color: var(--text-muted); font-size: 0.85rem; }
    .profile-info-row .value { font-weight: 500; }
    .profile-pending-notice {
        display: none;
        align-items: center;
        gap: 10px;
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 0.85rem;
    }
    .profile-pending-notice.status-pending {
        background: var(--light-yellow-accent);
        color: var(--text-main);
    }
    .profile-pending-notice.status-rejected {
        background: #fecaca;
        color: #991b1b;
    }
    [data-bs-theme="dark"] .profile-pending-notice.status-rejected {
        background: #7f1d1d;
        color: #fca5a5;
    }
    .profile-pending-thumb {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }
    .profile-pending-thumb img { width: 100%; height: 100%; object-fit: cover; }
</style>
';

$content = <<<HTML
<div class="profile-settings-layout">
    <nav class="profile-settings-nav">
        <div class="profile-settings-group-label">Account</div>
        <button type="button" class="profile-settings-nav-item active" data-panel="panel-profile">
            <i class="bi bi-person-circle"></i> Profile
        </button>
        <button type="button" class="profile-settings-nav-item" data-panel="panel-security">
            <i class="bi bi-shield-lock"></i> Security
        </button>
        {$prefsNavItems}
    </nav>

    <div class="profile-settings-content">
        <div class="profile-settings-panel active" id="panel-profile">
            <div class="modern-card p-4 mb-3">
                <div class="profile-section-title">Profile picture</div>
                <div class="profile-section-sub">This is shown in the sidebar and anywhere your account appears.</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="profile-avatar-wrap" id="profileAvatarWrap">
                        <i class="bi bi-person-fill" id="profileAvatarIcon"></i>
                        <img id="profileAvatarImg" src="" alt="Profile" style="display:none;">
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="uploadAvatarBtn">
                                <i class="bi bi-upload"></i> Upload image
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeAvatarBtn">
                                <i class="bi bi-trash"></i> Remove image
                            </button>
                        </div>
                        <small class="text-muted">JPG, PNG, or WEBP. Max 3MB. New uploads need owner approval before they go live.</small>
                    </div>
                    <input type="file" id="avatarFileInput" accept=".jpg,.jpeg,.png,.webp" style="display:none;">
                </div>
                <div class="profile-pending-notice status-pending" id="pendingNotice">
                    <div class="profile-pending-thumb"><img id="pendingNoticeImg" src="" alt="Pending"></div>
                    <div class="flex-grow-1">Waiting for owner approval.</div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelPendingBtn">Cancel</button>
                </div>
                <div class="profile-pending-notice status-rejected" id="rejectedNotice">
                    <i class="bi bi-x-circle-fill"></i>
                    <div class="flex-grow-1">Your last upload was rejected<span id="rejectedReasonText"></span>. Try uploading a different photo.</div>
                </div>
            </div>

            <div class="modern-card p-4">
                <div class="profile-section-title">Account info</div>
                <div class="profile-section-sub">Managed by HR. Contact HR if any of this needs to change.</div>
                <div id="profileInfoBody">
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-settings-panel" id="panel-security">
            <div class="modern-card p-4">
                <div class="profile-section-title">Change password</div>
                <div class="profile-section-sub">Use a strong password you don't use anywhere else.</div>
                <form id="changePasswordForm">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small">Current password</label>
                            <input type="password" class="form-control" id="currentPassword" autocomplete="current-password" required>
                        </div>
                        <div class="col-12 col-md-6"></div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small">New password</label>
                            <input type="password" class="form-control" id="newPassword" autocomplete="new-password" minlength="8" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small">Confirm new password</label>
                            <input type="password" class="form-control" id="confirmPassword" autocomplete="new-password" minlength="8" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3" id="changePasswordBtn">
                        <i class="bi bi-shield-lock"></i> Update password
                    </button>
                </form>
            </div>
        </div>

        {$prefsPanels}
    </div>
</div>
HTML;

if ($layout === 'hr') {
    require_once __DIR__ . '/../layouts/hr.php';
} elseif ($layout === 'store_manager') {
    require_once __DIR__ . '/../layouts/store_manager.php';
} elseif ($layout === 'finance') {
    require_once __DIR__ . '/../layouts/finance.php';
} elseif ($layout === 'supplier') {
    require_once __DIR__ . '/../layouts/supplier.php';
} elseif ($layout === 'trainee') {
    require_once __DIR__ . '/../layouts/trainee.php';
} else {
    require_once __DIR__ . '/../layouts/cashier.php';
}
