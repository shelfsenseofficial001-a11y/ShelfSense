// ============================================
// HR DASHBOARD - AJAX
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

function loadDashboardData() {
    fetch('?page=api_get_dashboard_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.data.stats);
                updateUpcomingInterviews(data.data.upcoming_interviews);
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
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }
}

function updateUpcomingInterviews(interviews) {
    const container = document.getElementById('upcomingInterviews');
    
    if (!interviews || interviews.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                No upcoming interviews scheduled
            </div>
        `;
        return;
    }
    
    let html = '<div class="list-group">';
    interviews.forEach(interview => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>${interview.applicant_name}</strong>
                    <br>
                    <small class="text-muted">${interview.type_label}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-warning">${interview.formatted_date}</span>
                    <br>
                    <small class="text-muted">${interview.formatted_time}</small>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
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
                backgroundColor: 'rgba(255, 196, 20, 0.6)',
                borderColor: '#ffc414',
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
        '#ffc107', '#17a2b8', '#28a745', '#007bff', 
        '#fd7e14', '#20c997', '#6f42c1', '#dc3545'
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