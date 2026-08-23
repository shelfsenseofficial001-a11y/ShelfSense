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
// GLOBAL: Update Pending Badge (Module-Aware)
// ============================================

function updatePendingBadge() {
    const badge = document.getElementById('pendingBadge');
    if (!badge) return;

    const url = window.location.href;

    // --- FINANCE MODULE ---
    if (url.includes('finance_staff') || url.includes('finance_head')) {
        let apiUrl = '';
        if (url.includes('finance_staff')) {
            apiUrl = '?page=api_finance_staff_dashboard_stats';
        } else if (url.includes('finance_head')) {
            apiUrl = '?page=api_finance_head_dashboard_stats';
        } else {
            return; // fallback: do nothing
        }

        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.stats) {
                    let pending = 0;
                    const stats = data.data.stats;
                    if (stats.pending_requisitions !== undefined) {
                        pending = stats.pending_requisitions;
                    } else if (stats.pending !== undefined) {
                        pending = stats.pending;
                    }
                    if (pending > 0) {
                        badge.textContent = pending;
                        badge.style.display = 'inline-block';
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
                    badge.style.display = 'inline-block';
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