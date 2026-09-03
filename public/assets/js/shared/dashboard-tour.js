// ============================================
// DASHBOARD ONBOARDING TOUR (shared engine)
// A Mobile-Legends-style spotlight walkthrough: dims the screen, cuts a
// hole around the current feature, and describes it in a small card with
// Skip / Don't show again / Next controls.
//
// Portal-agnostic -- each dashboard page defines its own steps and (for
// pages whose widgets are injected async via fetch) a "ready" event name
// before including this file:
//
//   <script>
//     window.dashboardTourSteps = [ { target: '...', title: '...', desc: '...' }, ... ];
//     window.dashboardTourReadyEvent = 'fn-head-dashboard-rendered'; // optional
//   </script>
//   <script src="/ShelfSense/public/assets/js/shared/dashboard-tour.js"></script>
//
// If dashboardTourReadyEvent isn't set, the tour starts on DOMContentLoaded
// (for pages whose widget containers are static markup already in the DOM).
// ============================================

(function () {
    var STEPS = window.dashboardTourSteps || [];
    var READY_EVENT = window.dashboardTourReadyEvent || null;

    if (!STEPS.length) return;

    var overlayEl, spotlightEl, cardEl;
    var currentStep = 0;

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
        overlayEl.className = 'dash-tour-overlay';

        spotlightEl = document.createElement('div');
        spotlightEl.className = 'dash-tour-spotlight';
        overlayEl.appendChild(spotlightEl);

        cardEl = document.createElement('div');
        cardEl.className = 'dash-tour-card';
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

    // offsetParent is null both for display:none elements AND for
    // position:fixed ones (e.g. a FAB) -- a bare `!!el.offsetParent` check
    // would wrongly treat a perfectly visible fixed-position target as
    // hidden, so disambiguate with computed style.
    function isVisible(el) {
        if (!el) return false;
        if (el.offsetParent) return true;
        var style = getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') return false;
        return style.position === 'fixed' || style.position === 'sticky';
    }

    function renderStep() {
        var step = STEPS[currentStep];
        var target = document.querySelector(step.target);

        // A hidden target still "exists" for querySelector -- e.g. the
        // Edit Profile pencil, which the collapsed sidebar hides in favor
        // of just the avatar -- but spotlighting it renders a zero-size
        // hole at (0,0), so fall back to an alternate target instead.
        if (target && !isVisible(target) && step.fallbackTarget) {
            target = document.querySelector(step.fallbackTarget);
        }

        // A step's target isn't guaranteed to exist on every portal (e.g.
        // an Edit UI button, a FAB, or a profile link some layouts omit)
        // -- skip it rather than spotlighting nothing.
        if (!isVisible(target)) {
            advance(1);
            return;
        }

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

        var isLast = currentStep === STEPS.length - 1;
        var dotsHtml = STEPS.map(function (_, i) {
            return '<span class="' + (i === currentStep ? 'active' : '') + '"></span>';
        }).join('');

        cardEl.innerHTML =
            '<div class="dash-tour-step-count">Step ' + (currentStep + 1) + ' of ' + STEPS.length + '</div>' +
            '<div class="dash-tour-title">' + step.title + '</div>' +
            '<div class="dash-tour-desc">' + step.desc + '</div>' +
            '<div class="dash-tour-dots">' + dotsHtml + '</div>' +
            (isLast ? '<label class="dash-tour-dont-show"><input type="checkbox" id="dashTourDontShow"> Don’t show this tour again</label>' : '') +
            '<div class="dash-tour-footer">' +
            '<button type="button" class="dash-tour-skip" id="dashTourSkip">Skip tour</button>' +
            '<button type="button" class="dash-tour-next" id="dashTourNext">' + (isLast ? 'Finish' : 'Next') + '</button>' +
            '</div>';

        // Wait a frame so the card has real dimensions before positioning.
        requestAnimationFrame(function () { positionForTarget(target); });

        document.getElementById('dashTourSkip').addEventListener('click', function () { endTour(false); });
        document.getElementById('dashTourNext').addEventListener('click', function () {
            if (isLast) {
                var checkbox = document.getElementById('dashTourDontShow');
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

    function maybeStart() {
        fetchPreference().then(function (enabled) {
            if (enabled) startTour();
        });
    }

    if (READY_EVENT) {
        document.addEventListener(READY_EVENT, maybeStart, { once: true });
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', maybeStart, { once: true });
    } else {
        maybeStart();
    }
})();
