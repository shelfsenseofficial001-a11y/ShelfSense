// ============================================
// POS — CLIENT-SIDE NOTIFICATION BELL
//
// Light-weight, session-only notification feed. There's no backend table
// for this yet -- items are pushed by page JS (pos.js, budget.js, ...) as
// real events happen (low stock, a voided order, a large sale) and kept in
// sessionStorage so they survive a page refresh but not a new tab/session.
// ============================================

(function () {
    const STORAGE_KEY = 'pos_notifications';
    const MAX_ITEMS = 30;

    function load() {
        try {
            return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    function save(items) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        } catch (e) { /* storage full/unavailable -- notifications just won't persist */ }
    }

    function timeAgo(iso) {
        const diffMs = Date.now() - new Date(iso).getTime();
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return `${mins}m ago`;
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return `${hrs}h ago`;
        return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function render() {
        const list = document.getElementById('posNotifList');
        const badge = document.getElementById('posNotifBadge');
        if (!list || !badge) return;

        const items = load();
        const unreadCount = items.filter(n => !n.read).length;

        if (unreadCount > 0) {
            badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }

        if (items.length === 0) {
            list.innerHTML = '<div class="text-center text-muted small py-3">No notifications</div>';
            return;
        }

        list.innerHTML = items.map(n => `
            <div class="notification-item ${n.read ? '' : 'unread'}" data-id="${n.id}">
                <div class="notification-icon"><i class="bi ${escapeHtml(n.icon || 'bi-info-circle')}"></i></div>
                <div class="notification-body">
                    <div class="notification-title">${escapeHtml(n.title)}</div>
                    <div class="notification-message">${escapeHtml(n.message)}</div>
                    <div class="notification-time">${timeAgo(n.created_at)}</div>
                </div>
            </div>
        `).join('');
    }

    // type: 'low_stock' | 'void' | 'large_sale' | 'info' etc.
    // dedupeKey: optional -- if a not-yet-dismissed item with the same key already
    // exists, skip (keeps a restocked/low-stock product from spamming the feed).
    function push({ title, message, icon, dedupeKey }) {
        const items = load();
        if (dedupeKey && items.some(n => n.dedupeKey === dedupeKey)) return;

        items.unshift({
            id: Date.now() + Math.random().toString(36).slice(2, 7),
            title,
            message,
            icon: icon || 'bi-info-circle',
            dedupeKey: dedupeKey || null,
            read: false,
            created_at: new Date().toISOString()
        });

        save(items.slice(0, MAX_ITEMS));
        render();
    }

    function markAllRead() {
        const items = load().map(n => ({ ...n, read: true }));
        save(items);
        render();
    }

    function clearAll() {
        save([]);
        render();
    }

    function init() {
        const bell = document.getElementById('posNotifBell');
        const dropdown = document.getElementById('posNotifDropdown');
        const clearBtn = document.getElementById('posNotifClearBtn');
        if (!bell || !dropdown) return;

        render();

        bell.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dropdown.style.display === 'block';
            dropdown.style.display = isOpen ? 'none' : 'block';
            if (!isOpen) markAllRead();
        });

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && e.target !== bell) {
                dropdown.style.display = 'none';
            }
        });

        clearBtn?.addEventListener('click', function (e) {
            e.stopPropagation();
            clearAll();
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    window.PosNotify = { push };
})();
