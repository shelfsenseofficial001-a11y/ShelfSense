// ============================================
// DATE PICKER MODAL (Year / Month / Day, Year / Month, or
// Year / Month / Day / Time)
// ============================================
// Progressive replacement for native <input type="date"> / type="month">
// / type="datetime-local">: every such input on the page is auto-upgraded
// into a read-only display field that opens a Bootstrap modal calendar
// (plus an Hour/Minute/AM-PM row for datetime-local) instead of the
// browser's native picker.
//
// The original <input> is kept in the DOM (as type="hidden", same
// id/name/min/max/required) so every existing getElementById(...).value
// read/write, FormData collection, and 'change'/'input' listener
// elsewhere in the app keeps working completely unchanged. This file is
// the only thing a page needs to load to get the new picker -- no other
// code has to change, and any NEW date field added later just needs
// <input type="date"> (or type="month"/"datetime-local") plus this script
// already loaded by the layout.

(function () {
    const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const DAY_HEADERS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

    function pad(n) { return String(n).padStart(2, '0'); }

    // Parses "YYYY-MM-DD", "YYYY-MM", or a datetime-local "YYYY-MM-DDTHH:MM".
    // hh/mm are null whenever there's no time portion (plain date/month).
    function parseISO(str) {
        if (!str) return null;
        const [datePart, timePart] = String(str).split('T');
        const parts = datePart.split('-').map(Number);
        let result = null;
        if (parts.length === 3 && parts.every(n => !isNaN(n))) result = { y: parts[0], m: parts[1], d: parts[2], hh: null, mm: null };
        else if (parts.length === 2 && parts.every(n => !isNaN(n))) result = { y: parts[0], m: parts[1], d: null, hh: null, mm: null };
        if (!result) return null;
        if (timePart) {
            const t = timePart.split(':').map(Number);
            if (t.length >= 2 && !isNaN(t[0]) && !isNaN(t[1])) {
                result.hh = t[0];
                result.mm = t[1];
            }
        }
        return result;
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
            this.mode = input.type === 'month' ? 'month' : (input.type === 'datetime-local' ? 'datetime' : 'date');
            this.id = input.id || ('dp' + Math.random().toString(36).slice(2));
            this._value = input.value || input.getAttribute('value') || '';
            this.viewYear = null;
            this.viewMonth = null;
            this.pickedDate = null; // datetime mode only: {y,m,d} chosen so far, separate from the confirmed _value

            this.build();
        }

        build() {
            const input = this.original;
            const placeholder = input.getAttribute('placeholder') || (this.mode === 'month' ? 'Select month' : (this.mode === 'datetime' ? 'Select date & time' : 'Select date'));

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
            // own DOMContentLoaded handler. Also mirror every set into the
            // real `value` content attribute -- for a hidden input that
            // attribute IS the spec-defined backing store for its value, so
            // native browser mechanisms that don't go through this JS
            // getter/setter (new FormData(formElement), form.submit(),
            // checkValidity()) still see the picked date instead of
            // whatever the attribute was left at (usually empty).
            const self = this;
            Object.defineProperty(input, 'value', {
                get() { return self._value; },
                set(v) {
                    self._value = v || '';
                    input.setAttribute('value', self._value);
                    self.updateDisplay();
                },
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
            } else if (this.mode === 'datetime') {
                if (parsed.hh === null || parsed.mm === null) { this.display.value = ''; return; }
                const d = new Date(parsed.y, parsed.m - 1, parsed.d, parsed.hh, parsed.mm);
                this.display.value = d.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
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
            const isDatetime = this.mode === 'datetime';
            const el = document.createElement('div');
            el.className = 'modal fade date-picker-modal-el';
            el.id = this.id + 'PickerModal';
            el.tabIndex = -1;
            el.setAttribute('aria-hidden', 'true');
            el.innerHTML = `
                <div class="modal-dialog modal-dialog-centered ${isDatetime ? '' : 'modal-sm'}">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title mb-0"><i class="bi bi-calendar-event me-2"></i>${this.mode === 'month' ? 'Select Month' : (isDatetime ? 'Select Date & Time' : 'Select Date')}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="dp-yr-mo-row d-flex gap-2 mb-2">
                                <select class="form-select form-select-sm dp-month-select"></select>
                                <div class="dp-year-combo">
                                    <input type="text" class="form-control form-control-sm dp-year-input" inputmode="numeric" autocomplete="off" placeholder="Year">
                                    <div class="dp-year-list"></div>
                                </div>
                            </div>
                            <div class="dp-day-grid"></div>
                            <div class="dp-month-grid"></div>
                            <div class="dp-time-row d-flex gap-2 mt-2" style="display:none;">
                                <select class="form-select form-select-sm dp-hour-select"><option value="">HH</option></select>
                                <select class="form-select form-select-sm dp-minute-select"><option value="">MM</option></select>
                                <select class="form-select form-select-sm dp-ampm-select">
                                    <option value="">--</option>
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer py-2 d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-outline-secondary dp-today-btn">Today</button>
                            <div>
                                ${!this.original.required ? '<button type="button" class="btn btn-sm btn-outline-secondary me-2 dp-clear-btn">Clear</button>' : ''}
                                <button type="button" class="btn btn-sm btn-outline-secondary${isDatetime ? ' me-2' : ''}" data-bs-dismiss="modal">Cancel</button>
                                ${isDatetime ? '<button type="button" class="btn btn-sm btn-yellow-primary dp-set-btn">Set</button>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const monthSelect = el.querySelector('.dp-month-select');
            const yearCombo = el.querySelector('.dp-year-combo');
            const yearInput = el.querySelector('.dp-year-input');
            const yearList = el.querySelector('.dp-year-list');
            const dayGrid = el.querySelector('.dp-day-grid');
            const monthGrid = el.querySelector('.dp-month-grid');
            const timeRow = el.querySelector('.dp-time-row');
            const hourSelect = el.querySelector('.dp-hour-select');
            const minuteSelect = el.querySelector('.dp-minute-select');
            const ampmSelect = el.querySelector('.dp-ampm-select');

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

            if (isDatetime) {
                timeRow.style.display = 'flex';
                for (let h = 1; h <= 12; h++) {
                    const opt = document.createElement('option');
                    opt.value = h;
                    opt.textContent = pad(h);
                    hourSelect.appendChild(opt);
                }
                for (let mi = 0; mi < 60; mi++) {
                    const opt = document.createElement('option');
                    opt.value = mi;
                    opt.textContent = pad(mi);
                    minuteSelect.appendChild(opt);
                }
                hourSelect.addEventListener('change', () => this.applyTimeBounds());
                ampmSelect.addEventListener('change', () => this.applyTimeBounds());

                const setBtn = el.querySelector('.dp-set-btn');
                setBtn.addEventListener('click', () => {
                    if (!this.pickedDate) { this.flashInvalid(this.dayGrid); return; }
                    const hh12 = parseInt(hourSelect.value, 10);
                    const mm = parseInt(minuteSelect.value, 10);
                    const ampm = ampmSelect.value;
                    if (isNaN(hh12) || isNaN(mm) || !ampm) { this.flashInvalid(timeRow); return; }
                    let hh24 = hh12 % 12;
                    if (ampm === 'PM') hh24 += 12;
                    // Catches a selection made before the bound-disabling
                    // above kicked in (e.g. hour/AM-PM picked in an order
                    // that left a since-invalidated minute still selected).
                    const { min, max } = this.getBounds();
                    const pd = this.pickedDate;
                    const isMinDay = min && min.d != null && pd.y === min.y && pd.m === min.m && pd.d === min.d && min.hh !== null;
                    const isMaxDay = max && max.d != null && pd.y === max.y && pd.m === max.m && pd.d === max.d && max.hh !== null;
                    const minTotal = isMinDay ? min.hh * 60 + min.mm : 0;
                    const maxTotal = isMaxDay ? max.hh * 60 + max.mm : 23 * 60 + 59;
                    const total = hh24 * 60 + mm;
                    if (total < minTotal || total > maxTotal) { this.flashInvalid(timeRow); return; }
                    const { y, m, d } = this.pickedDate;
                    this.confirm(`${y}-${pad(m)}-${pad(d)}T${pad(hh24)}:${pad(mm)}`);
                });
            }

            // Year as a type-to-filter combo instead of a plain <select> --
            // scrolling through 60-80 <option>s (e.g. a birthdate's 18-80
            // year window) is painful; typing a few digits and picking from
            // the narrowed list is much faster.
            yearInput.addEventListener('focus', () => this.openYearList());
            yearInput.addEventListener('click', () => this.openYearList());
            yearInput.addEventListener('input', () => this.renderYearList(yearInput.value.trim()));
            yearInput.addEventListener('keydown', (e) => this.handleYearKeydown(e));
            yearInput.addEventListener('blur', () => {
                // Let a click on a list item register (it runs in the
                // 'mousedown' phase, before this blur) before closing/
                // resolving -- otherwise the list disappears first and the
                // click never lands on anything.
                setTimeout(() => this.resolveYearInput(), 150);
            });
            document.addEventListener('click', (e) => {
                if (!yearCombo.contains(e.target)) this.closeYearList();
            });

            el.querySelector('.dp-today-btn').addEventListener('click', () => {
                const t = new Date();
                this.viewYear = t.getFullYear();
                this.viewMonth = t.getMonth() + 1;
                this.yearUnset = false;
                if (this.mode === 'datetime') this.pickedDate = { y: this.viewYear, m: this.viewMonth, d: t.getDate() };
                this.syncSelects();
                if (this.mode === 'month') this.renderMonthGrid(); else this.renderDayGrid();
                if (this.mode === 'datetime') this.applyTimeBounds();
            });

            const clearBtn = el.querySelector('.dp-clear-btn');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => { this.confirm(''); });
            }

            this.monthSelect = monthSelect;
            this.yearCombo = yearCombo;
            this.yearInput = yearInput;
            this.yearList = yearList;
            this.dayGrid = dayGrid;
            this.monthGrid = monthGrid;
            this.timeRow = timeRow;
            this.hourSelect = hourSelect;
            this.minuteSelect = minuteSelect;
            this.ampmSelect = ampmSelect;
            this.yearListOpen = false;
            this.yearHighlight = -1;

            return el;
        }

        flashInvalid(el) {
            el.classList.add('dp-invalid-flash');
            setTimeout(() => el.classList.remove('dp-invalid-flash'), 600);
        }

        // Sets the Hour/Minute/AM-PM selects from an existing value (or
        // blank, for a field that's never had one -- same "don't look
        // already chosen" rule as the year combo) and applies bounds.
        initTimeSelects(parsed) {
            if (parsed && parsed.hh !== null && parsed.mm !== null) {
                const ampm = parsed.hh >= 12 ? 'PM' : 'AM';
                const hh12 = parsed.hh % 12 === 0 ? 12 : parsed.hh % 12;
                this.hourSelect.value = hh12;
                this.minuteSelect.value = parsed.mm;
                this.ampmSelect.value = ampm;
            } else {
                this.hourSelect.value = '';
                this.minuteSelect.value = '';
                this.ampmSelect.value = '';
            }
            this.applyTimeBounds();
        }

        // Disables Hour/Minute options that would fall outside min/max --
        // only actually restrictive on the exact boundary day (e.g. the
        // earliest allowed day also carries "no earlier than 9:00 AM"); any
        // other day has no time-of-day restriction.
        applyTimeBounds() {
            if (this.mode !== 'datetime' || !this.pickedDate) return;
            const { min, max } = this.getBounds();
            const pd = this.pickedDate;
            const isMinDay = min && min.d != null && pd.y === min.y && pd.m === min.m && pd.d === min.d && min.hh !== null;
            const isMaxDay = max && max.d != null && pd.y === max.y && pd.m === max.m && pd.d === max.d && max.hh !== null;
            const minTotal = isMinDay ? min.hh * 60 + min.mm : 0;
            const maxTotal = isMaxDay ? max.hh * 60 + max.mm : 23 * 60 + 59;
            const ampm = this.ampmSelect.value;

            Array.from(this.hourSelect.options).forEach(opt => {
                if (!opt.value) { opt.disabled = false; return; }
                if (!ampm) { opt.disabled = false; return; } // can't resolve to a real 24h hour yet
                const hh12 = parseInt(opt.value, 10);
                let hh24 = hh12 % 12;
                if (ampm === 'PM') hh24 += 12;
                opt.disabled = (hh24 * 60 + 59) < minTotal || (hh24 * 60) > maxTotal;
            });

            const hh12 = parseInt(this.hourSelect.value, 10);
            Array.from(this.minuteSelect.options).forEach(opt => {
                if (!opt.value) { opt.disabled = false; return; }
                if (isNaN(hh12) || !ampm) { opt.disabled = false; return; }
                let hh24 = hh12 % 12;
                if (ampm === 'PM') hh24 += 12;
                const total = hh24 * 60 + parseInt(opt.value, 10);
                opt.disabled = total < minTotal || total > maxTotal;
            });
        }

        yearRange() {
            // Only fall back to a window centered on "now" for whichever
            // bound wasn't explicitly set -- an explicit min/max (e.g. a
            // birthdate's 18-80-years-ago window, entirely in the past)
            // must not get stretched to also include the current year, or
            // the dropdown offers years the field can never actually accept.
            const { min, max } = this.getBounds();
            const nowY = new Date().getFullYear();
            const start = min ? min.y : nowY - 5;
            const end = max ? max.y : nowY + 5;
            return { start, end };
        }

        syncSelects() {
            const { start, end } = this.yearRange();
            this.yearOptions = [];
            for (let y = start; y <= end; y++) this.yearOptions.push(y);
            // viewYear still drives which month the calendar renders (so
            // Today/day-clicks work immediately on a never-touched field),
            // but the box itself stays blank until the field actually has a
            // value or the person has explicitly picked a year -- showing
            // "2026" by default read as if that were already chosen.
            this.yearInput.value = this.yearUnset ? '' : this.viewYear;
            this.closeYearList();
            if (this.monthSelect) this.monthSelect.value = this.viewMonth;
        }

        // ============================================
        // YEAR COMBO (type-to-filter instead of a plain <select>)
        // ============================================

        openYearList() {
            this.yearListOpen = true;
            this.yearCombo.classList.add('dp-year-combo-open');
            this.renderYearList(this.yearInput.value.trim());
        }

        closeYearList() {
            this.yearListOpen = false;
            this.yearCombo.classList.remove('dp-year-combo-open');
            this.yearHighlight = -1;
        }

        renderYearList(filterStr) {
            const query = (filterStr || '').replace(/[^0-9]/g, '');
            const matches = query
                ? this.yearOptions.filter(y => String(y).includes(query))
                : this.yearOptions;

            this.yearList.innerHTML = '';
            if (matches.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'dp-year-item dp-year-item-empty';
                empty.textContent = 'No matching year';
                this.yearList.appendChild(empty);
            } else {
                matches.forEach(y => {
                    const item = document.createElement('div');
                    item.className = 'dp-year-item';
                    if (!this.yearUnset && y === this.viewYear) item.classList.add('dp-year-item-selected');
                    item.textContent = y;
                    item.dataset.year = y;
                    item.addEventListener('mousedown', (e) => {
                        // mousedown (not click) so this fires before the
                        // input's own blur handler closes the list.
                        e.preventDefault();
                        this.selectYear(y);
                    });
                    this.yearList.appendChild(item);
                });
            }
            this.yearHighlight = -1;
            if (!this.yearListOpen) this.openYearList();
        }

        selectYear(y) {
            this.viewYear = y;
            this.yearUnset = false;
            this.yearInput.value = y;
            this.closeYearList();
            if (this.mode === 'month') this.renderMonthGrid(); else this.renderDayGrid();
        }

        resolveYearInput() {
            const query = this.yearInput.value.trim().replace(/[^0-9]/g, '');
            const y = parseInt(query, 10);
            if (query && !isNaN(y) && this.yearOptions.includes(y)) {
                this.selectYear(y);
            } else {
                // Invalid/partial/no match left in the box -- revert to
                // whatever year is actually in effect (or back to blank, if
                // none has been chosen yet) instead of leaving unresolved
                // text that looks like a selection.
                this.yearInput.value = this.yearUnset ? '' : this.viewYear;
                this.closeYearList();
            }
        }

        handleYearKeydown(e) {
            const items = Array.from(this.yearList.querySelectorAll('.dp-year-item[data-year]'));
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!items.length) return;
                this.yearHighlight = (this.yearHighlight + 1) % items.length;
                this.highlightYearItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!items.length) return;
                this.yearHighlight = (this.yearHighlight - 1 + items.length) % items.length;
                this.highlightYearItem(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.yearHighlight >= 0 && this.yearHighlight < items.length) {
                    this.selectYear(parseInt(items[this.yearHighlight].dataset.year, 10));
                } else if (items.length === 1) {
                    this.selectYear(parseInt(items[0].dataset.year, 10));
                } else {
                    this.resolveYearInput();
                }
            } else if (e.key === 'Escape') {
                this.yearInput.value = this.yearUnset ? '' : this.viewYear;
                this.closeYearList();
            } else if (e.key === 'Tab') {
                this.resolveYearInput();
            }
        }

        highlightYearItem(items) {
            items.forEach((item, i) => item.classList.toggle('dp-year-item-highlight', i === this.yearHighlight));
            if (this.yearHighlight >= 0) items[this.yearHighlight].scrollIntoView({ block: 'nearest' });
        }

        open() {
            const parsed = parseISO(this._value);
            const today = new Date();
            this.viewYear = parsed ? parsed.y : today.getFullYear();
            this.viewMonth = parsed ? parsed.m : today.getMonth() + 1;
            // The calendar still needs a concrete year/month to render (so
            // Today/day-clicks work right away on a field that's never had
            // a value), but the year box itself should read as untouched --
            // not as if today's year were already the chosen one -- until
            // the field has a real value or the person picks one.
            this.yearUnset = !parsed;
            if (this.mode === 'datetime') {
                this.pickedDate = parsed ? { y: parsed.y, m: parsed.m, d: parsed.d } : null;
                this.initTimeSelects(parsed);
            }
            this.syncSelects();
            if (this.mode === 'month') this.renderMonthGrid(); else this.renderDayGrid();

            installModalStackFix();
            bootstrap.Modal.getOrCreateInstance(this.modalEl).show();
        }

        renderDayGrid() {
            const { min, max } = this.getBounds();
            const y = this.viewYear, m = this.viewMonth;
            // Datetime mode tracks the in-progress pick separately from the
            // confirmed _value, since a day click there only stages the day
            // -- the Set button (below, once a time is also chosen) is what
            // actually confirms.
            const selected = this.mode === 'datetime' ? this.pickedDate : parseISO(this._value);
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
                    if (this.mode === 'datetime') {
                        this.pickedDate = { y: this.viewYear, m: this.viewMonth, d };
                        this.renderDayGrid();
                        this.applyTimeBounds();
                    } else {
                        this.confirm(`${this.viewYear}-${pad(this.viewMonth)}-${pad(d)}`);
                    }
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
        (root || document).querySelectorAll('input[type="date"], input[type="month"], input[type="datetime-local"]').forEach(input => {
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
