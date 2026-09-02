// ============================================
// PROFILE PAGE - LIST VIEW PREFERENCE CARDS (Store Manager only)
// Reads/writes the exact same localStorage keys the Requisitions and
// Inventory pages' own Grid/Rows toggle buttons use (see
// store_manager/requisitions.js and store_manager/inventory.js), so
// picking a layout here immediately takes effect there too.
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sm-viewpref-options').forEach(function (group) {
        var key = group.dataset.prefKey;
        if (!key) return;

        var cards = group.querySelectorAll('.sm-viewpref-card');

        function applyActive(value) {
            cards.forEach(function (card) {
                var isActive = card.dataset.value === value;
                card.classList.toggle('active', isActive);
                card.querySelector('input').checked = isActive;
            });
        }

        applyActive(localStorage.getItem(key) === 'rows' ? 'rows' : 'grid');

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                var value = card.dataset.value;
                localStorage.setItem(key, value);
                applyActive(value);
            });
        });
    });
});
