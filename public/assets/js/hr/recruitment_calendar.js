(function () {
    'use strict';

    const monthLabelEl = document.getElementById('rcMonthLabel');
    const gridEl = document.getElementById('rcGrid');
    const prevBtn = document.getElementById('rcPrevMonth');
    const nextBtn = document.getElementById('rcNextMonth');
    const todayBtn = document.getElementById('rcTodayBtn');

    if (!gridEl) return;

    const today = new Date();
    let viewYear = today.getFullYear();
    let viewMonth = today.getMonth(); // 0-indexed

    const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    const EVENT_CLASS = {
        initial_interview: 'rc-event-initial',
        final_interview: 'rc-event-final',
        posting_opened: 'rc-event-opened',
        posting_closes: 'rc-event-closes'
    };

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function monthKey(year, month) {
        return year + '-' + pad(month + 1);
    }

    function isSameDay(y, m, d) {
        return y === today.getFullYear() && m === today.getMonth() && d === today.getDate();
    }

    function renderCalendar(eventsByDate) {
        monthLabelEl.textContent = MONTH_NAMES[viewMonth] + ' ' + viewYear;

        const firstOfMonth = new Date(viewYear, viewMonth, 1);
        // JS getDay(): 0=Sun..6=Sat. We want Monday-first columns.
        const firstWeekday = (firstOfMonth.getDay() + 6) % 7;
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        let html = '';
        for (let i = 0; i < firstWeekday; i++) {
            html += '<div class="rc-cell rc-empty"></div>';
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = viewYear + '-' + pad(viewMonth + 1) + '-' + pad(day);
            const events = eventsByDate[dateStr] || [];
            const todayClass = isSameDay(viewYear, viewMonth, day) ? ' rc-today' : '';

            let eventsHtml = '';
            events.slice(0, 3).forEach(function (ev) {
                const cls = EVENT_CLASS[ev.type] || '';
                const timePrefix = ev.time ? ev.time + ' ' : '';
                const metaLine = ev.meta ? '<small>' + escapeHtml(ev.meta) + '</small>' : '';
                eventsHtml += '<div class="rc-event ' + cls + '" title="' + escapeHtml(timePrefix + ev.label) + '">'
                    + (ev.time ? '<strong>' + escapeHtml(ev.time) + '</strong> ' : '')
                    + escapeHtml(ev.label) + metaLine + '</div>';
            });
            if (events.length > 3) {
                eventsHtml += '<div class="rc-event text-muted">+' + (events.length - 3) + ' more</div>';
            }

            html += '<div class="rc-cell' + todayClass + '"><div class="rc-daynum">' + day + '</div>' + eventsHtml + '</div>';
        }

        gridEl.innerHTML = html;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function loadMonth() {
        gridEl.innerHTML = '<div class="text-center text-muted py-5" style="grid-column: 1 / -1;"><div class="spinner-border text-primary" role="status"></div></div>';
        monthLabelEl.textContent = MONTH_NAMES[viewMonth] + ' ' + viewYear;

        fetch('?page=api_hr_get_recruitment_calendar&month=' + encodeURIComponent(monthKey(viewYear, viewMonth)))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    gridEl.innerHTML = '<div class="text-center text-danger py-5" style="grid-column: 1 / -1;">' + escapeHtml(data.message || 'Failed to load calendar') + '</div>';
                    return;
                }
                renderCalendar(data.data.events_by_date || {});
            })
            .catch(function () {
                gridEl.innerHTML = '<div class="text-center text-danger py-5" style="grid-column: 1 / -1;">Failed to load calendar</div>';
            });
    }

    prevBtn.addEventListener('click', function () {
        viewMonth -= 1;
        if (viewMonth < 0) { viewMonth = 11; viewYear -= 1; }
        loadMonth();
    });

    nextBtn.addEventListener('click', function () {
        viewMonth += 1;
        if (viewMonth > 11) { viewMonth = 0; viewYear += 1; }
        loadMonth();
    });

    todayBtn.addEventListener('click', function () {
        viewYear = today.getFullYear();
        viewMonth = today.getMonth();
        loadMonth();
    });

    loadMonth();
})();
