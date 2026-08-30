<?php
// views/pages/hr/avatar_approvals.php

use App\Core\Auth;
use App\Core\Response;

$title = 'Profile Picture Approvals - ShelfSense';
$pageTitle = 'Profile Picture Approvals';
$activePage = 'avatar_approvals';

// Owner-only page
if (!Auth::isOwner()) {
    Response::redirect('?page=dashboard');
    exit;
}

$additional_js = '<script src="/ShelfSense/public/assets/js/hr/avatar_approvals.js?v=20260829200500"></script>';
$additional_css = '
<style>
    .avatar-approval-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
    }
    .avatar-approval-thumb {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        overflow: hidden;
        background: var(--light-yellow-accent);
        flex-shrink: 0;
        border: 2px solid var(--brand-yellow);
    }
    .avatar-approval-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .avatar-approval-current {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        overflow: hidden;
        background: var(--bg-card-subtle);
        flex-shrink: 0;
    }
    .avatar-approval-current img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .avatar-approval-current i {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--text-muted);
    }
</style>
';

$content = <<<'EOT'
<div class="modern-card mb-3">
    <div class="card-body p-0">
        <div id="pendingAvatarsList">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading pending uploads...</p>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';
