// ============================================
// EDIT PROFILE - SETTINGS SIDEBAR (Modrinth-style)
// Client-side tab switching between .profile-settings-panel sections;
// no page reload, no server round trip. Generic/shared across every
// portal since the Profile page itself is shared.
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    var navItems = document.querySelectorAll('.profile-settings-nav-item');
    var panels = document.querySelectorAll('.profile-settings-panel');
    if (!navItems.length || !panels.length) return;

    navItems.forEach(function (item) {
        item.addEventListener('click', function () {
            var targetId = item.dataset.panel;

            navItems.forEach(function (el) { el.classList.remove('active'); });
            item.classList.add('active');

            panels.forEach(function (panel) {
                panel.classList.toggle('active', panel.id === targetId);
            });
        });
    });
});
