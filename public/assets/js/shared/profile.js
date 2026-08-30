document.addEventListener('DOMContentLoaded', function () {
    const avatarWrap = document.getElementById('profileAvatarWrap');
    const avatarIcon = document.getElementById('profileAvatarIcon');
    const avatarImg = document.getElementById('profileAvatarImg');
    const uploadBtn = document.getElementById('uploadAvatarBtn');
    const removeBtn = document.getElementById('removeAvatarBtn');
    const fileInput = document.getElementById('avatarFileInput');
    const infoBody = document.getElementById('profileInfoBody');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const pendingNotice = document.getElementById('pendingNotice');
    const pendingNoticeImg = document.getElementById('pendingNoticeImg');
    const cancelPendingBtn = document.getElementById('cancelPendingBtn');
    const rejectedNotice = document.getElementById('rejectedNotice');
    const rejectedReasonText = document.getElementById('rejectedReasonText');

    function setAvatar(path) {
        if (path) {
            avatarImg.src = '/ShelfSense/public/' + path + '?t=' + Date.now();
            avatarImg.style.display = 'block';
            avatarIcon.style.display = 'none';
            removeBtn.disabled = false;
        } else {
            avatarImg.style.display = 'none';
            avatarIcon.style.display = 'block';
            removeBtn.disabled = true;
        }
    }

    function setPendingState(status, pendingPath, reason) {
        pendingNotice.style.display = 'none';
        rejectedNotice.style.display = 'none';
        if (status === 'pending' && pendingPath) {
            pendingNoticeImg.src = '/ShelfSense/public/' + pendingPath + '?t=' + Date.now();
            pendingNotice.style.display = 'flex';
        } else if (status === 'rejected') {
            rejectedReasonText.textContent = reason ? ': "' + reason + '"' : '';
            rejectedNotice.style.display = 'flex';
        }
    }

    function loadProfile() {
        fetch('?page=api_get_profile')
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                const p = res.data;
                setAvatar(p.profile_pic);
                setPendingState(p.pending_profile_pic_status, p.pending_profile_pic, p.pending_profile_pic_reason);
                infoBody.innerHTML = `
                    <div class="profile-info-row">
                        <span class="label">Full name</span>
                        <span class="value">${escapeHtml(p.first_name + ' ' + p.last_name)}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="label">Employee #</span>
                        <span class="value">${escapeHtml(p.employee_number)}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="label">Email</span>
                        <span class="value">${escapeHtml(p.email)}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="label">Role</span>
                        <span class="value">${escapeHtml(p.role_label)}</span>
                    </div>
                `;
            });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    uploadBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];
        if (!file) return;

        if (file.size > 3 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'File too large', text: 'Please choose an image under 3MB.' });
            fileInput.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';

        fetch('?page=api_update_avatar', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="bi bi-upload"></i> Upload image';
                fileInput.value = '';
                if (res.success) {
                    setPendingState('pending', res.data.pending_profile_pic, null);
                    Swal.fire({ icon: 'success', title: 'Submitted for approval', text: 'Your new profile picture will show once the owner approves it.', timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Upload failed', text: res.message || 'Please try again.' });
                }
            })
            .catch(() => {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="bi bi-upload"></i> Upload image';
                Swal.fire({ icon: 'error', title: 'Upload failed', text: 'Please try again.' });
            });
    });

    cancelPendingBtn.addEventListener('click', function () {
        cancelPendingBtn.disabled = true;
        fetch('?page=api_cancel_pending_avatar', { method: 'POST' })
            .then(r => r.json())
            .then(res => {
                cancelPendingBtn.disabled = false;
                if (res.success) {
                    setPendingState('none', null, null);
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Please try again.' });
                }
            })
            .catch(() => {
                cancelPendingBtn.disabled = false;
                Swal.fire({ icon: 'error', title: 'Failed', text: 'Please try again.' });
            });
    });

    removeBtn.addEventListener('click', function () {
        Swal.fire({
            title: 'Remove profile picture?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, remove',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch('?page=api_remove_avatar', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        setAvatar(null);
                        Swal.fire({ icon: 'success', title: 'Profile picture removed', timer: 1200, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Please try again.' });
                    }
                });
        });
    });

    changePasswordForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            Swal.fire({ icon: 'warning', title: 'Passwords do not match' });
            return;
        }
        if (newPassword.length < 8) {
            Swal.fire({ icon: 'warning', title: 'New password must be at least 8 characters' });
            return;
        }

        changePasswordBtn.disabled = true;
        changePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';

        fetch('?page=api_change_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            })
        })
            .then(r => r.json())
            .then(res => {
                changePasswordBtn.disabled = false;
                changePasswordBtn.innerHTML = '<i class="bi bi-shield-lock"></i> Update password';
                if (res.success) {
                    changePasswordForm.reset();
                    Swal.fire({ icon: 'success', title: 'Password updated', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Please try again.' });
                }
            })
            .catch(() => {
                changePasswordBtn.disabled = false;
                changePasswordBtn.innerHTML = '<i class="bi bi-shield-lock"></i> Update password';
                Swal.fire({ icon: 'error', title: 'Failed', text: 'Please try again.' });
            });
    });

    loadProfile();
});
