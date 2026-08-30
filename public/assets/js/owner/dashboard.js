// ============================================
// OWNER DASHBOARD (prototype)
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    loadOwnerOverview();
});

function ownerEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function loadOwnerOverview() {
    fetch('?page=api_owner_get_overview')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;
            const d = data.data;
            document.getElementById('ownerStatApplicants').textContent = d.applicants.total || 0;
            document.getElementById('ownerStatTrainees').textContent = d.trainees.active || 0;
            document.getElementById('ownerStatHired').textContent = d.applicants.hired || 0;
            document.getElementById('ownerStatPendingPostings').textContent = d.job_postings.pending_approval || 0;
            renderFinalInterviews(d.upcoming_final_interviews || []);
        })
        .catch(() => {});
}

function renderFinalInterviews(interviews) {
    const tbody = document.getElementById('ownerFinalInterviewsBody');
    if (!interviews.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No upcoming Final Interviews.</td></tr>`;
        return;
    }
    tbody.innerHTML = interviews.map(i => `
        <tr>
            <td>${ownerEscapeHtml(i.first_name)} ${ownerEscapeHtml(i.last_name)}</td>
            <td>${ownerEscapeHtml(i.target_role || '')}</td>
            <td>${new Date(i.scheduled_date).toLocaleString()}</td>
            <td>${i.gmeet_link ? `<a href="${ownerEscapeHtml(i.gmeet_link)}" target="_blank">Join</a>` : '—'}</td>
        </tr>
    `).join('');
}
