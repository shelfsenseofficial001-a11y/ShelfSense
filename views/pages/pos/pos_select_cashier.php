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

<p class="text-center small text-muted mt-3">
    <a href="?page=pos_logout" class="auth-link"><i class="bi bi-box-arrow-left me-1"></i>Not this register? Leave</a>
</p>

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
</style>

<script>
function escapeHtmlPos(text) {
    const div = document.createElement("div");
    div.textContent = text == null ? "" : String(text);
    return div.innerHTML;
}

fetch("?page=api_pos_get_cashiers")
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById("cashierList");
        if (!data.success || !data.data.cashiers || data.data.cashiers.length === 0) {
            container.innerHTML = \'<div class="text-center text-muted small py-3">No active cashiers found. Ask your Store Manager to check employee accounts.</div>\';
            return;
        }
        container.innerHTML = data.data.cashiers.map(c => {
            const name = c.first_name + " " + c.last_name;
            const initial = c.first_name.charAt(0).toUpperCase();
            return \'<button type="button" class="cashier-pick-btn" data-id="\' + c.user_id + \'">\'
                + \'<span class="cashier-pick-avatar">\' + initial + \'</span>\'
                + \'<span><div class="fw-semibold">\' + escapeHtmlPos(name) + \'</div><small class="text-muted">\' + escapeHtmlPos(c.employee_number || "") + \'</small></span>\'
                + \'</button>\';
        }).join("");

        container.querySelectorAll(".cashier-pick-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                const userId = this.dataset.id;
                container.querySelectorAll(".cashier-pick-btn").forEach(b => b.disabled = true);
                fetch("?page=api_pos_select_cashier", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ user_id: userId })
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            window.location.href = res.data.redirect;
                        } else {
                            Swal.fire({ icon: "error", title: "Failed", text: res.message });
                            container.querySelectorAll(".cashier-pick-btn").forEach(b => b.disabled = false);
                        }
                    });
            });
        });
    })
    .catch(() => {
        document.getElementById("cashierList").innerHTML = \'<div class="text-center text-danger small py-3">Failed to load cashiers.</div>\';
    });
</script>
';

require_once __DIR__ . '/../../layouts/auth.php';
