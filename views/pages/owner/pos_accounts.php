<?php
$title = 'POS Accounts - Owner';
$pageTitle = 'POS Accounts';
$activePage = 'pos_accounts';
$additional_js = '<script src="/ShelfSense/public/assets/js/owner/pos_accounts.js?v=20260902030000"></script>';

$content = '
<div class="alert alert-info d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-info-circle"></i>
    <span>A POS account (POS ID + 4-digit PIN) unlocks a register/terminal for checkout. It is separate from staff logins -- cashiers pick their name after unlocking, purely for sale attribution. A store manager can have any number of registers; creating one here always adds a new one for the store manager you pick.</span>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-yellow me-2"></i>Create POS Account</h6>
            <form id="createPosForm">
                <div class="mb-2">
                    <label class="form-label fw-semibold">Store Manager</label>
                    <select id="posStoreManager" class="form-select" required></select>
                    <small class="text-muted">Creates a new register for this store manager.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">4-Digit PIN</label>
                    <input type="password" id="posPinCreate" class="form-control" maxlength="4" inputmode="numeric" pattern="[0-9]*" placeholder="••••" required>
                </div>
                <button type="submit" class="btn btn-yellow-primary btn-sm w-100" id="createPosBtn">
                    <i class="bi bi-shield-plus me-1"></i> Create POS Account
                </button>
            </form>
            <div id="createPosMessage" class="mt-2"></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="modern-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-list-ul text-yellow me-2"></i>Registers</h6>
            <div id="posAccountsTable">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
        </div>
    </div>
</div>
';

require_once __DIR__ . '/../../layouts/hr.php';
