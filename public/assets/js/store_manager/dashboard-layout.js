// ============================================
// STORE MANAGER DASHBOARD - DRAG-TO-REORDER EDIT MODE
// Same mechanic as the HR dashboard (see hr/dashboard-layout.js): each
// .dash-canvas-row is an independent sortable zone (widgets never move
// between rows, only within their own row). The resulting order is saved
// per account via api_save_store_manager_dashboard_layout and restored on
// load via api_get_store_manager_dashboard_layout.
// ============================================

(function () {
    var EDIT_MODE_CLASS = 'dash-edit-mode';
    var draggedEl = null;
    var draggedRow = null;
    var preEditSnapshot = null;
    var revertCountdownInterval = null;

    function getRowOrder(row) {
        return Array.prototype.filter.call(row.children, function (el) {
            return el.classList.contains('dash-widget');
        }).map(function (el) { return el.dataset.widgetId; });
    }

    function collectAllOrders() {
        return {
            stats: getRowOrder(document.getElementById('smDashCanvasStats')),
            content: getRowOrder(document.getElementById('smDashCanvasContent')),
        };
    }

    function saveLayout() {
        fetch('?page=api_save_store_manager_dashboard_layout', {
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
        fetch('?page=api_get_store_manager_dashboard_layout')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var order = data.success && data.data ? data.data.widget_order : null;
                if (!order) return;
                applyOrder(document.getElementById('smDashCanvasStats'), order.stats);
                applyOrder(document.getElementById('smDashCanvasContent'), order.content);
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

    // Shown top-left when the user turns edit mode back off, confirming
    // any rearranging they did during this session is persisted.
    function showSavedToast() {
        var toastEl = document.getElementById('dashSavedToast');
        if (!toastEl || !window.bootstrap || !window.bootstrap.Toast) return;
        var toast = window.bootstrap.Toast.getOrCreateInstance(toastEl);
        toast.show();
    }

    // Windows-display-settings-style confirmation: whatever was rearranged
    // during this edit session is already auto-saved after every drag (see
    // handleDragEnd), so "Revert" here means restoring the snapshot taken
    // the moment edit mode was turned ON and re-saving THAT. A 5-second
    // countdown auto-confirms "Keep" if the user doesn't respond -- unlike
    // Windows' own dialog (which defaults to reverting), this one defaults
    // to keeping, per how it was specced.
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
                applyOrder(document.getElementById('smDashCanvasStats'), preEditSnapshot.stats);
                applyOrder(document.getElementById('smDashCanvasContent'), preEditSnapshot.content);
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
    // cue is always freshly visible on each click of the pen button --
    // not just left running from whenever it first started.
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
    // letting it snap there. Only the widgets that actually moved (not
    // the one under the cursor) get the eased slide.
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
    // the page for you, so without this a widget can only be dropped
    // among whatever's already visible on screen. Tracks the pointer via
    // a page-wide dragover listener (separate from handleDragOver, which
    // only fires on the widget's own row and bails out early for
    // everything else) and nudges window.scrollY every animation frame
    // while a drag is active and the pointer sits inside either edge zone.
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
        // Page-wide, unlike the row-scoped listeners in handleDragOver --
        // needed so the auto-scroll edge zones work even when the pointer
        // is over a different row, a gap between cards, or the sidebar.
        document.addEventListener('dragover', trackPointerForAutoScroll);

        // The dashboard's own canvas rows are injected async by
        // dashboard.js after its fetch resolves, so wiring dragover
        // listeners and loading the saved layout has to wait for that
        // instead of running at DOMContentLoaded.
        document.addEventListener('sm-dashboard-rendered', function () {
            Array.prototype.forEach.call(document.querySelectorAll('.dash-canvas-row'), function (row) {
                row.addEventListener('dragover', handleDragOver);
            });
            loadLayout();
        });
    });
})();
