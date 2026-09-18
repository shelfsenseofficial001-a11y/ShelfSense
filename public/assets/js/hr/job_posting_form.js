// ============================================
// HR - JOB POSTING FORM (standalone create/edit page)
// window.__POSTING__ (set inline by job_posting_form.php) is either the
// full posting row being edited, or null for a brand-new posting.
// ============================================

let jpBusy = false;
let jpAlreadySavedOnLeave = false;

// Mirrors JOB_POSTING_GROUP_POSITIONS in app/helpers/functions.php -- keep
// in sync if a department ever gets more positions.
const JP_GROUP_POSITIONS = {
    'Front Department': ['Cashier'],
    'Human Resources Department': ['HR Staff'],
    'Finance Department': ['Finance Staff'],
};

// Positions where Qualifications stops being a nice-to-have and becomes a
// real requirement before a posting can go out for approval -- kept in
// sync with submit_job_posting.php's server-side copy of this same rule.
const JP_QUALIFICATIONS_REQUIRED_POSITIONS = ['HR Staff', 'Finance Staff', 'Store Manager'];

function jpVal(id) {
    const el = document.getElementById(id);
    return el ? String(el.value ?? '') : '';
}

function jpEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// Qualifications and Key Responsibilities are entered "one per line" (see
// their textarea placeholders) -- render each non-empty line as its own
// bullet instead of just <br>-joining them, so the preview actually looks
// like the list it's meant to be.
function jpLinesToList(text, extraClass) {
    const lines = (text || '').split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length === 0) return '';
    const cls = 'jp-bullet-list' + (extraClass ? ' ' + extraClass : '');
    return `<ul class="${cls}">` + lines.map(l => `<li>${jpEscapeHtml(l)}</li>`).join('') + '</ul>';
}

function isQualificationsRequired() {
    return JP_QUALIFICATIONS_REQUIRED_POSITIONS.includes(jpVal('postingDepartment'));
}

// ============================================
// PUBLISHING CHECKLIST
// Each item disappears from the checklist the moment it's satisfied.
// `type` is 'required' | 'warning' | 'suggestion', or 'dynamic' when it
// depends on another field (see qualifications below).
// ============================================
const JP_CHECKLIST_ITEMS = [
    {
        id: 'title', type: 'required', section: 'Position Details', label: 'Add a job title',
        message: 'A job title is required so applicants know what position they’re applying for.',
        fieldId: 'postingTitle',
        satisfied: () => jpVal('postingTitle').trim() !== '',
    },
    {
        id: 'department_group', type: 'required', section: 'Position Details', label: 'Select a department',
        message: 'Choose which department this position belongs to.',
        fieldId: 'postingDepartmentGroup',
        satisfied: () => jpVal('postingDepartmentGroup').trim() !== '',
    },
    {
        id: 'department', type: 'required', section: 'Position Details', label: 'Select a position',
        message: 'Choose the specific position within the department.',
        fieldId: 'postingDepartment',
        satisfied: () => jpVal('postingDepartment').trim() !== '',
    },
    {
        id: 'location', type: 'required', section: 'Posting Details', label: 'Add a location',
        message: 'Applicants need to know where this position is based.',
        fieldId: 'postingLocation',
        satisfied: () => jpVal('postingLocation').trim() !== '',
    },
    {
        id: 'qualifications', type: 'dynamic', section: 'Posting Details', label: 'Add qualifications',
        dynamicType: () => isQualificationsRequired() ? 'required' : 'suggestion',
        message: () => isQualificationsRequired()
            ? 'This position requires listed qualifications before it can be submitted for approval.'
            : 'Listing qualifications helps applicants self-screen before applying.',
        fieldId: 'postingRequirements',
        satisfied: () => jpVal('postingRequirements').trim() !== '',
    },
    {
        id: 'salary', type: 'suggestion', section: 'Posting Details', label: 'Add a salary range',
        message: 'A visible salary range tends to increase the number of applications.',
        fieldId: 'postingSalaryMin',
        satisfied: () => jpVal('postingSalaryMin').trim() !== '' || jpVal('postingSalaryMax').trim() !== '',
    },
    {
        id: 'responsibilities', type: 'suggestion', section: 'Posting Details', label: 'Add key responsibilities & duties',
        message: 'Outlining daily responsibilities sets clearer expectations for applicants.',
        fieldId: 'postingResponsibilities',
        satisfied: () => jpVal('postingResponsibilities').trim() !== '',
    },
    {
        id: 'description', type: 'required', section: 'Job Details', label: 'Add a description',
        message: 'A description is required so applicants understand the role.',
        fieldId: 'postingDescription',
        satisfied: () => jpVal('postingDescription').trim() !== '',
    },
    {
        id: 'description_brief', type: 'warning', section: 'Job Details', label: 'Expand the description',
        message: 'Your description is too brief. Add a bit more detail about the role’s purpose and expectations.',
        fieldId: 'postingDescription',
        satisfied: () => {
            const text = jpVal('postingDescription').trim();
            if (text === '') return true; // handled by the "required" item above instead
            const plain = text.replace(/[#*_`~-]/g, '').trim();
            return plain.length >= 120;
        },
    },
    {
        id: 'timeline', type: 'required', section: 'Job Details', label: 'Set a closing date',
        message: 'Applicants need to know the application deadline.',
        fieldId: 'postingOpenUntil',
        satisfied: () => jpVal('postingOpenUntil').trim() !== '',
    },
];

function jpEffectiveType(item) {
    return item.type === 'dynamic' ? item.dynamicType() : item.type;
}
function jpMessage(item) {
    return typeof item.message === 'function' ? item.message() : item.message;
}
function jpHasUnfinishedRequired() {
    return JP_CHECKLIST_ITEMS.some(item => jpEffectiveType(item) === 'required' && !item.satisfied());
}
function jpHasAnyContent() {
    return ['postingTitle', 'postingDepartmentGroup', 'postingDepartment', 'postingLocation', 'postingRequirements',
        'postingSalaryMin', 'postingSalaryMax', 'postingResponsibilities', 'postingDescription', 'postingOpenUntil']
        .some(id => jpVal(id).trim() !== '');
}

const JP_TYPE_ICON = {
    required: '<i class="bi bi-asterisk text-danger"></i>',
    warning: '<i class="bi bi-exclamation-triangle-fill text-warning"></i>',
    suggestion: '<i class="bi bi-lightbulb-fill text-info"></i>',
};

// Tracks the live (non-leaving) card element for each checklist item id
// across renders, so a still-unmet item keeps its existing DOM node
// instead of being torn down and rebuilt on every keystroke -- that's
// what lets a *newly satisfied* item's card animate out on its own
// (fade + collapse) while the others just shift left via normal flex
// reflow, rather than the whole row silently snapping to a new state.
const jpChecklistCardEls = {};

function jpChecklistCardLabel(item) {
    return item.label.replace(/^Add |^Select |^Set /, '');
}

function jpChecklistBuildCard(item, type) {
    const el = document.createElement('div');
    el.className = `jp-checklist-card jp-checklist-card-${type}`;
    el.innerHTML = `
        <div class="jp-checklist-card-icon">${JP_TYPE_ICON[type]} <span>${item.label}</span></div>
        <p class="jp-checklist-card-msg">${jpEscapeHtml(jpMessage(item))}</p>
        <button type="button" class="jp-checklist-card-link" data-field="${item.fieldId}">Edit ${jpEscapeHtml(jpChecklistCardLabel(item))} &rsaquo;</button>
    `;
    el.querySelector('.jp-checklist-card-link').addEventListener('click', () => {
        const field = document.getElementById(item.fieldId);
        if (!field) return;
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        field.focus({ preventScroll: true });
        jpFlashField(field);
    });
    return el;
}

// Flashes an outline glow around the field a checklist card's "Edit X"
// link jumps to, so it's obvious which one just got focused/scrolled to
// even before the user looks for the blinking caret. Searchable-select
// fields hide the real <select> and show a wrapper div in its place (see
// components/searchable-select.js), so the flash has to land on THAT,
// not on an invisible element.
function jpFlashField(field) {
    const target = field.closest('.searchable-select-wrapper') || field;
    target.classList.remove('jp-field-flash');
    void target.offsetWidth; // restart the animation even on repeated clicks
    target.classList.add('jp-field-flash');
    target.addEventListener('animationend', () => target.classList.remove('jp-field-flash'), { once: true });
}

function jpChecklistUpdateCard(el, item, type) {
    el.className = `jp-checklist-card jp-checklist-card-${type}`;
    el.querySelector('.jp-checklist-card-icon').innerHTML = `${JP_TYPE_ICON[type]} <span>${item.label}</span>`;
    el.querySelector('.jp-checklist-card-msg').textContent = jpMessage(item);
    el.querySelector('.jp-checklist-card-link').innerHTML = `Edit ${jpEscapeHtml(jpChecklistCardLabel(item))} &rsaquo;`;
}

// Once a leaving card's fade finishes and no cards (leaving or otherwise)
// remain, swap the row over to the "all clear" line.
function jpChecklistMaybeShowClear() {
    const wrapEl = document.getElementById('jpChecklistCardsWrap');
    const cardsEl = document.getElementById('jpChecklistCards');
    const clearEl = document.getElementById('jpChecklistClear');
    if (!wrapEl || !cardsEl || !clearEl || cardsEl.querySelector('.jp-checklist-card:not(.jp-checklist-card-leaving)')) return;
    wrapEl.style.display = 'none';
    clearEl.style.display = 'flex';
}

// A card whose field just got filled in is pulled OUT of the flex flow
// (position:absolute, pinned to its last on-screen spot via inline
// top/left/width/height) and fades out with a plain opacity transition --
// removing it from flow immediately is what lets the still-unmet cards
// settle into their final positions right away, ready for the FLIP slide
// animated on them in renderChecklist() below. Deliberately does NOT try
// to animate the card's own width/flex-basis down to 0 -- that turned out
// to be unreliable (silently not animating at all in some cases); a plain
// opacity transition never has that problem.
function jpChecklistStartLeave(el, cardsEl) {
    if (el.classList.contains('jp-checklist-card-leaving')) return;
    const containerRect = cardsEl.getBoundingClientRect();
    const rect = el.getBoundingClientRect();
    el.style.left = `${rect.left - containerRect.left + cardsEl.scrollLeft}px`;
    el.style.width = `${rect.width}px`;
    el.style.height = `${rect.height}px`;
    el.classList.add('jp-checklist-card-leaving');
    // Forces the browser to compute/commit the box above (now
    // position:absolute, still fully opaque) as a real "before" state
    // this tick -- only then does the opacity change below have
    // something to visibly transition FROM instead of the two changes
    // collapsing into a single style flush with no visible animation.
    void el.offsetWidth;
    el.style.opacity = '0';

    let removed = false;
    function removeLeavingCard() {
        if (removed) return;
        removed = true;
        el.remove();
        jpChecklistMaybeShowClear();
    }
    el.addEventListener('transitionend', function onEnd(e) {
        if (e.propertyName !== 'opacity') return;
        el.removeEventListener('transitionend', onEnd);
        removeLeavingCard();
    });
    // Fallback in case `transitionend` never fires (e.g. the element got
    // hidden/detached mid-flight) -- guarantees it's still cleaned up.
    setTimeout(removeLeavingCard, 600);
}

function renderChecklist() {
    const wrapEl = document.getElementById('jpChecklistCardsWrap');
    const cardsEl = document.getElementById('jpChecklistCards');
    const clearEl = document.getElementById('jpChecklistClear');
    if (!cardsEl) return;

    const unmet = JP_CHECKLIST_ITEMS
        .map(item => ({ item, type: jpEffectiveType(item) }))
        .filter(({ item }) => !item.satisfied());
    // Required first, then warnings, then suggestions -- most urgent first.
    const order = { required: 0, warning: 1, suggestion: 2 };
    unmet.sort((a, b) => order[a.type] - order[b.type]);
    const unmetIds = new Set(unmet.map(({ item }) => item.id));

    // FLIP "First": record every still-unmet card's current on-screen
    // position before anything else changes, so the cards that shift
    // left below can be animated from here rather than just snapping.
    const firstLeft = {};
    Object.keys(jpChecklistCardEls).forEach(id => {
        if (!unmetIds.has(id)) return;
        firstLeft[id] = jpChecklistCardEls[id].getBoundingClientRect().left;
    });

    // Anything that just became satisfied is pulled out of the flex flow
    // and fades in place (see jpChecklistStartLeave) -- taking it out of
    // flow immediately is what lets the still-unmet cards below settle
    // into their real final positions right away, instead of waiting on
    // its own width to visibly animate down to 0 first.
    Object.keys(jpChecklistCardEls).forEach(id => {
        if (unmetIds.has(id)) return;
        const el = jpChecklistCardEls[id];
        delete jpChecklistCardEls[id];
        if (el) jpChecklistStartLeave(el, cardsEl);
    });

    if (unmet.length === 0) {
        clearEl.style.display = 'flex';
        // Keep the row visible (rather than hiding it outright) until any
        // still-leaving card actually finishes fading out.
        wrapEl.style.display = cardsEl.querySelector('.jp-checklist-card') ? 'block' : 'none';
        jpUpdateChecklistFade();
        return;
    }

    clearEl.style.display = 'none';
    wrapEl.style.display = 'block';

    let prevEl = null;
    unmet.forEach(({ item, type }) => {
        let el = jpChecklistCardEls[item.id];
        if (!el) {
            el = jpChecklistBuildCard(item, type);
            jpChecklistCardEls[item.id] = el;
        } else {
            jpChecklistUpdateCard(el, item, type);
        }
        const wantedNext = prevEl ? prevEl.nextSibling : cardsEl.firstChild;
        if (wantedNext !== el) cardsEl.insertBefore(el, wantedNext);
        prevEl = el;
    });

    // FLIP "Last, Invert": snap every card that had a recorded starting
    // position back there via `transform` (transitions off), so it's
    // visually still sitting exactly where it was even though layout has
    // already moved it.
    const toPlay = [];
    Object.keys(firstLeft).forEach(id => {
        const el = jpChecklistCardEls[id];
        if (!el) return;
        const delta = firstLeft[id] - el.getBoundingClientRect().left;
        if (Math.abs(delta) < 1) return;
        el.style.transition = 'none';
        el.style.transform = `translateX(${delta}px)`;
        toPlay.push(el);
    });
    if (toPlay.length) {
        // Force those inverted positions to actually be committed as a
        // real "before" state this tick, THEN switch on the transition
        // and let go of the transform -- same forced-reflow trick as
        // jpChecklistStartLeave above, and for the same reason: setting
        // both in the same pass with no reflow between them can collapse
        // into one style flush with no visible animation.
        void toPlay[0].offsetWidth;
        toPlay.forEach(el => {
            el.style.transition = 'transform 0.3s ease';
            el.style.transform = 'translateX(0)';
            el.addEventListener('transitionend', function onEnd(e) {
                if (e.propertyName !== 'transform') return;
                el.removeEventListener('transitionend', onEnd);
                el.style.transition = '';
                el.style.transform = '';
            });
        });
    }

    jpUpdateChecklistFade();
}

// The right-edge fade is only meaningful (and only shown) while there's
// actually more to scroll to -- hidden outright when everything fits, and
// faded out once scrolled to the end, matching the visible native
// scrollbar this container also gets (see jp-checklist-cards CSS).
function jpUpdateChecklistFade() {
    const cardsEl = document.getElementById('jpChecklistCards');
    const fadeEl = document.getElementById('jpChecklistFade');
    if (!cardsEl || !fadeEl) return;
    const scrollable = cardsEl.scrollWidth > cardsEl.clientWidth + 1;
    const atEnd = cardsEl.scrollLeft + cardsEl.clientWidth >= cardsEl.scrollWidth - 1;
    fadeEl.classList.toggle('jp-checklist-fade-visible', scrollable && !atEnd);
}

function jpRefreshQualificationsTag() {
    const tag = document.getElementById('postingRequirementsTag');
    if (!tag) return;
    const required = isQualificationsRequired();
    tag.textContent = required ? 'Required' : 'Suggested';
    tag.classList.toggle('jp-field-tag-required', required);
}

function jpUpdateSubmitButtonState() {
    const btn = document.getElementById('saveAndSubmitBtn');
    if (!btn) return;
    const unfinished = jpHasUnfinishedRequired();
    // Stays a real (non-`disabled`) button so it can still be clicked to
    // explain why it's dimmed -- a truly `disabled` button swallows clicks
    // instead of firing them.
    btn.classList.toggle('jp-btn-dimmed', unfinished);
}

function jpRecompute() {
    jpRefreshQualificationsTag();
    renderChecklist();
    renderFullPreview();
    jpUpdateSubmitButtonState();
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('postingDepartmentGroup').addEventListener('change', function () {
        populatePositionOptions(this.value, null);
    });

    setupForm();
    setupChecklistCollapse();
    setupDangerZone();
    setupLeaveGuards();
    jpInitIconTooltips(document);

    prefillForm(window.__POSTING__ || null);
});

// Bootstrap tooltip (dark bubble + arrow) for any icon-only control with a
// `title` -- the plain browser tooltip it'd fall back to is slow to appear
// and looks inconsistent with the rest of the UI. Safe to call repeatedly
// on the same root (e.g. after re-rendering dynamic content): skips
// elements that already have one wired up.
function jpInitIconTooltips(root) {
    if (!window.bootstrap || !window.bootstrap.Tooltip) return;
    root.querySelectorAll('[title]').forEach(el => {
        if (el.__jpTooltip || !el.title.trim()) return;
        el.__jpTooltip = new window.bootstrap.Tooltip(el, { trigger: 'hover focus', placement: 'top' });
    });
}

function setupChecklistCollapse() {
    const btn = document.getElementById('jpChecklistCollapseBtn');
    const header = document.querySelector('.jp-checklist-header');
    const checklist = document.getElementById('jpChecklist');

    function toggle() {
        checklist.classList.toggle('jp-checklist-collapsed');
        const collapsed = checklist.classList.contains('jp-checklist-collapsed');
        btn.innerHTML = collapsed ? '<i class="bi bi-chevron-down"></i>' : '<i class="bi bi-chevron-up"></i>';
    }

    // Dropdown-style: the whole header row toggles it, not just the small
    // chevron button -- the button is inside the header, so its clicks
    // already bubble up into this one listener rather than needing their
    // own (attaching a second listener directly on it would double-fire).
    header?.addEventListener('click', toggle);

    document.getElementById('jpChecklistCards')?.addEventListener('scroll', jpUpdateChecklistFade);
    window.addEventListener('resize', jpUpdateChecklistFade);
}

// ============================================
// PREFILL (edit mode) / INITIAL STATE (create mode)
// ============================================
function prefillForm(posting) {
    document.getElementById('jpFormPageTitle').innerHTML = posting
        ? '<i class="bi bi-pencil-square"></i> Edit Job Posting'
        : '<i class="bi bi-megaphone"></i> New Job Posting';

    document.getElementById('postingTitle').value = posting ? posting.title : '';
    document.getElementById('postingDepartmentGroup').value = posting ? (posting.department_group || '') : '';
    window.refreshSearchableSelect && window.refreshSearchableSelect('postingDepartmentGroup');
    populatePositionOptions(posting ? (posting.department_group || '') : '', posting ? posting.department : null);
    document.getElementById('postingLocation').value = posting ? (posting.location || '') : '';
    document.getElementById('postingSlots').value = posting && posting.slots !== null ? posting.slots : '';
    const requirementsTextarea = document.getElementById('postingRequirements');
    requirementsTextarea.value = posting ? (posting.requirements || '') : '';
    requirementsTextarea.style.height = '';
    autosizeTextarea(requirementsTextarea);
    document.getElementById('postingSalaryMin').value = posting && posting.salary_range_min !== null ? posting.salary_range_min : '';
    document.getElementById('postingSalaryMax').value = posting && posting.salary_range_max !== null ? posting.salary_range_max : '';
    const responsibilitiesTextarea = document.getElementById('postingResponsibilities');
    responsibilitiesTextarea.value = posting ? (posting.responsibilities || '') : '';
    responsibilitiesTextarea.style.height = '';
    autosizeTextarea(responsibilitiesTextarea);

    const descriptionTextarea = document.getElementById('postingDescription');
    descriptionTextarea.value = posting ? (posting.description || '') : '';
    descriptionTextarea.style.height = '';
    jpMdResetHistory(descriptionTextarea);
    autosizeTextarea(descriptionTextarea);

    const openUntilInput = document.getElementById('postingOpenUntil');
    openUntilInput.value = posting ? posting.open_until : '';

    jpRecompute();
}

function populatePositionOptions(group, selectedPosition) {
    const posSelect = document.getElementById('postingDepartment');
    const positions = JP_GROUP_POSITIONS[group] || [];
    posSelect.innerHTML = '<option value=""></option>' + positions.map(p => `<option value="${p}">${p}</option>`).join('');
    posSelect.disabled = positions.length === 0;
    posSelect.value = selectedPosition && positions.includes(selectedPosition) ? selectedPosition : '';
    window.refreshSearchableSelect && window.refreshSearchableSelect(posSelect);
    // Assigning .value directly doesn't fire 'change', so the checklist +
    // live preview (which listen on postingDepartment) would miss this.
    jpRecompute();
}

// Recomputed fresh from "now" every call instead of trusting the input's
// own min/max attributes -- Chromium's native date-picker POPUP lets you
// navigate to and click a day far outside min/max (it only flags the
// input :invalid for form submission, it doesn't stop you from picking
// one), and that same interaction was observed re-asserting the clicked
// (out-of-range) value a moment after this handler already snapped it
// back, as if the popup's own internal state overwrote it once more on
// close. Not relying on input.min/max sidesteps any chance those
// attributes are themselves the problem, and the deferred re-check below
// specifically guards against that late overwrite.
function jpDateBounds() {
    const min = new Date();
    min.setHours(0, 0, 0, 0);
    const max = new Date(min);
    max.setMonth(max.getMonth() + 6);
    return { min, max };
}
function jpDateToInputValue(date) {
    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
}

// Snaps an out-of-range closing date to the nearest allowed bound instead of
// silently clearing it, so a typed/invalid date never gets past validation.
function validatePostingDate(input) {
    const value = input.value;
    if (!value) return;
    const selected = new Date(value + 'T00:00:00');
    if (isNaN(selected.getTime())) {
        input.value = '';
        return;
    }
    const { min, max } = jpDateBounds();
    if (selected < min) {
        input.value = jpDateToInputValue(min);
        Swal.fire({ icon: 'warning', title: 'Date Too Early', text: 'Closing date cannot be in the past. Snapped to today.', timer: 2500, timerProgressBar: true });
    } else if (selected > max) {
        input.value = jpDateToInputValue(max);
        Swal.fire({ icon: 'warning', title: 'Date Too Far', text: 'Closing date cannot exceed 6 months out. Snapped to the latest allowed date.', timer: 2500, timerProgressBar: true });
    } else {
        return;
    }
    // Setting .value directly doesn't fire 'input'/'change' on its own, so
    // the checklist/live preview need an explicit nudge to reflect the
    // corrected date rather than looking stale until some other field's
    // event happens to trigger a re-render.
    jpRecompute();
    // The native picker popup (if still open) can re-assert the exact date
    // the user clicked a beat after we've already corrected it -- re-run
    // once more shortly after to catch and re-correct that.
    setTimeout(() => validatePostingDate(input), 50);
}

// Open Slots only ever makes sense as a positive whole number -- blocks
// typing/pasting letters, minus, decimal points, or scientific notation
// ("e") instead of relying on type="number"/min="1" alone, which only
// affects constraint validation (the field still happily accepts "-3" or
// "12e5" as typed text) rather than what can actually be entered. Swaps
// the hint text to explain why for a moment, worded for whichever kind
// of character got blocked.
let jpSlotsHintTimer = null;
function setupSlotsGuard() {
    const input = document.getElementById('postingSlots');
    const hint = document.getElementById('postingSlotsHint');
    if (!input || !hint) return;
    const defaultHint = hint.textContent;
    // Keys allowed through besides digits -- editing/navigation, not characters.
    const ALLOWED_KEYS = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter'];

    function showWarning(message) {
        hint.textContent = message;
        hint.classList.add('text-danger');
        clearTimeout(jpSlotsHintTimer);
        jpSlotsHintTimer = setTimeout(() => {
            hint.textContent = defaultHint;
            hint.classList.remove('text-danger');
        }, 2500);
        // Restart the shake even on back-to-back rejected keystrokes --
        // removing the class then forcing a reflow before re-adding it is
        // what lets the same CSS animation replay from frame 0 each time,
        // instead of a second rapid keystroke landing mid-animation and
        // doing nothing visible.
        input.classList.remove('jp-input-shake');
        void input.offsetWidth;
        input.classList.add('jp-input-shake');
    }

    input.addEventListener('animationend', () => input.classList.remove('jp-input-shake'));
    input.addEventListener('keydown', e => {
        if (e.ctrlKey || e.metaKey || ALLOWED_KEYS.includes(e.key)) return;
        if (/^[0-9]$/.test(e.key)) return;
        e.preventDefault();
        showWarning(e.key === '-' ? "Negative numbers aren't allowed." : 'Only numbers are allowed.');
    });
    input.addEventListener('input', () => {
        const digitsOnly = input.value.replace(/[^0-9]/g, '');
        if (digitsOnly !== input.value) {
            const hadLetter = /[a-zA-Z]/.test(input.value);
            input.value = digitsOnly;
            showWarning(hadLetter ? 'Only numbers are allowed.' : "Negative numbers aren't allowed.");
            return;
        }
        if (digitsOnly !== '' && parseInt(digitsOnly, 10) > 299) {
            input.value = '299';
            showWarning('Cannot exceed 299.');
        }
    });
}

// Closing-date bounds also get a JS-attached 'change' listener (on top of
// the oninput/onblur attributes on the element itself) -- the native date
// picker POPUP's final "commit" on clicking a day doesn't reliably behave
// like a normal blur/input in every engine, so this is a second, more
// robust hook on the one event that's guaranteed to fire once the picker
// actually closes with a value.
function setupDateGuard() {
    const input = document.getElementById('postingOpenUntil');
    input?.addEventListener('change', () => validatePostingDate(input));
}

// ============================================
// FORM WIRING
// ============================================
function setupForm() {
    document.getElementById('postingForm').addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(false);
    });
    document.getElementById('saveAndSubmitBtn').addEventListener('click', function () {
        submitForm(true);
    });
    setupMarkdownEditor();
    setupSlotsGuard();
    setupDateGuard();

    // Qualifications/Key Responsibilities grow with their content just
    // like the Description editor -- one line per item, so a new bullet
    // typed in should never end up scrolled out of view in a boxed-in
    // 3-row textarea.
    ['postingRequirements', 'postingResponsibilities'].forEach(id => {
        const el = document.getElementById(id);
        el?.addEventListener('input', () => autosizeTextarea(el));
    });

    ['postingTitle', 'postingDepartmentGroup', 'postingDepartment', 'postingLocation', 'postingSlots',
        'postingRequirements', 'postingSalaryMin', 'postingSalaryMax', 'postingResponsibilities', 'postingOpenUntil'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', jpRecompute);
        document.getElementById(id)?.addEventListener('change', jpRecompute);
    });
}

// ============================================
// DESCRIPTION MARKDOWN TOOLBAR
// Wraps/inserts markdown syntax around the current selection (or at the
// cursor, with placeholder text, when nothing is selected) -- the same
// interaction every markdown editor toolbar uses. Rendering back to HTML
// is handled by the shared mdToHtml() in assets/js/shared/markdown.js.
// ============================================
const JP_MD_ACTIONS = {
    h1: { type: 'line-prefix', prefix: '# ' },
    h2: { type: 'line-prefix', prefix: '## ' },
    h3: { type: 'line-prefix', prefix: '### ' },
    bold: { type: 'wrap', before: '**', after: '**', placeholder: 'bold text' },
    italic: { type: 'wrap', before: '*', after: '*', placeholder: 'italic text' },
    strike: { type: 'wrap', before: '~~', after: '~~', placeholder: 'strikethrough text' },
    code: { type: 'wrap', before: '`', after: '`', placeholder: 'code' },
    ul: { type: 'line-prefix', prefix: '- ' },
    ol: { type: 'line-prefix', prefix: '1. ' },
};

function applyMarkdownAction(textarea, action) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;
    const selected = value.slice(start, end);

    if (action.type === 'wrap') {
        const text = selected || action.placeholder;
        const newValue = value.slice(0, start) + action.before + text + action.after + value.slice(end);
        textarea.value = newValue;
        const selStart = start + action.before.length;
        textarea.setSelectionRange(selStart, selStart + text.length);
    } else if (action.type === 'line-prefix') {
        let lineStart = value.lastIndexOf('\n', start - 1) + 1;
        let lineEnd = value.indexOf('\n', end);
        if (lineEnd === -1) lineEnd = value.length;
        const block = value.slice(lineStart, lineEnd);
        const prefixed = block.split('\n').map(l => action.prefix + l).join('\n');
        textarea.value = value.slice(0, lineStart) + prefixed + value.slice(lineEnd);
        textarea.setSelectionRange(lineStart, lineStart + prefixed.length);
    }

    textarea.focus();
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    jpMdCommit(textarea);
}

function autosizeTextarea(textarea) {
    const maxHeight = 500;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, maxHeight) + 'px';
    textarea.style.overflowY = textarea.scrollHeight > maxHeight ? 'auto' : 'hidden';
}

// ============================================
// UNDO / REDO (self-contained -- see job_postings.js history for the
// original rationale: direct .value assignment wipes native undo)
// ============================================
const jpMdHistory = { stack: [], index: -1 };
let jpMdCommitTimer = null;
const JP_MD_HISTORY_LIMIT = 100;

function jpMdSnapshotNow(textarea) {
    return { value: textarea.value, start: textarea.selectionStart, end: textarea.selectionEnd };
}
function jpMdCommit(textarea) {
    clearTimeout(jpMdCommitTimer);
    jpMdCommitTimer = null;
    const current = jpMdSnapshotNow(textarea);
    const top = jpMdHistory.stack[jpMdHistory.index];
    if (top && top.value === current.value) return;
    jpMdHistory.stack = jpMdHistory.stack.slice(0, jpMdHistory.index + 1);
    jpMdHistory.stack.push(current);
    if (jpMdHistory.stack.length > JP_MD_HISTORY_LIMIT) jpMdHistory.stack.shift();
    jpMdHistory.index = jpMdHistory.stack.length - 1;
    jpUpdateUndoRedoButtons();
}
function jpMdScheduleCommit(textarea) {
    clearTimeout(jpMdCommitTimer);
    jpMdCommitTimer = setTimeout(() => jpMdCommit(textarea), 500);
}
function jpMdResetHistory(textarea) {
    clearTimeout(jpMdCommitTimer);
    jpMdCommitTimer = null;
    jpMdHistory.stack = [jpMdSnapshotNow(textarea)];
    jpMdHistory.index = 0;
    jpUpdateUndoRedoButtons();
}
function jpMdRestoreSnapshot(textarea, snapshot) {
    textarea.value = snapshot.value;
    textarea.setSelectionRange(snapshot.start, snapshot.end);
    textarea.focus();
    autosizeTextarea(textarea);
    jpRecompute();
    jpUpdateUndoRedoButtons();
}
function jpMdUndo(textarea) {
    jpMdCommit(textarea);
    if (jpMdHistory.index <= 0) return;
    jpMdHistory.index--;
    jpMdRestoreSnapshot(textarea, jpMdHistory.stack[jpMdHistory.index]);
}
function jpMdRedo(textarea) {
    if (jpMdHistory.index >= jpMdHistory.stack.length - 1) return;
    jpMdHistory.index++;
    jpMdRestoreSnapshot(textarea, jpMdHistory.stack[jpMdHistory.index]);
}
function jpUpdateUndoRedoButtons() {
    const undoBtn = document.getElementById('jpMdUndoBtn');
    const redoBtn = document.getElementById('jpMdRedoBtn');
    if (undoBtn) undoBtn.disabled = jpMdHistory.index <= 0;
    if (redoBtn) redoBtn.disabled = jpMdHistory.index >= jpMdHistory.stack.length - 1;
}

// ============================================
// INSERT LINK MODAL
// ============================================
let jpLinkTextarea = null;
let jpLinkSelStart = 0;
let jpLinkSelEnd = 0;

function openLinkModal(textarea) {
    jpLinkTextarea = textarea;
    jpLinkSelStart = textarea.selectionStart;
    jpLinkSelEnd = textarea.selectionEnd;
    const selectedText = textarea.value.slice(jpLinkSelStart, jpLinkSelEnd);

    document.getElementById('jpLinkLabel').value = selectedText;
    document.getElementById('jpLinkUrl').value = '';
    updateLinkPreview();

    const modalEl = document.getElementById('jpLinkModal');
    modalEl.addEventListener('shown.bs.modal', function focusField() {
        modalEl.removeEventListener('shown.bs.modal', focusField);
        document.getElementById(selectedText ? 'jpLinkUrl' : 'jpLinkLabel').focus();
    });
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function isValidLinkUrl(url) {
    return /^https?:\/\/\S+$/.test(url);
}

function updateLinkPreview() {
    const label = document.getElementById('jpLinkLabel').value.trim();
    const url = document.getElementById('jpLinkUrl').value.trim();
    const preview = document.getElementById('jpLinkPreview');
    const insertBtn = document.getElementById('jpLinkInsertBtn');
    const valid = isValidLinkUrl(url);
    insertBtn.disabled = !valid;

    if (!url) {
        preview.innerHTML = '<span class="text-muted small fst-italic">Nothing to preview yet.</span>';
    } else if (!valid) {
        preview.innerHTML = '<span class="text-danger small">URL must start with http:// or https://</span>';
    } else {
        preview.innerHTML = `<a href="${jpEscapeHtml(url)}" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> ${jpEscapeHtml(label || url)}</a>`;
    }
}

function insertLinkFromModal() {
    const label = document.getElementById('jpLinkLabel').value.trim();
    const url = document.getElementById('jpLinkUrl').value.trim();
    if (!isValidLinkUrl(url) || !jpLinkTextarea) return;

    const textarea = jpLinkTextarea;
    const markdown = `[${label || url}](${url})`;
    const value = textarea.value;
    textarea.value = value.slice(0, jpLinkSelStart) + markdown + value.slice(jpLinkSelEnd);
    const caret = jpLinkSelStart + markdown.length;
    textarea.setSelectionRange(caret, caret);
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    jpMdCommit(textarea);

    bootstrap.Modal.getInstance(document.getElementById('jpLinkModal'))?.hide();
}

// ============================================
// KEYBOARD SHORTCUTS
// ============================================
function handleMarkdownShortcut(e, textarea) {
    const mod = e.ctrlKey || e.metaKey;
    if (!mod) return;

    if (e.code === 'KeyZ' && !e.shiftKey) { e.preventDefault(); jpMdUndo(textarea); return; }
    if ((e.code === 'KeyZ' && e.shiftKey) || e.code === 'KeyY') { e.preventDefault(); jpMdRedo(textarea); return; }

    if (e.code === 'KeyB') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.bold); return; }
    if (e.code === 'KeyI') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.italic); return; }
    if (e.code === 'KeyE') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.code); return; }
    if (e.code === 'KeyK') { e.preventDefault(); openLinkModal(textarea); return; }
    if (e.shiftKey && e.code === 'KeyX') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.strike); return; }
    if (e.shiftKey && e.code === 'Digit8') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.ul); return; }
    if (e.shiftKey && e.code === 'Digit7') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.ol); return; }
    if (e.altKey && e.code === 'Digit1') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.h1); return; }
    if (e.altKey && e.code === 'Digit2') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.h2); return; }
    if (e.altKey && e.code === 'Digit3') { e.preventDefault(); applyMarkdownAction(textarea, JP_MD_ACTIONS.h3); return; }
}

function setupMarkdownEditor() {
    const textarea = document.getElementById('postingDescription');
    if (!textarea || textarea.dataset.mdWired) return;
    textarea.dataset.mdWired = '1';

    textarea.addEventListener('input', () => {
        autosizeTextarea(textarea);
        jpRecompute();
        jpMdScheduleCommit(textarea);
    });
    textarea.addEventListener('keydown', e => handleMarkdownShortcut(e, textarea));

    document.querySelectorAll('.jp-md-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (btn.dataset.md === 'link') { openLinkModal(textarea); return; }
            const action = JP_MD_ACTIONS[btn.dataset.md];
            if (action) applyMarkdownAction(textarea, action);
        });
    });
    jpInitIconTooltips(document);

    document.getElementById('jpMdUndoBtn')?.addEventListener('click', () => jpMdUndo(textarea));
    document.getElementById('jpMdRedoBtn')?.addEventListener('click', () => jpMdRedo(textarea));

    document.getElementById('jpLinkLabel')?.addEventListener('input', updateLinkPreview);
    document.getElementById('jpLinkUrl')?.addEventListener('input', updateLinkPreview);
    document.getElementById('jpLinkInsertBtn')?.addEventListener('click', insertLinkFromModal);
}

function renderFullPreview() {
    const container = document.getElementById('postingFullPreview');
    if (!container) return;

    const title = jpVal('postingTitle').trim();
    const department = jpVal('postingDepartment').trim();
    const location = jpVal('postingLocation').trim();
    const slots = jpVal('postingSlots').trim();
    const description = jpVal('postingDescription').trim();
    const openUntil = jpVal('postingOpenUntil');
    const salaryMin = jpVal('postingSalaryMin').trim();
    const salaryMax = jpVal('postingSalaryMax').trim();

    const badges = [];
    if (department) badges.push(`<span class="jp-preview-badge"><i class="bi bi-briefcase"></i> ${jpEscapeHtml(department)}</span>`);
    if (location) badges.push(`<span class="jp-preview-badge"><i class="bi bi-geo-alt"></i> ${jpEscapeHtml(location)}</span>`);
    if (slots) badges.push(`<span class="jp-preview-badge"><i class="bi bi-people"></i> ${jpEscapeHtml(slots)} slot${slots == 1 ? '' : 's'}</span>`);
    if (salaryMin || salaryMax) {
        const fmt = n => n ? '₱' + parseFloat(n).toLocaleString('en-PH') : '?';
        badges.push(`<span class="jp-preview-badge"><i class="bi bi-cash-coin"></i> ${fmt(salaryMin)} - ${fmt(salaryMax)}</span>`);
    }

    const descriptionHtml = description
        ? window.mdToHtml(description)
        : '<p class="text-muted fst-italic mb-0">No description written yet.</p>';

    const qualifications = jpVal('postingRequirements').trim();
    const responsibilities = jpVal('postingResponsibilities').trim();

    const closingHtml = openUntil
        ? `<p class="mb-0"><i class="bi bi-calendar-event"></i> Applications close <strong>${jpEscapeHtml(new Date(openUntil + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }))}</strong></p>`
        : '<p class="text-muted fst-italic mb-0">No closing date set yet.</p>';

    container.innerHTML = `
        <h4 class="jp-preview-title">${title ? jpEscapeHtml(title) : '<span class="text-muted fst-italic">Untitled position</span>'}</h4>
        <div class="jp-preview-badges">${badges.join('') || '<span class="text-muted small fst-italic">No department/location/slots set yet.</span>'}</div>
        <div class="jp-preview-section">
            <h6><i class="bi bi-file-text"></i> Job Description</h6>
            <div class="jp-preview-description">${descriptionHtml}</div>
        </div>
        ${qualifications ? `<div class="jp-preview-section"><h6><i class="bi bi-check2-square"></i> Qualifications</h6><div class="jp-preview-description">${jpLinesToList(qualifications, 'jp-check-list')}</div></div>` : ''}
        ${responsibilities ? `<div class="jp-preview-section"><h6><i class="bi bi-list-check"></i> Key Responsibilities</h6><div class="jp-preview-description">${jpLinesToList(responsibilities, 'jp-check-list')}</div></div>` : ''}
        <div class="jp-preview-section jp-preview-section-last">
            ${closingHtml}
        </div>
    `;
}

// ============================================
// SAVE / SUBMIT
// ============================================

// job_postings.role has no user-facing meaning anymore -- it only still
// exists in the DB as a NOT NULL, unique-among-active-postings internal
// key. Slugify the title for new postings so it still gets a sane,
// differentiated value; the id suffix keeps two postings with the same
// title from colliding on the uniqueness check.
function slugifyForRoleKey(title, id) {
    const slug = (title || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    return (slug || 'posting') + '-' + id;
}

function collectFormPayload() {
    // The hidden #postingId field renders as value="0" for a brand-new
    // posting (job_posting_form.php echoes (int)$_GET['id'], which is 0
    // when absent) -- "0" is a non-empty string, so a plain `|| undefined`
    // check never catches it, silently routing every "Save as Draft" on a
    // new posting to the update endpoint with id=0 instead of create.
    const idRaw = document.getElementById('postingId').value;
    const id = (idRaw && idRaw !== '0') ? idRaw : undefined;
    const title = document.getElementById('postingTitle').value.trim();
    const payload = {
        id,
        title,
        department_group: document.getElementById('postingDepartmentGroup').value.trim(),
        department: document.getElementById('postingDepartment').value.trim(),
        location: document.getElementById('postingLocation').value.trim(),
        slots: document.getElementById('postingSlots').value,
        requirements: document.getElementById('postingRequirements').value.trim(),
        salary_range_min: document.getElementById('postingSalaryMin').value,
        salary_range_max: document.getElementById('postingSalaryMax').value,
        responsibilities: document.getElementById('postingResponsibilities').value.trim(),
        description: document.getElementById('postingDescription').value.trim(),
        open_until: document.getElementById('postingOpenUntil').value
    };
    if (!id) {
        payload.role = slugifyForRoleKey(title, Date.now());
    }
    return payload;
}

function saveDraft(payload) {
    const isEdit = !!payload.id;
    const url = isEdit ? '?page=api_hr_update_job_posting' : '?page=api_hr_create_job_posting';
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(r => r.json());
}

// Bottom-right popup toast (5s, with a manual close button) for save/submit
// errors -- replaces the old inline alert banner at the top of the form,
// which was easy to miss since the checklist/preview push it out of view.
function jpShowErrorToast(message) {
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'error',
        title: message,
        showConfirmButton: false,
        showCloseButton: true,
        timer: 5000,
        timerProgressBar: true,
    });
}

function submitForm(alsoSubmit) {
    if (jpBusy) return;

    if (alsoSubmit && jpHasUnfinishedRequired()) {
        Swal.fire({ icon: 'warning', title: 'Fill out the required fields first!' });
        return;
    }

    jpBusy = true;
    const payload = collectFormPayload();
    const isEdit = !!payload.id;

    saveDraft(payload)
        .then(data => {
            jpBusy = false;
            if (!data.success) {
                const errs = data.errors ? Object.values(data.errors).join(' ') : '';
                jpShowErrorToast(`${data.message} ${errs}`.trim());
                return;
            }
            const id = isEdit ? payload.id : data.data.id;
            document.getElementById('postingId').value = id;
            jpAlreadySavedOnLeave = true;
            if (alsoSubmit) {
                submitForApproval(id);
            } else {
                Swal.fire({ icon: 'success', title: 'Saved', text: data.message, timer: 1800, showConfirmButton: false })
                    .then(() => { window.location.href = '?page=hr_job_postings'; });
            }
        })
        .catch(() => { jpBusy = false; jpShowErrorToast('Something went wrong.'); });
}

function submitForApproval(id) {
    fetch('?page=api_hr_submit_job_posting', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Submitted', text: data.message, timer: 1800, showConfirmButton: false })
                    .then(() => { window.location.href = '?page=hr_job_postings'; });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        });
}

// ============================================
// DANGER ZONE
// ============================================
function setupDangerZone() {
    document.getElementById('deletePostingBtn')?.addEventListener('click', function () {
        const id = document.getElementById('postingId').value;
        if (!id) return;
        Swal.fire({
            icon: 'warning', title: 'Delete this job posting?',
            text: 'This permanently deletes the posting. This cannot be undone.',
            showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc3545'
        }).then(result => {
            if (!result.isConfirmed) return;
            jpAlreadySavedOnLeave = true; // nothing left worth autosaving
            fetch('?page=api_hr_delete_job_posting', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Deleted', text: data.message, timer: 1800, showConfirmButton: false })
                            .then(() => { window.location.href = '?page=hr_job_postings'; });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                });
        });
    });
}

// ============================================
// LEAVE GUARDS -- ask the user whether to save an unfinished posting as a
// draft or discard it, instead of silently doing either, when they leave
// before it's ready to submit.
// ============================================
let jpLeaveTargetHref = '?page=hr_job_postings';

function setupLeaveGuards() {
    function handleLeave(e) {
        const href = e.currentTarget.getAttribute('href') || '?page=hr_job_postings';
        if (jpAlreadySavedOnLeave || !jpHasAnyContent() || !jpHasUnfinishedRequired()) return; // nothing worth asking about
        e.preventDefault();
        jpLeaveTargetHref = href;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('jpLeaveConfirmModal')).show();
    }
    document.querySelectorAll('.jp-back-link, #cancelFormBtn').forEach(el => el.addEventListener('click', handleLeave));

    document.getElementById('jpLeaveDiscardBtn')?.addEventListener('click', function () {
        jpAlreadySavedOnLeave = true;
        window.location.href = jpLeaveTargetHref;
    });
    document.getElementById('jpLeaveSaveDraftBtn')?.addEventListener('click', function (e) {
        const btn = e.currentTarget;
        btn.disabled = true;
        jpAlreadySavedOnLeave = true;
        saveDraft(collectFormPayload()).finally(() => { window.location.href = jpLeaveTargetHref; });
    });

    // Best-effort coverage for closing the tab / typing a new URL / back
    // button -- a confirmation modal can't be shown during unload, so this
    // still auto-saves silently as a fallback in those cases only.
    window.addEventListener('beforeunload', function () {
        if (jpAlreadySavedOnLeave || !jpHasAnyContent() || !jpHasUnfinishedRequired()) return;
        const payload = collectFormPayload();
        const url = payload.id ? '?page=api_hr_update_job_posting' : '?page=api_hr_create_job_posting';
        navigator.sendBeacon(url, new Blob([JSON.stringify(payload)], { type: 'application/json' }));
    });
}
