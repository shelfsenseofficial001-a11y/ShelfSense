// ============================================
// PROFILE PAGE - DASHBOARD TOUR TOGGLE (shared across every portal)
// Loads/saves the same preference the onboarding tour itself reads via
// api_get_tour_preference / api_save_tour_preference.
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('dashboardTourToggle');
    if (!toggle) return;

    fetch('?page=api_get_tour_preference')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            toggle.checked = data.success ? !!data.data.show_dashboard_tour : true;
        })
        .catch(function () {
            toggle.checked = true;
        });

    toggle.addEventListener('change', function () {
        fetch('?page=api_save_tour_preference', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: toggle.checked }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Failed to save');
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: toggle.checked ? 'Tour enabled' : 'Tour disabled',
                        text: toggle.checked
                            ? 'You\'ll see the walkthrough next time you open the Dashboard.'
                            : 'You won\'t see the walkthrough again unless you turn this back on.',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                }
            })
            .catch(function (err) {
                console.error('Failed to save tour preference:', err);
                toggle.checked = !toggle.checked; // revert on failure
            });
    });
});
