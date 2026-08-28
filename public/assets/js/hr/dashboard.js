// ============================================
// HR DASHBOARD - AJAX
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadDashboardApplicants();
    loadDashboardTrainees();
    loadDashboardInterviews();
});

function loadDashboardData() {
    fetch('?page=api_get_dashboard_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.data.stats);
                renderMonthlyChart(data.data.monthly_applications);
                renderPipelineChart(data.data.pipeline);
                updatePendingBadge(data.data.stats.pending);
            } else {
                console.error('Failed to load dashboard data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading dashboard:', error);
        });
}

function loadDashboardInterviews() {
    fetch('?page=api_get_interviews&p=1&limit=5&type=all&status=all&search=')
        .then(response => response.json())
        .then(data => {
            const interviews = (data.success && data.data.interviews) ? data.data.interviews : [];
            updateUpcomingInterviews(interviews);
            updateInterviewsDueBadge(interviews);
        })
        .catch(error => {
            console.error('Error loading dashboard interviews:', error);
            const tbody = document.getElementById('dashInterviewsBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Failed to load</td></tr>';
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

const applicantStatusColors = {
    'pending': 'warning',
    'initial_scheduled': 'info',
    'initial_passed': 'primary',
    'initial_failed': 'danger',
    'final_scheduled': 'info',
    'final_passed': 'primary',
    'final_failed': 'danger',
    'screening': 'warning',
    'screening_success': 'success',
    'screening_failed': 'danger',
    'contract_offered': 'primary',
    'contract_declined': 'secondary',
    'hired': 'success'
};

function loadDashboardApplicants() {
    fetch('?page=api_get_applicants&p=1&limit=5&status=all&role=all&search=')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('dashApplicantsBody');
            if (!tbody) return;
            const applicants = (data.success && data.data.applicants) ? data.data.applicants : [];
            const pendingCount = (data.success && data.data.stats) ? (data.data.stats.pending || 0) : 0;
            const countBadge = document.getElementById('dashApplicantsCountBadge');
            if (countBadge) {
                if (pendingCount > 0) {
                    countBadge.textContent = pendingCount;
                    countBadge.style.display = 'inline-flex';
                } else {
                    countBadge.style.display = 'none';
                }
            }
            if (applicants.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No applicants yet</td></tr>';
                return;
            }
            tbody.innerHTML = applicants.map(a => `
                <tr>
                    <td>${escapeHtml(a.first_name)} ${escapeHtml(a.last_name)}</td>
                    <td class="text-muted small">${escapeHtml(a.target_role)}</td>
                    <td><span class="badge bg-${applicantStatusColors[a.status] || 'secondary'}">${escapeHtml(a.status_label || a.status)}</span></td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading dashboard applicants:', error);
            const tbody = document.getElementById('dashApplicantsBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Failed to load</td></tr>';
        });
}

function loadDashboardTrainees() {
    fetch('?page=api_get_trainees&p=1&limit=5&status=all&role=all&search=')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('dashTraineesBody');
            if (!tbody) return;
            const trainees = (data.success && data.data.trainees) ? data.data.trainees : [];
            if (trainees.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No trainees yet</td></tr>';
                return;
            }
            tbody.innerHTML = trainees.map(t => `
                <tr>
                    <td>${escapeHtml(t.trainee_name)}</td>
                    <td class="text-muted small">${t.trainer_name ? escapeHtml(t.trainer_name) : '—'}</td>
                    <td><span class="badge bg-${t.status_color || 'secondary'}">${escapeHtml(t.status_label || t.status)}</span></td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading dashboard trainees:', error);
            const tbody = document.getElementById('dashTraineesBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Failed to load</td></tr>';
        });
}

function updateStats(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statPending').textContent = stats.pending || 0;
    document.getElementById('statScheduled').textContent = stats.scheduled || 0;
    document.getElementById('statHired').textContent = stats.hired || 0;
    // Badge is now updated globally via app.js
}

function updatePendingBadge(pending) {
    const badge = document.getElementById('pendingBadge');
    if (badge) {
        if (pending > 0) {
            badge.textContent = pending;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

function updateInterviewsDueBadge(interviews) {
    const badge = document.getElementById('dashInterviewsDueBadge');
    if (!badge) return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let soonest = null;
    (interviews || []).forEach(iv => {
        if (iv.status !== 'scheduled' || !iv.scheduled_date) return;
        const d = new Date(iv.scheduled_date.replace(' ', 'T'));
        d.setHours(0, 0, 0, 0);
        if (d >= today && (!soonest || d < soonest)) soonest = d;
    });

    if (!soonest) {
        badge.style.display = 'none';
        return;
    }

    const diffDays = Math.round((soonest - today) / 86400000);
    badge.classList.remove('due-today', 'due-soon');
    if (diffDays === 0) {
        badge.textContent = 'Interview Due Today';
        badge.classList.add('due-today');
    } else {
        badge.textContent = `Interview in ${diffDays} Day${diffDays === 1 ? '' : 's'}`;
        badge.classList.add('due-soon');
    }
    badge.style.display = 'inline-flex';
}

function updateUpcomingInterviews(interviews) {
    const tbody = document.getElementById('dashInterviewsBody');
    if (!tbody) return;

    if (!interviews || interviews.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No interviews yet</td></tr>';
        return;
    }

    tbody.innerHTML = interviews.map(interview => {
        const statusLabel = interview.status ? interview.status.charAt(0).toUpperCase() + interview.status.slice(1) : 'Unknown';
        return `
        <tr>
            <td>${escapeHtml(interview.applicant_name)}</td>
            <td class="text-muted small">${escapeHtml(interview.type_label)}</td>
            <td><span class="badge bg-${interview.status_color || 'secondary'}">${escapeHtml(statusLabel)}</span></td>
            <td class="small">${escapeHtml(interview.formatted_date)}<br><span class="text-muted">${escapeHtml(interview.formatted_time)}</span></td>
        </tr>
    `;
    }).join('');
}

let monthlyChartInstance = null;
let pipelineChartInstance = null;

function renderMonthlyChart(data) {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    
    if (monthlyChartInstance) {
        monthlyChartInstance.destroy();
    }
    
    const labels = data.map(item => {
        const [year, month] = item.month.split('-');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return monthNames[parseInt(month) - 1] + ' ' + year;
    });
    
    const values = data.map(item => item.count);
    
    monthlyChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Applications',
                data: values,
                backgroundColor: 'rgba(242, 99, 43, 0.7)',
                borderColor: '#f2632b',
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

function renderPipelineChart(data) {
    const ctx = document.getElementById('pipelineChart').getContext('2d');
    
    if (pipelineChartInstance) {
        pipelineChartInstance.destroy();
    }
    
    const labels = data.map(item => item.label);
    const values = data.map(item => item.count);
    
    const colors = [
        '#1a1a1a', '#f2632b', '#c2c2c5', '#dc5220',
        '#71717a', '#f7b98c', '#3f3f46', '#e8935f'
    ];
    
    pipelineChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, values.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 6,
                        font: { size: 10 }
                    }
                }
            },
            cutout: '60%'
        }
    });
}