// ============================================
// DATE PICKER MODAL (Year / Month / Day, or Year / Month)
// ============================================
// Progressive replacement for native <input type="date"> / type="month">:
// every such input on the page is auto-upgraded into a read-only display
// field that opens a Bootstrap modal calendar (or month grid) instead of
// the browser's native picker.
//
// The original <input> is kept in the DOM (as type="hidden", same
// id/name/min/max/required) so every existing getElementById(...).value
// read/write, FormData collection, and 'change'/'input' listener
// elsewhere in the app keeps working completely unchanged. This file is
// the only thing a page needs to load to get the new picker -- no other
// code has to change, and any NEW date field added later just needs
// <input type="date"> (or type="month") plus this script already loaded
// by the layout.

(function () {
    const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const DAY_HEADERS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

    function pad(n) { return String(n).padStart(2, '0'); }

    function parseISO(str) {
        if (!str) return null;
        const parts = String(str).split('-').map(Number);
        if (parts.length === 3 && parts.every(n => !isNaN(n))) return { y: parts[0], m: parts[1], d: parts[2] };
        if (parts.length === 2 && parts.every(n => !isNaN(n))) return { y: parts[0], m: parts[1], d: null };
        return null;
    }

    // Compares two {y,m,d} points; d may be null (month-granularity compare).
    function cmp(a, b) {
        const av = a.y * 10000 + a.m * 100 + (a.d || 0);
        const bv = b.y * 10000 + b.m * 100 + (b.d || 0);
        return av - bv;
    }

    function daysInMonth(y, m) {
        return new Date(y, m, 0).getDate();
    }

    let stackFixInstalled = false;
    function installModalStackFix() {
        if (stackFixInstalled) return;
        stackFixInstalled = true;
        // Bootstrap doesn't natively support one modal opening on top of
        // another (e.g. this picker opening from inside a "Create X" form's
        // own modal) -- the second modal's own backdrop paints ABOVE it,
        // hiding it. Standard fix: bump the new modal + its backdrop above
        // whatever's already open.
        document.addEventListener('show.bs.modal', function (e) {
            if (document.querySelectorAll('.modal.show').length > 0) {
                e.target.classList.add('dp-stacked-modal');
            }
        });
        document.addEventListener('shown.bs.modal', function () {
            const shown = document.querySelectorAll('.modal.show');
            const backdrops = document.querySelectorAll('.modal-backdrop:not(.dp-stacked-backdrop)');
            if (shown.length > 1 && backdrops.length > 0) {
                backdrops[backdrops.length - 1].classList.add('dp-stacked-backdrop');
            }
        });
    }

    class DatePickerModal {
        constructor(input) {
            this.original = input;
            this.mode = input.type === 'month' ? 'month' : 'date';
            this.id = input.id || ('dp' + Math.random().toString(36).slice(2));
            this._value = input.value || input.getAttribute('value') || '';
            this.viewYear = null;
            this.viewMonth = null;

            this.build();
        }

        build() {
            const input = this.original;
            const placeholder = input.getAttribute('placeholder') || (this.mode === 'month' ? 'Select month' : 'Select date');

            const wrapper = document.createElement('div');
            wrapper.className = 'date-picker-wrapper';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            input.type = 'hidden';

            const display = document.createElement('input');
            display.type = 'text';
            display.className = 'form-control date-picker-display';
            display.readOnly = true;
            display.placeholder = placeholder;
            display.autocomplete = 'off';
            if (input.disabled) display.disabled = true;
            wrapper.appendChild(display);

            const icon = document.createElement('span');
            icon.className = 'date-picker-icon';
            icon.innerHTML = '<i class="bi bi-calendar-event"></i>';
            wrapper.appendChild(icon);

            this.wrapper = wrapper;
            this.display = display;

            // Intercept .value on the original (now hidden) input so any
            // other script that reads/sets it directly by id keeps working
            // and stays in sync with the visible display -- regardless of
            // whether that other script runs before or after this file's
            // own DOMContentLoaded handler.
            const self = this;
            Object.defineProperty(input, 'value', {
                get() { return self._value; },
                set(v) { self._value = v || ''; self.updateDisplay(); },
                configurable: true
            });

            this.updateDisplay();

            display.addEventListener('click', () => { if (!input.disabled) this.open(); });

            this.modalEl = this.buildModal();
            document.body.appendChild(this.modalEl);
            input.datePickerInstance = this;
        }

        updateDisplay() {
            const parsed = parseISO(this._value);
            if (!parsed) { this.display.value = ''; return; }
            if (this.mode === 'month') {
                this.display.value = MONTH_NAMES[parsed.m - 1] + ' ' + parsed.y;
            } else {
                const d = new Date(parsed.y, parsed.m - 1, parsed.d);
                this.display.value = d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            }
        }

        getBounds() {
            return {
                min: parseISO(this.original.min || this.original.getAttribute('min')),
                max: parseISO(this.original.max || this.original.getAttribute('max'))
            };
        }

        buildModal() {
            const el = document.createElement('div');
            el.className = 'modal fade date-picker-modal-el';
            el.id = this.id + 'PickerModal';
            el.tabIndex = -1;
            el.setAttribute('aria-hidden', 'true');
            el.innerHTML = `
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title mb-0"><i class="bi bi-calendar-event me-2"></i>${this.mode === 'month' ? 'Select Month' : 'Select Date'}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="dp-yr-mo-row d-flex gap-2 mb-2">
                                <select class="form-select form-select-sm dp-month-select"></select>
                                <select class="form-select form-select-sm dp-year-select"></select>
                            </div>
                            <div class="dp-day-grid"></div>
                            <div class="dp-month-grid"></div>
                        </div>
                        <div class="modal-footer py-2 d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-outline-secondary dp-today-btn">Today</button>
                            <div>
                                ${!this.original.required ? '<button type="button" class="btn btn-sm btn-outline-secondary me-2 dp-clear-btn">Clear</button>' : ''}
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const monthSelect = el.querySelector('.dp-month-select');
            const yearSelect = el.querySelector('.dp-year-select');
            const dayGrid = el.querySelector('.dp-day-grid');
            const monthGrid = el.querySelector('.dp-month-grid');

            if (this.mode === 'month') {
                dayGrid.style.display = 'none';
                monthSelect.style.display = 'none';
            } else {
                monthGrid.style.display = 'none';
                MONTH_NAMES.forEach((name, i) => {
                    const opt = document.createElement('option');
                    opt.value = i + 1;
                    opt.textContent = name;
                    monthSelect.appendChild(opt);
                });
                monthSelect.addEventListener('change', () => {
                    this.viewMonth = parseInt(monthSelect.value, 10);
                    this.renderDayGrid();
                });
            }

            yearSelect.addEventListener('change', () => {
                this.viewYear = parseInt(yearSelect.value, 10);
                if (this.mode === 'month') this.renderMonthGrid(); else this.renderDayGrid();
            });

            el.querySelector('.dp-today-btn').addEventListener('click', () => {
                const t = new Date();
                this.viewYear = t.getFullYear();
                this.viewMonth = t.getMonth() + 1;
                this.syncSelects();
                if (this.mode === 'month') this.renderMonthGrid(); else this.renderDayGrid();
            });

            const clearBtn = el.querySelector('.dp-clear-btn');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => { this.confirm(''); });
            }

            this.monthSelect = monthSelect;
            this.yearSelect = yearSelect;
            this.dayGrid = dayGrid;
            this.monthGrid = monthGrid;

            return el;
        }

        yearRange() {
            const { min, max } = this.getBounds();
            const nowY = new Date().getFullYear();
            const start = min ? min.y : nowY - 5;
            const end = max ? max.y : nowY + 5;
            return { start: Math.min(start, nowY), end: Math.max(end, nowY) };
        }

        syncSelects() {
            const { start, end } = this.yearRange();
            this.yearSelect.innerHTML = '';
            for (let y = start; y <= end; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === this.viewYear) opt.selected = true;
                this.yearSelect.appendChild(opt);
            }
            if (this.monthSelect) this.monthSelect.value = this.viewMonth;
        }

        open() {
            const parsed = parseISO(this._value);
            const today = new Date();
            this.viewYear = parsed ? parsed.y : today.getFullYear();
            this.viewMonth = parsed ? parsed.m : today.getMonth() + 1;
            this.syncSelects();
            if (this.mode === 'month') this.renderMonthGrid(); else this.renderDayGrid();

            installModalStackFix();
            bootstrap.Modal.getOrCreateInstance(this.modalEl).show();
        }

        renderDayGrid() {
            const { min, max } = this.getBounds();
            const y = this.viewYear, m = this.viewMonth;
            const selected = parseISO(this._value);
            const today = new Date();
            const firstWeekday = (new Date(y, m - 1, 1).getDay() + 6) % 7; // Monday-first
            const total = daysInMonth(y, m);

            let html = '<div class="dp-day-headrow">' + DAY_HEADERS.map(h => `<div class="dp-day-head">${h}</div>`).join('') + '</div><div class="dp-day-body">';
            for (let i = 0; i < firstWeekday; i++) html += '<div class="dp-day-cell dp-day-blank"></div>';
            for (let d = 1; d <= total; d++) {
                const cellDate = { y, m, d };
                const disabled = (min && cmp(cellDate, min) < 0) || (max && cmp(cellDate, max) > 0);
                const isSelected = !!(selected && selected.y === y && selected.m === m && selected.d === d);
                const isToday = today.getFullYear() === y && today.getMonth() + 1 === m && today.getDate() === d;
                let cls = 'dp-day-cell';
                if (disabled) cls += ' dp-day-cell-disabled';
                if (isSelected) cls += ' dp-day-cell-selected';
                if (isToday) cls += ' dp-day-cell-today';
                html += `<div class="${cls}" data-day="${d}">${d}</div>`;
            }
            html += '</div>';
            this.dayGrid.innerHTML = html;

            this.dayGrid.querySelectorAll('.dp-day-cell[data-day]:not(.dp-day-cell-disabled)').forEach(cell => {
                cell.addEventListener('click', () => {
                    const d = parseInt(cell.dataset.day, 10);
                    this.confirm(`${this.viewYear}-${pad(this.viewMonth)}-${pad(d)}`);
                });
            });
        }

        renderMonthGrid() {
            const { min, max } = this.getBounds();
            const y = this.viewYear;
            const selected = parseISO(this._value);
            let html = '';
            MONTH_NAMES.forEach((name, i) => {
                const m = i + 1;
                const cellPoint = { y, m, d: null };
                const disabled = (min && cmp(cellPoint, { y: min.y, m: min.m, d: null }) < 0) || (max && cmp(cellPoint, { y: max.y, m: max.m, d: null }) > 0);
                const isSelected = !!(selected && selected.y === y && selected.m === m);
                let cls = 'dp-month-cell';
                if (disabled) cls += ' dp-month-cell-disabled';
                if (isSelected) cls += ' dp-month-cell-selected';
                html += `<div class="${cls}" data-month="${m}">${name.slice(0, 3)}</div>`;
            });
            this.monthGrid.innerHTML = html;

            this.monthGrid.querySelectorAll('.dp-month-cell:not(.dp-month-cell-disabled)').forEach(cell => {
                cell.addEventListener('click', () => {
                    const m = parseInt(cell.dataset.month, 10);
                    this.confirm(`${y}-${pad(m)}`);
                });
            });
        }

        confirm(value) {
            this.original.value = value; // via the overridden setter -- updates the display too
            this.original.dispatchEvent(new Event('input', { bubbles: true }));
            this.original.dispatchEvent(new Event('change', { bubbles: true }));
            const modal = bootstrap.Modal.getInstance(this.modalEl);
            if (modal) modal.hide();
        }
    }

    function upgradeAll(root) {
        (root || document).querySelectorAll('input[type="date"], input[type="month"]').forEach(input => {
            if (input.dataset.datePickerUpgraded) return;
            input.dataset.datePickerUpgraded = 'true';
            new DatePickerModal(input);
        });
    }

    document.addEventListener('DOMContentLoaded', () => upgradeAll());

    // For date/month fields injected into the DOM after initial page load
    // (e.g. a form built dynamically in JS) -- call this once the new
    // <input type="date"> / type="month"> element exists in the DOM.
    window.upgradeDatePickers = function (root) { upgradeAll(root); };
})();
