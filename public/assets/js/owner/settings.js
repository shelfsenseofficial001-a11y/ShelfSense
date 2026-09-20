// ============================================
// OWNER — SYSTEM SETTINGS
// ============================================

console.log('✅ owner/settings.js loaded');

document.addEventListener('DOMContentLoaded', function () {
    if (window.__INITIAL_DATA__) {
        renderTestMode(window.__INITIAL_DATA__.test_mode);
        if (window.ShelfSplash) window.ShelfSplash.ready();
    } else {
        load();
    }

    document.getElementById('testModeToggle').addEventListener('change', handleToggle);
});

function escapeHtmlSettings(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function load() {
    fetch('?page=api_owner_get_settings')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderTestMode(data.data.test_mode);
            }
        });
}

function renderTestMode(isOn) {
    document.getElementById('testModeToggle').checked = !!isOn;
    const status = document.getElementById('testModeStatus');
    status.textContent = isOn ? 'On' : 'Off';
    status.className = 'fw-semibold ' + (isOn ? 'text-danger' : 'text-muted');
}

function handleToggle(e) {
    const isOn = e.target.checked;
    const msgEl = document.getElementById('testModeMessage');
    e.target.disabled = true;

    fetch('?page=api_owner_update_setting', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key: 'test_mode', value: isOn })
    })
        .then(r => r.json())
        .then(data => {
            e.target.disabled = false;
            if (data.success) {
                renderTestMode(data.data.value);
                msgEl.innerHTML = `<div class="text-success small">Saved.</div>`;
                setTimeout(() => { msgEl.innerHTML = ''; }, 2000);
            } else {
                e.target.checked = !isOn;
                msgEl.innerHTML = `<div class="text-danger small">${escapeHtmlSettings(data.message)}</div>`;
            }
        })
        .catch(() => {
            e.target.disabled = false;
            e.target.checked = !isOn;
            msgEl.innerHTML = `<div class="text-danger small">Something went wrong.</div>`;
        });
}
