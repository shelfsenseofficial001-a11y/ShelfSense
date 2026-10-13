// ============================================
// FINANCE DASHBOARD - DRAG-TO-REORDER EDIT MODE
// Same mechanic as the Store Manager / HR dashboards (see
// store_manager/dashboard-layout.js): each .dash-canvas-row is an
// independent sortable zone (widgets never move between rows, only within
// their own row). The resulting order is saved per account via
// api_save_finance_dashboard_layout and restored on load via
// api_get_finance_dashboard_layout.
//
// This one file serves BOTH Finance dashboards (Head and Staff) -- they
// use the same canvas row ids and the server picks the storage key from
// the logged-in role, so neither dashboard can overwrite the other's.
// ============================================

(function () {
    var EDIT_MODE_CLASS = 'dash-edit-mode';
    var draggedEl = null;
    var draggedRow = null;
    var preEditSnapshot = null;
    var revertCountdownInterval = null;

    function getRowOrder(row) {
        if (!row) return [];
        return Array.prototype.filter.call(row.children, function (el) {
            return el.classList.contains('dash-widget');
        }).map(function (el) { return el.dataset.widgetId; });
    }

    function collectAllOrders() {
        return {
            stats: getRowOrder(document.getElementById('fnDashCanvasStats')),
            content: getRowOrder(document.getElementById('fnDashCanvasContent')),
        };
    }

    function saveLayout() {
        fetch('?page=api_save_finance_dashboard_layout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ widget_order: collectAllOrders() }),
        }).catch(function (err) {
            console.error('Failed to save dashboard layout:', err);
        });
    }

    function applyOrder(row, order) {
        if (!row || !Array.isArray(order)) return;
        var widgets = {};
        Array.prototype.forEach.call(row.children, function (el) {
            if (el.classList.contains('dash-widget')) widgets[el.dataset.widgetId] = el;
        });
        order.forEach(function (id) {
            if (widgets[id]) row.appendChild(widgets[id]);
        });
    }

    function loadLayout() {
        fetch('?page=api_get_finance_dashboard_layout')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var order = data.success && data.data ? data.data.widget_order : null;
                if (!order) return;
                applyOrder(document.getElementById('fnDashCanvasStats'), order.stats);
                applyOrder(document.getElementById('fnDashCanvasContent'), order.content);
            })
            .catch(function (err) {
                console.error('Failed to load dashboard layout:', err);
            });
    }

    function setEditMode(on) {
        document.body.classList.toggle(EDIT_MODE_CLASS, on);
        var btn = document.getElementById('dashEditModeBtn');
        if (btn) {
            btn.classList.toggle('active', on);
            btn.querySelector('i').className = on ? 'bi bi-check-lg' : 'bi bi-pencil-fill';
            var labelEl = btn.querySelector('.dash-edit-label');
            if (labelEl) labelEl.textContent = on ? 'Editing UI...' : 'Edit UI';
        }
        var widgets = document.querySelectorAll('.dash-widget');
        Array.prototype.forEach.call(widgets, function (el) {
            el.draggable = on;
        });
        if (on) {
            preEditSnapshot = collectAllOrders();
            restartJiggle(widgets);
        } else {
            showRevertConfirm();
        }
    }

    function showSavedToast() {
        var toastEl = document.getElementById('dashSavedToast');
        if (!toastEl || !window.bootstrap || !window.bootstrap.Toast) return;
        window.bootstrap.Toast.getOrCreateInstance(toastEl).show();
    }

    // Windows-display-settings-style confirmation: whatever was rearranged
    // during this edit session is already auto-saved after every drag, so
    // "Revert" here means restoring the snapshot taken the moment edit mode
    // was turned ON and re-saving THAT. The 5-second countdown defaults to
    // keeping, matching the other dashboards.
    function showRevertConfirm() {
        var panel = document.getElementById('dashRevertConfirm');
        var editBtn = document.getElementById('dashEditModeBtn');
        if (!panel || !preEditSnapshot) {
            saveLayout();
            showSavedToast();
            return;
        }

        var countdownEl = panel.querySelector('.dash-revert-countdown');
        var keepBtn = panel.querySelector('.dash-revert-keep');
        var undoBtn = panel.querySelector('.dash-revert-undo');
        var seconds = 5;
        countdownEl.textContent = seconds;
        panel.classList.add('show');
        if (editBtn) editBtn.disabled = true;

        function finish(keep) {
            clearInterval(revertCountdownInterval);
            revertCountdownInterval = null;
            panel.classList.remove('show');
            keepBtn.onclick = null;
            undoBtn.onclick = null;
            if (editBtn) editBtn.disabled = false;

            if (!keep) {
                applyOrder(document.getElementById('fnDashCanvasStats'), preEditSnapshot.stats);
                applyOrder(document.getElementById('fnDashCanvasContent'), preEditSnapshot.content);
            }
            preEditSnapshot = null;
            saveLayout();
            showSavedToast();
        }

        keepBtn.onclick = function () { finish(true); };
        undoBtn.onclick = function () { finish(false); };

        clearInterval(revertCountdownInterval);
        revertCountdownInterval = setInterval(function () {
            seconds -= 1;
            countdownEl.textContent = seconds;
            if (seconds <= 0) finish(true);
        }, 1000);
    }

    // Forces the CSS shake animation to (re)start from frame 0 on every
    // widget the instant edit mode turns on, so the "you can move these"
    // cue is always freshly visible on each click of the pen button.
    function restartJiggle(widgets) {
        Array.prototype.forEach.call(widgets, function (el) {
            el.style.animation = 'none';
        });
        // Reading offsetHeight forces layout, flushing the animation:none
        // before we clear it -- without this the browser coalesces both
        // style writes into one frame and the restart never happens.
        void document.body.offsetHeight;
        Array.prototype.forEach.call(widgets, function (el) {
            el.style.animation = '';
        });
    }

    // FLIP (First-Last-Invert-Play): captures every widget's position
    // before the reorder, lets the DOM update instantly, then animates
    // each displaced widget from its old spot to its new one instead of
    // letting it snap there.
    function reorderWithEase(row, movingEl, referenceNode) {
        var others = Array.prototype.filter.call(row.children, function (el) {
            return el.classList.contains('dash-widget') && el !== movingEl;
        });
        var firstRects = others.map(function (el) { return el.getBoundingClientRect(); });

        row.insertBefore(movingEl, referenceNode);

        others.forEach(function (el, i) {
            var first = firstRects[i];
            var last = el.getBoundingClientRect();
            var dx = first.left - last.left;
            var dy = first.top - last.top;
            if (!dx && !dy) return;

            el.style.transition = 'none';
            el.style.transform = 'translate(' + dx + 'px, ' + dy + 'px)';
            void el.offsetWidth; // flush before enabling the transition
            el.style.transition = 'transform 0.28s ease';
            el.style.transform = '';
            el.addEventListener('transitionend', function cleanup() {
                el.style.transition = '';
                el.removeEventListener('transitionend', cleanup);
            });
        });
    }

    // Auto-scroll the page while dragging a widget near the top/bottom
    // edge of the viewport -- native HTML5 drag-and-drop does NOT scroll
    // the page for you.
    var EDGE_ZONE = 90;
    var MAX_SCROLL_SPEED = 22;
    var lastPointerY = 0;
    var autoScrollRAF = null;

    function trackPointerForAutoScroll(e) {
        lastPointerY = e.clientY;
    }

    function autoScrollTick() {
        if (!draggedEl) {
            autoScrollRAF = null;
            return;
        }
        var vh = window.innerHeight;
        if (lastPointerY < EDGE_ZONE) {
            window.scrollBy(0, -MAX_SCROLL_SPEED * (1 - lastPointerY / EDGE_ZONE));
        } else if (lastPointerY > vh - EDGE_ZONE) {
            window.scrollBy(0, MAX_SCROLL_SPEED * (1 - (vh - lastPointerY) / EDGE_ZONE));
        }
        autoScrollRAF = requestAnimationFrame(autoScrollTick);
    }

    function handleDragStart(e) {
        // Links etc. inside a widget are natively draggable regardless of
        // our own draggable=true/false toggle, so without this guard a
        // "View All" link dragged while edit mode is OFF would still
        // bubble up here and silently reorder (and auto-save!).
        if (!document.body.classList.contains(EDIT_MODE_CLASS)) return;
        var widget = e.target.closest('.dash-widget');
        if (!widget) return;
        draggedEl = widget;
        draggedRow = widget.closest('.dash-canvas-row');
        widget.classList.add('dash-dragging');
        e.dataTransfer.effectAllowed = 'move';
        // Firefox requires setData to allow the drag to start at all
        e.dataTransfer.setData('text/plain', widget.dataset.widgetId);

        lastPointerY = e.clientY;
        if (!autoScrollRAF) autoScrollRAF = requestAnimationFrame(autoScrollTick);
    }

    function handleDragOver(e) {
        if (!draggedEl) return;
        var row = e.currentTarget;
        if (row !== draggedRow) return; // widgets never cross rows
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        var target = e.target.closest('.dash-widget');
        if (!target || target === draggedEl || target.closest('.dash-canvas-row') !== row) return;

        var rect = target.getBoundingClientRect();
        var isAfter = e.clientX > rect.left + rect.width / 2;
        var referenceNode = isAfter ? target.nextSibling : target;
        if (referenceNode === draggedEl) return; // already in this slot

        reorderWithEase(row, draggedEl, referenceNode);
    }

    function handleDragEnd() {
        if (draggedEl) draggedEl.classList.remove('dash-dragging');
        if (draggedEl && draggedRow) saveLayout();
        draggedEl = null;
        draggedRow = null;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var editBtn = document.getElementById('dashEditModeBtn');
        if (editBtn) {
            editBtn.addEventListener('click', function () {
                setEditMode(!document.body.classList.contains(EDIT_MODE_CLASS));
            });
        }

        document.addEventListener('dragstart', handleDragStart);
        document.addEventListener('dragend', handleDragEnd);
        document.addEventListener('dragover', trackPointerForAutoScroll);

        function wireCanvasRows() {
            Array.prototype.forEach.call(document.querySelectorAll('.dash-canvas-row'), function (row) {
                row.addEventListener('dragover', handleDragOver);
            });
            loadLayout();
        }

        // dashboard.js renders the canvas rows, usually synchronously from
        // server-provided __INITIAL_DATA__ before this listener is even
        // registered -- so waiting on the rendered event alone would miss
        // it and nothing would become draggable. Wire up immediately if
        // the rows already exist; otherwise fall back to the event. Both
        // Finance dashboards are covered.
        if (document.getElementById('fnDashCanvasStats')) {
            wireCanvasRows();
        } else {
            document.addEventListener('fn-staff-dashboard-rendered', wireCanvasRows);
            document.addEventListener('fn-head-dashboard-rendered', wireCanvasRows);
        }
    });
})();
