// ============================================
// STORE MANAGER DASHBOARD - ONBOARDING TOUR (experimental)
// A Mobile-Legends-style spotlight walkthrough: dims the screen, cuts a
// hole around the current feature, and describes it in a small card with
// Skip / Don't show again / Next controls. Runs only on the Store Manager
// Dashboard page, and only for users who haven't turned it off.
// ============================================

(function () {
    var STEPS = [
        {
            target: '.sidebar-nav',
            title: 'Your navigation',
            desc: 'Everything you need lives here -- Dashboard, Requisitions, Inventory, Budget, plus your personal Leaves and Payslip pages.'
        },
        {
            target: '#smDashCanvasStats',
            title: 'Quick stats',
            desc: 'A snapshot of your store: total requisitions, what’s pending with the supplier, what’s awaiting finance, and any low-stock items.'
        },
        {
            target: '#smDashCanvasContent',
            title: 'Requisitions & charts',
            desc: 'Live tables and charts covering your requisitions, low stock, and trends over time -- all in one place, no need to click into another page.'
        },
        {
            target: '#dashEditModeBtn',
            title: 'Make it yours',
            desc: 'Click "Edit UI" to drag and reorder every card above into whatever layout works best for you. Your arrangement is saved automatically.'
        },
        {
            target: '.sm-fab',
            title: 'New Requisition',
            desc: 'This button is always here in the corner -- click it anytime to jump straight into creating a new requisition.'
        },
        {
            target: '.user-edit-btn',
            title: 'You’re all set!',
            desc: 'You can turn this tour back on or off anytime -- click the pencil icon highlighted here to open your Profile, then look for "Preferences". Enjoy exploring the dashboard!'
        }
    ];

    var overlayEl, spotlightEl, cardEl;
    var currentStep = 0;

    function isOnDashboard() {
        // This script is only ever included on the dashboard page itself
        // (see views/pages/store_manager/dashboard.php), so the presence
        // of the stats canvas is enough confirmation it actually rendered.
        return !!document.getElementById('smDashCanvasStats');
    }

    function fetchPreference() {
        return fetch('?page=api_get_tour_preference')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                return data.success ? !!data.data.show_dashboard_tour : false;
            })
            .catch(function () { return false; });
    }

    function savePreference(enabled) {
        return fetch('?page=api_save_tour_preference', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: enabled }),
        }).catch(function (err) {
            console.error('Failed to save tour preference:', err);
        });
    }

    function buildOverlay() {
        overlayEl = document.createElement('div');
        overlayEl.className = 'sm-tour-overlay';

        spotlightEl = document.createElement('div');
        spotlightEl.className = 'sm-tour-spotlight';
        overlayEl.appendChild(spotlightEl);

        cardEl = document.createElement('div');
        cardEl.className = 'sm-tour-card';
        overlayEl.appendChild(cardEl);

        document.body.appendChild(overlayEl);
    }

    function teardownOverlay() {
        if (overlayEl && overlayEl.parentNode) overlayEl.parentNode.removeChild(overlayEl);
        overlayEl = spotlightEl = cardEl = null;
    }

    function positionForTarget(target) {
        var rect = target.getBoundingClientRect();
        var pad = 8;

        spotlightEl.style.top = (rect.top - pad) + 'px';
        spotlightEl.style.left = (rect.left - pad) + 'px';
        spotlightEl.style.width = (rect.width + pad * 2) + 'px';
        spotlightEl.style.height = (rect.height + pad * 2) + 'px';

        // Prefer the card below the target; flip above if there isn't
        // room, and clamp horizontally so it never runs off-screen.
        var cardWidth = 320;
        var estCardHeight = cardEl.offsetHeight || 200;
        var spaceBelow = window.innerHeight - rect.bottom;
        var top;
        if (spaceBelow > estCardHeight + 24) {
            top = rect.bottom + 16;
        } else if (rect.top > estCardHeight + 24) {
            top = rect.top - estCardHeight - 16;
        } else {
            top = Math.max(16, (window.innerHeight - estCardHeight) / 2);
        }

        var left = rect.left + rect.width / 2 - cardWidth / 2;
        left = Math.max(16, Math.min(left, window.innerWidth - cardWidth - 16));

        cardEl.style.top = top + 'px';
        cardEl.style.left = left + 'px';
    }

    function renderStep() {
        var step = STEPS[currentStep];
        var target = document.querySelector(step.target);

        // A step's target isn't guaranteed to exist (e.g. widgets a user
        // already dragged out, or a slow-loading fetch) -- skip it rather
        // than spotlighting nothing.
        if (!target) {
            advance(1);
            return;
        }

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

        var isLast = currentStep === STEPS.length - 1;
        var dotsHtml = STEPS.map(function (_, i) {
            return '<span class="' + (i === currentStep ? 'active' : '') + '"></span>';
        }).join('');

        cardEl.innerHTML =
            '<div class="sm-tour-step-count">Step ' + (currentStep + 1) + ' of ' + STEPS.length + '</div>' +
            '<div class="sm-tour-title">' + step.title + '</div>' +
            '<div class="sm-tour-desc">' + step.desc + '</div>' +
            '<div class="sm-tour-dots">' + dotsHtml + '</div>' +
            (isLast ? '<label class="sm-tour-dont-show"><input type="checkbox" id="smTourDontShow"> Don’t show this tour again</label>' : '') +
            '<div class="sm-tour-footer">' +
            '<button type="button" class="sm-tour-skip" id="smTourSkip">Skip tour</button>' +
            '<button type="button" class="sm-tour-next" id="smTourNext">' + (isLast ? 'Finish' : 'Next') + '</button>' +
            '</div>';

        // Wait a frame so the card has real dimensions before positioning.
        requestAnimationFrame(function () { positionForTarget(target); });

        document.getElementById('smTourSkip').addEventListener('click', function () { endTour(false); });
        document.getElementById('smTourNext').addEventListener('click', function () {
            if (isLast) {
                var checkbox = document.getElementById('smTourDontShow');
                endTour(!!(checkbox && checkbox.checked));
            } else {
                advance(1);
            }
        });
    }

    function advance(delta) {
        currentStep += delta;
        if (currentStep >= STEPS.length) {
            endTour(false);
            return;
        }
        renderStep();
    }

    function endTour(disableForever) {
        teardownOverlay();
        window.removeEventListener('resize', handleResize);
        if (disableForever) savePreference(false);
    }

    function handleResize() {
        if (!cardEl) return;
        var step = STEPS[currentStep];
        var target = document.querySelector(step.target);
        if (target) positionForTarget(target);
    }

    function startTour() {
        currentStep = 0;
        buildOverlay();
        renderStep();
        window.addEventListener('resize', handleResize);
    }

    document.addEventListener('sm-dashboard-rendered', function () {
        if (!isOnDashboard()) return;
        fetchPreference().then(function (enabled) {
            if (enabled) startTour();
        });
    }, { once: true });
})();
