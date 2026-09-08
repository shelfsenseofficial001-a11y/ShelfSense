<!-- Full-page splash shown from first paint until the page (and its initial
     data fetch, for pages that opt in) is actually ready -- masks the
     spinner-then-content flicker on every fresh navigation. Kept inline
     (no external CSS/JS dependency) so it can never itself be delayed. -->
<div id="shelfSplash" aria-hidden="true">
    <div class="shelf-splash-mark">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="7" width="20" height="14" rx="2" stroke="#ff6b35" stroke-width="1.6"/>
            <path d="M2 7L12 2L22 7" stroke="#ff6b35" stroke-width="1.6" stroke-linejoin="round"/>
            <line x1="7" y1="11" x2="17" y2="11" stroke="#ff6b35" stroke-width="1.4" stroke-linecap="round"/>
            <line x1="7" y1="15" x2="14" y2="15" stroke="#ff6b35" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
        <span class="shelf-splash-word">Shelf<span>Sense</span></span>
    </div>
    <div class="shelf-splash-spinner"></div>
</div>
<style>
    #shelfSplash {
        position: fixed;
        inset: 0;
        z-index: 20000;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 18px;
        background: #14100f;
        opacity: 1;
        visibility: visible;
        transition: opacity 0.25s ease;
    }
    #shelfSplash.shelf-splash-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    .shelf-splash-mark {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .shelf-splash-word {
        font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
        font-size: 1.4rem;
        font-weight: 700;
        color: #f5f2ef;
        letter-spacing: -0.01em;
    }
    .shelf-splash-word span {
        color: #ff6b35;
    }
    .shelf-splash-spinner {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 3px solid rgba(255, 107, 53, 0.2);
        border-top-color: #ff6b35;
        animation: shelfSplashSpin 0.7s linear infinite;
    }
    @keyframes shelfSplashSpin {
        to { transform: rotate(360deg); }
    }
</style>
<script>
    (function () {
        var MIN_VISIBLE_MS = 150;
        var MAX_WAIT_MS = 4000;
        var shownAt = Date.now();
        var hidden = false;

        function hideNow() {
            if (hidden) return;
            hidden = true;
            var el = document.getElementById('shelfSplash');
            if (el) el.classList.add('shelf-splash-hidden');
        }

        function hideWhenMinTimeElapsed() {
            var elapsed = Date.now() - shownAt;
            if (elapsed >= MIN_VISIBLE_MS) {
                hideNow();
            } else {
                setTimeout(hideNow, MIN_VISIBLE_MS - elapsed);
            }
        }

        // Pages that fetch their own initial data can delay the hide until
        // that data is actually rendered, by calling ShelfSplash.ready()
        // themselves instead of relying on the window.load fallback below.
        window.ShelfSplash = { ready: hideWhenMinTimeElapsed };

        window.addEventListener('load', hideWhenMinTimeElapsed);
        // Safety net: never let a page that forgets to call ready() (or
        // whose fetch fails) leave the splash stuck on screen forever.
        setTimeout(hideNow, MAX_WAIT_MS);
    })();
</script>
