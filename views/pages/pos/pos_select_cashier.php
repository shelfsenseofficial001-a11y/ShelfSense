<?php
// views/pages/pos/pos_select_cashier.php
use App\Core\Auth;

$title = 'Select Cashier - ShelfSense POS';
$subtitle = 'Select Cashier';

$registerName = htmlspecialchars(Auth::posRegisterName() ?? 'Register');

$content = '
<div class="brand">
    <h1><span class="brand-mark"></span>Shelf<span>Sense</span></h1>
    <small>' . $registerName . '</small>
</div>

<div class="form-header text-center">
    <h3>Who\'s ringing up sales?</h3>
    <p>Pick your name to attribute sales on this register to you.</p>
</div>

<div id="cashierList" class="d-flex flex-column gap-2">
    <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
</div>

<div id="traineeSection" style="display:none;">
    <div class="cashier-section-label">Trainees</div>
    <div id="traineeList" class="d-flex flex-column gap-2"></div>
</div>

<p class="text-center small text-muted mt-3">
    <a href="?page=pos_logout" class="auth-link"><i class="bi bi-box-arrow-left me-1"></i>Not this register? Leave</a>
</p>

<!-- Password confirmation modal -->
<div class="modal fade" id="cashierPasswordModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm it\'s you</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Enter the password for <strong id="cashierPasswordName"></strong> to continue.</p>
                <input type="password" id="cashierPasswordInput" class="form-control" placeholder="Password" autocomplete="off">
                <div id="cashierPasswordError" class="text-danger small mt-2" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-yellow-primary btn-sm" id="cashierPasswordConfirmBtn">Continue</button>
            </div>
        </div>
    </div>
</div>

<style>
    .cashier-pick-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        text-align: left;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: var(--bg-card-subtle, #f8f8f7);
        color: var(--text-main);
        transition: all 0.15s ease;
    }
    .cashier-pick-btn:hover {
        border-color: var(--brand-yellow);
        background: var(--light-yellow-subtle);
    }
    .cashier-pick-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--light-yellow-accent);
        color: var(--brand-yellow-hover, var(--brand-yellow));
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
    .cashier-section-label {
        margin-top: 18px;
        margin-bottom: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-muted);
        border-top: 1px solid var(--border-color);
        padding-top: 14px;
    }
</style>

<script>
function escapeHtmlPos(text) {
    const div = document.createElement("div");
    div.textContent = text == null ? "" : String(text);
    return div.innerHTML;
}

function renderPickList(container, people) {
    container.innerHTML = people.map(c => {
        const name = c.first_name + " " + c.last_name;
        const initial = c.first_name.charAt(0).toUpperCase();
        return \'<button type="button" class="cashier-pick-btn" data-id="\' + c.user_id + \'" data-name="\' + escapeHtmlPos(name) + \'">\'
            + \'<span class="cashier-pick-avatar">\' + initial + \'</span>\'
            + \'<span><div class="fw-semibold">\' + escapeHtmlPos(name) + \'</div><small class="text-muted">\' + escapeHtmlPos(c.employee_number || "") + \'</small></span>\'
            + \'</button>\';
    }).join("");

    container.querySelectorAll(".cashier-pick-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            openCashierPasswordModal(this.dataset.id, this.dataset.name);
        });
    });
}

let pendingCashierId = null;
let passwordModal = null;
const passwordInput = document.getElementById("cashierPasswordInput");
const passwordError = document.getElementById("cashierPasswordError");

function openCashierPasswordModal(userId, name) {
    if (!passwordModal) {
        passwordModal = new bootstrap.Modal(document.getElementById("cashierPasswordModal"));
    }
    pendingCashierId = userId;
    document.getElementById("cashierPasswordName").textContent = name;
    passwordInput.value = "";
    passwordError.style.display = "none";
    passwordModal.show();
    setTimeout(() => passwordInput.focus(), 300);
}

function submitCashierPassword() {
    const password = passwordInput.value;
    if (!password) {
        passwordError.textContent = "Please enter the password.";
        passwordError.style.display = "block";
        return;
    }
    const confirmBtn = document.getElementById("cashierPasswordConfirmBtn");
    confirmBtn.disabled = true;
    passwordError.style.display = "none";

    fetch("?page=api_pos_select_cashier", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: pendingCashierId, password: password })
    })
        .then(r => r.json())
        .then(res => {
            confirmBtn.disabled = false;
            if (res.success) {
                window.location.href = res.data.redirect;
            } else {
                passwordError.textContent = res.message || "Incorrect password.";
                passwordError.style.display = "block";
                passwordInput.value = "";
                passwordInput.focus();
            }
        })
        .catch(() => {
            confirmBtn.disabled = false;
            passwordError.textContent = "Something went wrong. Please try again.";
            passwordError.style.display = "block";
        });
}

document.getElementById("cashierPasswordConfirmBtn").addEventListener("click", submitCashierPassword);
passwordInput.addEventListener("keydown", function(e) {
    if (e.key === "Enter") submitCashierPassword();
});

fetch("?page=api_pos_get_cashiers")
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById("cashierList");
        if (!data.success) {
            container.innerHTML = \'<div class="text-center text-danger small py-3">Failed to load cashiers.</div>\';
            return;
        }

        const cashiers = data.data.cashiers || [];
        const trainees = data.data.trainees || [];

        if (cashiers.length === 0) {
            container.innerHTML = \'<div class="text-center text-muted small py-3">No active cashiers found. Ask your Store Manager to check employee accounts.</div>\';
        } else {
            renderPickList(container, cashiers);
        }

        if (trainees.length > 0) {
            const traineeSection = document.getElementById("traineeSection");
            traineeSection.style.display = "block";
            renderPickList(document.getElementById("traineeList"), trainees);
        }
    })
    .catch(() => {
        document.getElementById("cashierList").innerHTML = \'<div class="text-center text-danger small py-3">Failed to load cashiers.</div>\';
    });
</script>
';

require_once __DIR__ . '/../../layouts/auth.php';
