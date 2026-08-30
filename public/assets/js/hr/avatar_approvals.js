document.addEventListener('DOMContentLoaded', function () {
    const listEl = document.getElementById('pendingAvatarsList');

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function avatarUrl(path) {
        return '/ShelfSense/public/' + path + '?t=' + Date.now();
    }

    function renderCurrentAvatar(path) {
        if (path) {
            return `<div class="avatar-approval-current"><img src="${avatarUrl(path)}" alt="Current"></div>`;
        }
        return '<div class="avatar-approval-current"><i class="bi bi-person-fill"></i></div>';
    }

    function loadPending() {
        fetch('?page=api_list_pending_avatars')
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    listEl.innerHTML = `<div class="text-center py-4 text-muted">${escapeHtml(res.message || 'Failed to load')}</div>`;
                    return;
                }
                const items = res.data.pending;
                if (!items.length) {
                    listEl.innerHTML = '<div class="text-center py-4 text-muted"><i class="bi bi-check2-circle fs-3 d-block mb-2"></i>No profile pictures waiting for review.</div>';
                    return;
                }
                listEl.innerHTML = items.map(item => `
                    <div class="avatar-approval-card border-bottom" data-user-id="${item.user_id}">
                        <div class="avatar-approval-thumb">
                            <img src="${avatarUrl(item.pending_profile_pic)}" alt="Pending">
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${escapeHtml(item.name)}</div>
                            <div class="text-muted small">${escapeHtml(item.employee_number)} &middot; ${escapeHtml(item.role_label)}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-muted small mb-1">Current</div>
                            ${renderCurrentAvatar(item.current_profile_pic)}
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success approve-btn" data-user-id="${item.user_id}">
                                <i class="bi bi-check2"></i> Approve
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger reject-btn" data-user-id="${item.user_id}">
                                <i class="bi bi-x-lg"></i> Reject
                            </button>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {
                listEl.innerHTML = '<div class="text-center py-4 text-muted">Failed to load pending uploads.</div>';
            });
    }

    listEl.addEventListener('click', function (e) {
        const approveBtn = e.target.closest('.approve-btn');
        const rejectBtn = e.target.closest('.reject-btn');

        if (approveBtn) {
            const userId = approveBtn.dataset.userId;
            approveBtn.disabled = true;
            const formData = new FormData();
            formData.append('user_id', userId);
            fetch('?page=api_approve_avatar', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Approved', timer: 1200, showConfirmButton: false });
                        loadPending();
                    } else {
                        approveBtn.disabled = false;
                        Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Please try again.' });
                    }
                })
                .catch(() => {
                    approveBtn.disabled = false;
                    Swal.fire({ icon: 'error', title: 'Failed', text: 'Please try again.' });
                });
        }

        if (rejectBtn) {
            const userId = rejectBtn.dataset.userId;
            Swal.fire({
                title: 'Reject this profile picture?',
                input: 'text',
                inputPlaceholder: 'Reason (optional)',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, reject',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (!result.isConfirmed) return;
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('reason', result.value || '');
                fetch('?page=api_reject_avatar', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            Swal.fire({ icon: 'success', title: 'Rejected', timer: 1200, showConfirmButton: false });
                            loadPending();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Failed', text: res.message || 'Please try again.' });
                        }
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', title: 'Failed', text: 'Please try again.' });
                    });
            });
        }
    });

    loadPending();
});
