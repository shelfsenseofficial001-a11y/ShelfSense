// ============================================
// TRAINEE DASHBOARD
// ============================================

console.log('✅ trainee/dashboard.js loaded');

document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
});

function loadDashboard() {
    const container = document.getElementById('dashboardContent');
    
    fetch('?page=api_trainee_dashboard')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderDashboard(data.data);
            } else {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${data.message || 'Failed to load dashboard data'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading dashboard:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    An error occurred. Please refresh the page.
                </div>
            `;
        });
}

function renderDashboard(data) {
    const container = document.getElementById('dashboardContent');
    const t = data.trainee;
    const module = data.module;
    const leave = data.leave_balances;
    
    // Determine status color
    let statusColor = 'warning';
    if (t.status === 'completed') statusColor = 'success';
    else if (t.status === 'terminated') statusColor = 'danger';
    
    // Report status
    const reportStatusText = t.all_reports_submitted ? 'All Reports Submitted ✅' : `${t.reports_submitted}/${t.reports_total} Submitted`;
    
    // Trainer status
    const trainerStatus = t.trainer && t.trainer.first_name 
        ? `${t.trainer.first_name} ${t.trainer.last_name}${t.trainer.can_train ? ' 🟢 Available' : ' 🔒 Training'}` 
        : 'Not assigned yet';
    
    // Days remaining display
    let daysDisplay = t.is_completed ? '✅ Training Completed' : `${t.days_remaining} days remaining`;
    let daysColor = t.is_completed ? 'success' : (t.days_remaining < 7 ? 'danger' : 'warning');
    
    container.innerHTML = `
        <!-- Welcome Banner -->
        <div class="modern-card p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="fw-bold">Welcome back, <span class="text-yellow">${t.first_name} ${t.last_name}</span> 👋</h4>
                    <p class="text-muted mb-0">Employee #${t.employee_number} · ${t.target_role} · Status: <span class="badge bg-${statusColor}">${t.status_label}</span></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-${t.eligible_for_contract ? 'success' : 'secondary'} fs-6 px-3 py-2">
                        ${t.eligible_for_contract ? '✅ Eligible for Contract' : '📝 In Training'}
                    </span>
                </div>
            </div>
        </div>

        ${data.pending_contract && data.pending_contract.status === 'pending' ? renderContractCard(data.pending_contract) : ''}

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="trainee-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Days Remaining</div>
                            <div class="stat-number ${daysColor}">${t.is_completed ? '✅' : t.days_remaining}</div>
                        </div>
                        <div class="stat-icon">📅</div>
                    </div>
                    <small class="text-muted">${daysDisplay}</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="trainee-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Training Progress</div>
                            <div class="stat-number primary">${t.reports_submitted}/3</div>
                        </div>
                        <div class="stat-icon">📊</div>
                    </div>
                    <small class="text-muted">${reportStatusText}</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="trainee-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">My Trainer</div>
                            <div class="stat-number" style="font-size:1.1rem;">${t.trainer.first_name || '—'}</div>
                        </div>
                        <div class="stat-icon">👨‍🏫</div>
                    </div>
                    <small class="text-muted">${trainerStatus}</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="trainee-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Schedule</div>
                            <div class="stat-number" style="font-size:1.1rem;">${t.schedule_start}</div>
                        </div>
                        <div class="stat-icon">🕐</div>
                    </div>
                    <small class="text-muted">to ${t.schedule_end}</small>
                </div>
            </div>
        </div>
        
        <!-- Training Module Section -->
        <div class="modern-card p-3 mb-4 module-card">
            <h6 class="fw-bold mb-3"><i class="bi ${module.icon} text-yellow me-2"></i>Your Training Module</h6>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5>${t.target_role}</h5>
                    <p class="text-muted">${module.description}</p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-info">${module.name}</span>
                        <span class="badge bg-warning text-dark">${t.status_label}</span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="${module.url}" class="btn btn-yellow-primary">
                        <i class="bi ${module.icon} me-2"></i> Go to Module
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="modern-card p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold">Training Progress</span>
                <span class="text-muted small">${Math.round((t.reports_submitted / t.reports_total) * 100)}% Complete</span>
            </div>
            <div class="trainee-progress">
                <div class="progress-bar" style="width: ${(t.reports_submitted / t.reports_total) * 100}%;"></div>
            </div>
            <div class="row g-2 mt-3">
                <div class="col-md-4">
                    <div class="report-item ${t.reports[1] ? 'completed' : 'pending'}">
                        <span>📋 Month 1 Report</span>
                        <span class="badge ${t.reports[1] ? 'bg-success' : 'bg-warning text-dark'}">${t.reports[1] ? '✅' : '⏳'}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="report-item ${t.reports[2] ? 'completed' : 'pending'}">
                        <span>📋 Month 2 Report</span>
                        <span class="badge ${t.reports[2] ? 'bg-success' : 'bg-warning text-dark'}">${t.reports[2] ? '✅' : '⏳'}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="report-item ${t.reports[3] ? 'completed' : 'pending'}">
                        <span>📋 Month 3 Report</span>
                        <span class="badge ${t.reports[3] ? 'bg-success' : 'bg-warning text-dark'}">${t.reports[3] ? '✅' : '⏳'}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row g-3">
            <div class="col-12">
                <div class="modern-card p-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-bell text-yellow me-2"></i>Recent Activity</h6>
                    <div id="recentActivity">
                        ${data.notifications && data.notifications.length > 0 ? `
                            ${data.notifications.map(n => `
                                <div class="notification-item py-2 border-bottom">
                                    <div class="small">${n.message}</div>
                                    <small class="text-muted">${new Date(n.created_at).toLocaleString()}</small>
                                </div>
                            `).join('')}
                        ` : `
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                No recent activity
                            </div>
                        `}
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row g-3 mt-2">
            <div class="col-12">
                <div class="modern-card p-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-yellow me-2"></i>Quick Actions</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="${module.url}" class="btn btn-yellow-primary btn-sm">
                            <i class="bi ${module.icon} me-1"></i> ${module.name}
                        </a>
                        <a href="#" class="btn btn-yellow-outline btn-sm" onclick="window.location.reload();">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;

    if (data.pending_contract && data.pending_contract.status === 'pending') {
        wireContractButtons(data.pending_contract.id);
    }
}

function traineeEscapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function renderContractCard(c) {
    const formatPeso = v => '₱' + parseFloat(v).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    const salary = (c.salary_range_min && c.salary_range_max)
        ? `${formatPeso(c.salary_range_min)} – ${formatPeso(c.salary_range_max)}`
        : (c.salary ? formatPeso(c.salary) : '—');
    const restDays = c.rest_days ? c.rest_days.split(',').map(d => d.charAt(0).toUpperCase() + d.slice(1)).join(', ') : '—';
    return `
        <div class="modern-card p-4 mb-4" style="border:2px solid var(--brand-yellow);">
            <h5 class="fw-bold mb-2"><i class="bi bi-file-earmark-text text-yellow me-2"></i>You Have a Hired Contract Waiting</h5>
            <p class="text-muted mb-3">Review the terms below and accept or decline. Accepting will activate your official employee account.</p>
            <div class="row g-2 mb-3">
                <div class="col-md-4"><small class="text-muted d-block">Shift</small><strong>${traineeEscapeHtml(c.shift)}</strong></div>
                <div class="col-md-4"><small class="text-muted d-block">Salary Range</small><strong>${salary}</strong></div>
                <div class="col-md-4"><small class="text-muted d-block">Start Date</small><strong>${traineeEscapeHtml(c.start_date)}</strong></div>
                <div class="col-md-4"><small class="text-muted d-block">Rest Days</small><strong>${restDays}</strong></div>
                <div class="col-md-4"><small class="text-muted d-block">Decision Deadline</small><strong>${traineeEscapeHtml(c.decision_deadline || '—')}</strong></div>
            </div>
            ${c.job_details ? `<p class="small"><strong>Job Details:</strong> ${traineeEscapeHtml(c.job_details)}</p>` : ''}
            <div id="contractResponseAlert"></div>
            <div class="d-flex gap-2 mt-2">
                <button class="btn btn-success" id="acceptContractBtn"><i class="bi bi-check-circle me-1"></i> Accept Contract</button>
                <button class="btn btn-outline-danger" id="declineContractBtn"><i class="bi bi-x-circle me-1"></i> Decline</button>
            </div>
            <div class="mt-2" id="declineNotesWrap" style="display:none;">
                <textarea id="declineNotes" class="form-control form-control-sm mb-2" rows="2" maxlength="500" placeholder="Optional reason for declining..."></textarea>
                <button class="btn btn-danger btn-sm" id="confirmDeclineBtn">Confirm Decline</button>
                <button class="btn btn-secondary btn-sm" id="cancelDeclineBtn">Cancel</button>
            </div>
        </div>
    `;
}

function wireContractButtons(contractId) {
    document.getElementById('acceptContractBtn')?.addEventListener('click', function () {
        if (!confirm('Accept this contract? This will activate your official employee account.')) return;
        respondToContract(contractId, 'accept', '');
    });
    document.getElementById('declineContractBtn')?.addEventListener('click', function () {
        document.getElementById('declineNotesWrap').style.display = 'block';
        this.style.display = 'none';
        document.getElementById('acceptContractBtn').style.display = 'none';
    });
    document.getElementById('cancelDeclineBtn')?.addEventListener('click', function () {
        document.getElementById('declineNotesWrap').style.display = 'none';
        document.getElementById('acceptContractBtn').style.display = 'inline-block';
        document.getElementById('declineContractBtn').style.display = 'inline-block';
    });
    document.getElementById('confirmDeclineBtn')?.addEventListener('click', function () {
        respondToContract(contractId, 'decline', document.getElementById('declineNotes').value.trim());
    });
}

let traineeContractBusy = false;
function respondToContract(contractId, action, responseNotes) {
    if (traineeContractBusy) return;
    traineeContractBusy = true;
    const alertBox = document.getElementById('contractResponseAlert');

    fetch('?page=api_trainee_respond_to_contract', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ contract_id: contractId, action, response_notes: responseNotes })
    })
        .then(r => r.json())
        .then(data => {
            traineeContractBusy = false;
            if (data.success) {
                alertBox.innerHTML = `<div class="alert alert-success small">${traineeEscapeHtml(data.message)} Reloading...</div>`;
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger small">${traineeEscapeHtml(data.message)}</div>`;
            }
        })
        .catch(() => {
            traineeContractBusy = false;
            alertBox.innerHTML = `<div class="alert alert-danger small">Something went wrong. Please try again.</div>`;
        });
}