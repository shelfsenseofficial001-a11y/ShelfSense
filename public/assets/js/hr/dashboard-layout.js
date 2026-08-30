// ============================================
// HR DASHBOARD - DRAG-TO-REORDER EDIT MODE
// Each .dash-canvas-row is an independent sortable zone (widgets never
// move between rows, only within their own row). The resulting order is
// saved per account via api_save_dashboard_layout and restored on load
// via api_get_dashboard_layout.
//
// The tables and charts used to be two separate rows/groups; they're now
// one merged "content" row (uniform-height cards) so users can freely mix
// tables and charts together. collectAllOrders()/loadLayout() still read
// a legacy pre-merge save (separate "tables"/"charts" arrays) so older
// saved layouts don't silently reset.
// ============================================

(function () {
    var EDIT_MODE_CLASS = 'dash-edit-mode';
    var draggedEl = null;
    var draggedRow = null;

    function getRowOrder(row) {
        return Array.prototype.filter.call(row.children, function (el) {
            return el.classList.contains('dash-widget');
        }).map(function (el) { return el.dataset.widgetId; });
    }

    function collectAllOrders() {
        return {
            stats: getRowOrder(document.getElementById('dashCanvasStats')),
            content: getRowOrder(document.getElementById('dashCanvasTables')),
        };
    }

    function saveLayout() {
        fetch('?page=api_save_dashboard_layout', {
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
        fetch('?page=api_get_dashboard_layout')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var order = data.success && data.data ? data.data.widget_order : null;
                if (!order) return;
                applyOrder(document.getElementById('dashCanvasStats'), order.stats);
                // Legacy saves had "tables" and "charts" as two separate
                // arrays; the current single merged row accepts a
                // "content" array, falling back to the old pair appended
                // in order (tables first, then charts) if that's all a
                // previously-saved layout has.
                var contentOrder = order.content || [].concat(order.tables || [], order.charts || []);
                applyOrder(document.getElementById('dashCanvasTables'), contentOrder);
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
            restartJiggle(widgets);
        } else {
            showSavedToast();
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

    function handleDragStart(e) {
        var widget = e.target.closest('.dash-widget');
        if (!widget) return;
        draggedEl = widget;
        draggedRow = widget.closest('.dash-canvas-row');
        widget.classList.add('dash-dragging');
        e.dataTransfer.effectAllowed = 'move';
        // Firefox requires setData to allow the drag to start at all
        e.dataTransfer.setData('text/plain', widget.dataset.widgetId);
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
        loadLayout();

        var editBtn = document.getElementById('dashEditModeBtn');
        if (editBtn) {
            editBtn.addEventListener('click', function () {
                setEditMode(!document.body.classList.contains(EDIT_MODE_CLASS));
            });
        }

        Array.prototype.forEach.call(document.querySelectorAll('.dash-canvas-row'), function (row) {
            row.addEventListener('dragover', handleDragOver);
        });
        document.addEventListener('dragstart', handleDragStart);
        document.addEventListener('dragend', handleDragEnd);
    });
})();
