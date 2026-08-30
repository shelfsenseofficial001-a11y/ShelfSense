// ============================================
// SHELFSENSE - GLOBAL FUNCTIONS
// ============================================

console.log('✅ ShelfSense app.js loaded');

// ============================================
// DARK MODE - PERSISTENT & GLOBAL
// ============================================

(function() {
    const htmlElement = document.documentElement;
    
    const savedTheme = localStorage.getItem('theme') || 
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    htmlElement.setAttribute('data-bs-theme', savedTheme);
    
    function updateAllThemeIcons(theme) {
        document.querySelectorAll('.theme-toggle-btn, .theme-toggle-btn-auth').forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (theme === 'dark') {
                    icon.classList.remove('bi-moon-stars-fill');
                    icon.classList.add('bi-sun-fill');
                } else {
                    icon.classList.remove('bi-sun-fill');
                    icon.classList.add('bi-moon-stars-fill');
                }
            }
        });
        document.querySelectorAll('#themeIcon, #themeIconAuth').forEach(icon => {
            if (theme === 'dark') {
                icon.classList.remove('bi-moon-stars-fill');
                icon.classList.add('bi-sun-fill');
            } else {
                icon.classList.remove('bi-sun-fill');
                icon.classList.add('bi-moon-stars-fill');
            }
        });
    }
    
    function toggleTheme() {
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        htmlElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateAllThemeIcons(newTheme);
    }
    
    updateAllThemeIcons(savedTheme);
    
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.theme-toggle-btn, .theme-toggle-btn-auth, #themeToggle, #themeToggleAuth').forEach(btn => {
            btn.addEventListener('click', toggleTheme);
        });
    });
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.theme-toggle-btn, .theme-toggle-btn-auth, #themeToggle, #themeToggleAuth').forEach(btn => {
                btn.addEventListener('click', toggleTheme);
            });
        });
    } else {
        document.querySelectorAll('.theme-toggle-btn, .theme-toggle-btn-auth, #themeToggle, #themeToggleAuth').forEach(btn => {
            btn.addEventListener('click', toggleTheme);
        });
    }
    
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('theme')) {
            const newTheme = e.matches ? 'dark' : 'light';
            htmlElement.setAttribute('data-bs-theme', newTheme);
            updateAllThemeIcons(newTheme);
        }
    });
    
    window.toggleTheme = toggleTheme;
})();

// ============================================
// TABLE ACTION BUTTONS - mark icon-only buttons
// (icon + no text, e.g. a lone view/edit/reject glyph) so CSS can give
// them a solid-fill square look without also catching icon+text buttons
// like "<i class="bi bi-eye"></i> View Details" — CSS selectors can't
// see trailing text nodes, so this has to run in JS.
// ============================================
(function() {
    // Icon-only buttons have no visible label, so they get a hover tooltip
    // instead. The label is derived from the button's own most specific
    // class name (e.g. "reject-applicant" -> "Reject Applicant") rather
    // than hand-maintained per button, since these action buttons are
    // generated across 20+ separate JS files and already carry a
    // descriptive class for JS event binding purposes -- reusing it here
    // means every existing and future icon-only button gets a correct
    // tooltip for free, with no per-file changes.
    var TOOLTIP_SKIP_CLASSES = ['btn', 'btn-icon-only', 'btn-icon-checked'];
    function deriveTooltipLabel(btn) {
        var candidate = null;
        for (var i = 0; i < btn.classList.length; i++) {
            var c = btn.classList[i];
            if (TOOLTIP_SKIP_CLASSES.indexOf(c) !== -1) continue;
            if (/^btn-(outline-)?[a-z]+$/.test(c)) continue; // btn-outline-primary, btn-success, ...
            candidate = c;
            break;
        }
        if (!candidate) return null;
        var words = candidate.replace(/-btn$/, '').split('-');
        return words.map(function (w) {
            return w.charAt(0).toUpperCase() + w.slice(1);
        }).join(' ');
    }

    function markIconOnlyButtons(root) {
        root.querySelectorAll('.table .btn-sm:not(.btn-icon-checked)').forEach(function(btn) {
            btn.classList.add('btn-icon-checked');
            const icon = btn.querySelector(':scope > i.bi');
            if (!icon) return;
            const isOnlyChild = btn.children.length === 1 && btn.children[0] === icon;
            if (isOnlyChild && btn.textContent.trim() === '') {
                btn.classList.add('btn-icon-only');
                const label = deriveTooltipLabel(btn);
                if (label && window.bootstrap && window.bootstrap.Tooltip) {
                    btn.setAttribute('title', label);
                    btn.setAttribute('data-bs-toggle', 'tooltip');
                    new window.bootstrap.Tooltip(btn, { trigger: 'hover focus', placement: 'top' });
                }
            }
        });
    }
    markIconOnlyButtons(document);
    const observer = new MutationObserver(function(mutations) {
        for (const m of mutations) {
            if (m.addedNodes.length) {
                markIconOnlyButtons(document.body);
                break;
            }
        }
    });
    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    }
})();

// ============================================
// ACTIVE FILTER CHIPS (Modrinth-style removable filter pills)
// Generic, reusable across every filter row in every portal. A page just
// declares its own filter fields; this renders the chip row and, on
// removal, resets that field's value and re-fires the SAME event the
// page's own filter listener already reacts to (change/input) -- so no
// page's data-loading logic needs to know this exists.
// ============================================
window.ShelfSenseFilterChips = (function () {
    function escapeHtmlLocal(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function fieldIsActive(field, el) {
        if (field.type === 'checkbox') return el.checked !== !!field.defaultChecked;
        const def = field.defaultValue !== undefined ? field.defaultValue : '';
        return (el.value || '') !== def;
    }

    function fieldLabel(field, el) {
        if (field.type === 'select') {
            const opt = el.querySelector('option[value="' + CSS.escape(el.value) + '"]');
            return opt ? opt.textContent.trim() : el.value;
        }
        if (field.type === 'checkbox') {
            return field.label || el.value || 'On';
        }
        const prefix = field.labelPrefix ? field.labelPrefix + ' ' : '';
        return prefix + '"' + el.value + '"';
    }

    function resetField(field, el) {
        if (field.type === 'checkbox') {
            el.checked = !!field.defaultChecked;
            el.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }
        el.value = field.defaultValue !== undefined ? field.defaultValue : '';
        if (field.type === 'select' && window.refreshSearchableSelect) {
            window.refreshSearchableSelect(el);
        }
        el.dispatchEvent(new Event(field.type === 'search' || field.type === 'date' ? 'input' : 'change', { bubbles: true }));
        if (field.type !== 'select') {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function init(containerId, fields, options) {
        options = options || {};
        const container = document.getElementById(containerId);
        if (!container) return null;

        function render() {
            const active = [];
            fields.forEach(function (field) {
                const el = document.getElementById(field.elementId);
                if (!el) return;
                if (fieldIsActive(field, el)) {
                    active.push({ field: field, el: el, label: fieldLabel(field, el) });
                }
            });

            if (!active.length) {
                container.innerHTML = '';
                return;
            }

            let html = '<button type="button" class="filter-chip clear-all-chip" data-clear-all>'
                + '<i class="bi bi-x-circle"></i>Clear all filters</button>';
            active.forEach(function (item, i) {
                html += '<button type="button" class="filter-chip" data-chip-index="' + i + '">'
                    + '<i class="bi bi-x"></i>' + escapeHtmlLocal(item.label) + '</button>';
            });
            container.innerHTML = html;

            container.querySelectorAll('[data-chip-index]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const item = active[parseInt(this.dataset.chipIndex, 10)];
                    if (item) resetField(item.field, item.el);
                    if (options.applyButtonId) {
                        const applyBtn = document.getElementById(options.applyButtonId);
                        if (applyBtn) applyBtn.click();
                    }
                });
            });
            const clearAllBtn = container.querySelector('[data-clear-all]');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function () {
                    active.forEach(function (item) { resetField(item.field, item.el); });
                    if (options.applyButtonId) {
                        const applyBtn = document.getElementById(options.applyButtonId);
                        if (applyBtn) applyBtn.click();
                    }
                });
            }
        }

        fields.forEach(function (field) {
            const el = document.getElementById(field.elementId);
            if (!el) return;
            const evt = field.type === 'checkbox' ? 'change' : (field.type === 'select' ? 'change' : 'input');
            el.addEventListener(evt, render);
        });

        render();
        return { render: render };
    }

    return { init: init };
})();

// ============================================
// SIDEBAR COLLAPSE (generic — every portal)
// Finds any `.sidebar-collapse-btn` + the sidebar it belongs to
// (nearest ancestor whose id ends in "Sidebar", e.g. #hrSidebar,
// #storeManagerSidebar) and wires up the toggle + persistence.
//
// Also gives every nav item a hover tooltip carrying its label (derived
// from the .nav-label text itself, not a hand-maintained title attribute
// -- so it works on every portal even though only HR's markup happened
// to set title= originally). The tooltip is only enabled while that
// sidebar is collapsed; expanded, the label is already visible as text
// so a duplicate tooltip would be redundant.
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    function initSidebarTooltips(sidebar) {
        if (!window.bootstrap || !window.bootstrap.Tooltip) return;
        sidebar.querySelectorAll('.nav-item').forEach(function(item) {
            const label = item.querySelector('.nav-label');
            const text = label ? label.textContent.trim() : '';
            if (!text) return;
            item.setAttribute('title', text);
            item.__sidebarTooltip = new window.bootstrap.Tooltip(item, {
                trigger: 'hover focus',
                placement: 'right',
            });
        });
    }

    function syncSidebarTooltips(sidebar) {
        const collapsed = sidebar.classList.contains('collapsed');
        sidebar.querySelectorAll('.nav-item').forEach(function(item) {
            if (!item.__sidebarTooltip) return;
            if (collapsed) {
                item.__sidebarTooltip.enable();
            } else {
                item.__sidebarTooltip.hide();
                item.__sidebarTooltip.disable();
            }
        });
    }

    document.querySelectorAll('.sidebar-collapse-btn').forEach(function(btn) {
        const sidebar = btn.closest('[id$="Sidebar"]');
        if (!sidebar) return;
        const storageKey = sidebar.id + 'Collapsed';
        if (localStorage.getItem(storageKey) === '1') {
            sidebar.classList.add('collapsed');
        }

        initSidebarTooltips(sidebar);
        syncSidebarTooltips(sidebar);

        btn.addEventListener('click', function() {
            const collapsed = sidebar.classList.toggle('collapsed');
            localStorage.setItem(storageKey, collapsed ? '1' : '0');
            syncSidebarTooltips(sidebar);
        });
    });
});

// ============================================
// GLOBAL: Update Pending Badge (Module-Aware)
// ============================================

function updatePendingBadge() {
    // --- FINANCE HEAD MODULE: "Approve Payments" nav badge ---
    // Present in the sidebar on every page a finance head sees (including
    // My Leaves / My Payslip), so key off the element itself rather than
    // the current URL — that also keeps it live outside the head dashboard.
    const headBadge = document.getElementById('headPendingBadge');
    if (headBadge) {
        fetch('?page=api_finance_head_dashboard_stats')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.stats) {
                    const pending = data.data.stats.pending || 0;
                    if (pending > 0) {
                        headBadge.textContent = pending;
                        headBadge.style.display = 'flex';
                    } else {
                        headBadge.style.display = 'none';
                    }
                }
            })
            .catch(() => {});
    }

    const badge = document.getElementById('pendingBadge');
    if (!badge) return;

    // --- FINANCE STAFF MODULE ---
    // #pendingBadge is shared with the HR "Applicants" badge, so tell them
    // apart by the presence of the finance sidebar rather than the URL
    // (which won't mention "finance_staff" on shared pages like My Leaves).
    if (document.getElementById('financeSidebar')) {
        fetch('?page=api_finance_staff_dashboard_stats')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.stats) {
                    const pending = data.data.stats.pending_requisitions || 0;
                    if (pending > 0) {
                        badge.textContent = pending;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(() => {});
        return;
    }

    // --- HR MODULE (default) ---
    fetch('?page=api_get_applicants&p=1&status=all&role=all&search=')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pending = data.data.stats?.pending || 0;
                if (pending > 0) {
                    badge.textContent = pending;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(() => {});
}

// ============================================
// DATE VALIDATION
// ============================================

function validateScheduleDate(dateStr) {
    const selected = new Date(dateStr);
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);
    const maxDate = new Date(now);
    maxDate.setMonth(maxDate.getMonth() + 3);

    if (selected < tomorrow) {
        Swal.fire({ icon: 'warning', title: 'Invalid Date', text: 'Please select a date from tomorrow onwards.' });
        return false;
    }
    if (selected > maxDate) {
        Swal.fire({ icon: 'warning', title: 'Invalid Date', text: 'Date cannot exceed 3 months from now.' });
        return false;
    }
    return true;
}

// ============================================
// ON PAGE LOAD
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    updatePendingBadge();
});