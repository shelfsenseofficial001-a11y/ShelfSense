// public/assets/js/shared/face-enrollment.js
// Wires the "Attendance Face ID" card on the Profile page to
// face-capture.js + the enroll/status/remove endpoints.

document.addEventListener('DOMContentLoaded', function () {
    const enrollBtn = document.getElementById('faceEnrollBtn');
    const removeBtn = document.getElementById('faceRemoveBtn');
    const statusText = document.getElementById('faceEnrollStatus');
    if (!enrollBtn) return;

    function refreshStatus() {
        fetch('?page=api_face_enrollment_status')
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                if (res.data.enrolled) {
                    statusText.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Enrolled — used for attendance verification at the register.';
                    enrollBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Re-enroll';
                    removeBtn.style.display = 'inline-block';
                } else {
                    statusText.innerHTML = '<i class="bi bi-x-circle"></i> Not enrolled yet. Enroll to skip manual attendance entry at the register.';
                    enrollBtn.innerHTML = '<i class="bi bi-camera"></i> Enroll Face ID';
                    removeBtn.style.display = 'none';
                }
            })
            .catch(() => {});
    }

    enrollBtn.addEventListener('click', function () {
        enrollBtn.disabled = true;
        window.ShelfFaceCapture.run({
            angles: ['Look straight at the camera', 'Slowly turn your head slightly left', 'Slowly turn your head slightly right']
        }).then(result => {
            return fetch('?page=api_enroll_face', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ descriptors: result.descriptors, consent: true })
            }).then(r => r.json());
        }).then(res => {
            enrollBtn.disabled = false;
            if (res && res.success) {
                Swal.fire({ icon: 'success', title: 'Enrolled', text: 'Face ID is ready to use at the register.' });
                refreshStatus();
            } else if (res) {
                Swal.fire({ icon: 'error', title: 'Enrollment failed', text: res.message || 'Please try again.' });
            }
        }).catch(err => {
            enrollBtn.disabled = false;
            if (err && err.message !== 'cancelled') {
                Swal.fire({ icon: 'error', title: 'Enrollment failed', text: err.message || 'Please try again.' });
            }
        });
    });

    removeBtn.addEventListener('click', function () {
        Swal.fire({
            icon: 'warning',
            title: 'Remove Face ID?',
            text: 'You will need to enroll again to use face verification at the register.',
            showCancelButton: true,
            confirmButtonText: 'Remove'
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch('?page=api_remove_face_enrollment', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) refreshStatus();
                });
        });
    });

    refreshStatus();
});
