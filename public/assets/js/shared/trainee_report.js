// public/assets/js/shared/trainee_report.js
// Powers the shared "Trainee Report" tab (views/shared/trainee_report.php),
// used by Store Manager / Finance Head / Finance Staff / Employee / HR
// Staff / HR Head alike -- the same page, showing only the section(s) that
// apply to whoever is looking at it.

document.addEventListener('DOMContentLoaded', function () {
    loadTraineeReportQueue();
});

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function trStatusLabel(status) {
    return {
        submitted: 'Submitted', forwarded: 'Forwarded',
        hr_staff_reviewed: 'HR Staff Reviewed', hr_reviewed: 'HR Reviewed'
    }[status] || status;
}

function trRoleLabel(role) {
    return { store_manager: 'Store Manager', finance_head: 'Finance Head', hr_head: 'HR Head', hr_staff: 'HR Staff' }[role] || (role || 'Reviewer');
}

// Same preset lists as the old HR-only admin modal (views/pages/hr/trainees.php
// / trainees.js) -- kept identical so a week's weekly summary reads the same
// whichever surface later displays it.
const TR_STRENGTH_OPTIONS = [
    'Effective Communication', 'Technical / Job Knowledge', 'Punctuality & Reliability',
    'Problem-Solving & Critical Thinking', 'Adaptability & Learning Agility',
    'Teamwork & Collaboration', 'Attention to Detail', 'Initiative & Work Ethic'
];
const TR_IMPROVEMENT_OPTIONS = [
    'Communication Skills', 'Speed & Productivity', 'Attention to Detail',
    'Job Knowledge & Task Execution', 'Time Management & Prioritization',
    'Punctuality & Attendance', 'Adaptability to Feedback', 'Proactivity & Initiative'
];
const TR_DAY_NAMES = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
let TR_SELECTED_DAY = null; // {date: 'YYYY-MM-DD', dayName, weekNumber} of the currently-open day box

function loadTraineeReportQueue() {
    document.getElementById('trReportLoading').classList.remove('d-none');
    document.getElementById('trTrainerSection').classList.add('d-none');
    document.getElementById('trReviewBoard').classList.add('d-none');
    document.getElementById('trEmptyState').classList.add('d-none');

    fetch('?page=api_trainee_get_my_queue')
        .then(r => r.json())
        .then(data => {
            document.getElementById('trReportLoading').classList.add('d-none');
            if (!data.success) {
                document.getElementById('trEmptyState').classList.remove('d-none');
                return;
            }
            const d = data.data;
            let anyShown = false;

            if (d.my_active_trainee) {
                anyShown = true;
                document.getElementById('trTrainerSection').classList.remove('d-none');
                renderTraineeSummary(d.my_active_trainee);
                loadTrainerTimeline(d.my_active_trainee.id);
            }

            loadReviewBoard().then(hasBoard => {
                if (hasBoard) anyShown = true;
                if (!anyShown) {
                    document.getElementById('trEmptyState').classList.remove('d-none');
                }
            });
        })
        .catch(() => {
            document.getElementById('trReportLoading').classList.add('d-none');
            document.getElementById('trEmptyState').classList.remove('d-none');
        });
}

// ============================================
// TRAINER SECTION
// ============================================

function renderTraineeSummary(t) {
    const name = `${t.applicant_first} ${t.applicant_last}`;
    const end = new Date(t.end_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const daysLeft = Math.max(0, Math.ceil((end - today) / 86400000));
    document.getElementById('trTraineeSummary').innerHTML = `
        <p class="mb-1"><strong>${escapeHtml(name)}</strong> &mdash; training as <em>${escapeHtml(t.target_role)}</em></p>
        <p class="text-muted small mb-0">${daysLeft} day(s) remaining in training (ends ${end.toLocaleDateString()})</p>
    `;
}

function loadTrainerTimeline(traineeId) {
    Promise.all([
        fetch(`?page=api_trainee_get_reports&trainee_id=${traineeId}`).then(r => r.json()),
        fetch(`?page=api_trainee_get_daily_logs&trainee_id=${traineeId}`).then(r => r.json())
    ])
        .then(([reportsRes, logsRes]) => {
            if (reportsRes.success) renderTimeline(reportsRes.data.reports || []);
            if (logsRes.success) renderDayGrid(traineeId, logsRes.data);
        })
        .catch(() => {});
}

// Local-date (not UTC) YYYY-MM-DD -- toISOString() converts to UTC first,
// which silently shifts the date backward a day in any timezone ahead of
// UTC (e.g. Asia/Manila, UTC+8): midnight local Sep 8 is Sep 7, 16:00 UTC.
function trLocalISODate(d) {
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

// Builds the 7 consecutive calendar days of one week, same math
// save_daily_log.php/submit_report.php use server-side: start_date + (week-1)*7 days.
function trComputeWeekDays(startDateStr, weekNumber, restDays) {
    const restSet = new Set((restDays || []).map(d => d.toLowerCase()));
    const start = new Date(startDateStr + 'T00:00:00');
    const weekStart = new Date(start);
    weekStart.setDate(weekStart.getDate() + (weekNumber - 1) * 7);
    const days = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(weekStart);
        d.setDate(d.getDate() + i);
        const dayName = TR_DAY_NAMES[(d.getDay() + 6) % 7]; // JS Sunday=0 -> Monday-first index
        const iso = trLocalISODate(d);
        days.push({ date: d, iso, dayName, isRestDay: restSet.has(dayName) });
    }
    return days;
}

function renderDayGrid(traineeId, logsData) {
    const wrap = document.getElementById('trSubmitFormWrap');
    const openWeek = logsData.current_open_week;

    if (!openWeek) {
        wrap.innerHTML = '<div class="alert alert-success mb-0">All 12 weekly reports have been submitted.</div>';
        return;
    }

    const days = trComputeWeekDays(logsData.trainee_start_date, openWeek, logsData.trainee_rest_days);
    const logsByDate = {};
    (logsData.daily_logs || []).forEach(l => { logsByDate[l.log_date] = l; });

    const dayLabels = { monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun' };
    const nonRestDates = days.filter(d => !d.isRestDay).map(d => d.iso);
    const missingDates = nonRestDates.filter(iso => !logsByDate[iso]);

    if (!TR_SELECTED_DAY || TR_SELECTED_DAY.weekNumber !== openWeek) {
        const firstOpenDay = days.find(d => !d.isRestDay) || days[0];
        TR_SELECTED_DAY = { date: firstOpenDay.iso, dayName: firstOpenDay.dayName, weekNumber: openWeek };
    }

    const strengthOptions = TR_STRENGTH_OPTIONS.map(o => `<option value="${escapeHtml(o)}">${escapeHtml(o)}</option>`).join('');
    const improvementOptions = TR_IMPROVEMENT_OPTIONS.map(o => `<option value="${escapeHtml(o)}">${escapeHtml(o)}</option>`).join('');

    wrap.innerHTML = `
        <h6>Week ${openWeek}</h6>
        <div class="d-flex gap-2 flex-wrap mb-2" id="trDayGrid">
            ${days.map(d => `
                <div class="tr-day-box ${d.isRestDay ? 'tr-day-rest' : ''} ${logsByDate[d.iso] ? 'tr-day-filled' : ''} ${TR_SELECTED_DAY.date === d.iso ? 'tr-day-selected' : ''}"
                     data-date="${d.iso}" data-day-name="${d.dayName}" ${d.isRestDay ? '' : 'role="button"'}>
                    <div class="tr-day-name">${dayLabels[d.dayName]}</div>
                    <div class="tr-day-num">${d.date.getDate()}</div>
                    ${d.isRestDay ? '<div class="tr-day-tag">Rest</div>' : (logsByDate[d.iso] ? '<i class="bi bi-check-circle-fill tr-day-check"></i>' : '')}
                </div>
            `).join('')}
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold" id="trDayContentLabel">Report for ${dayLabels[TR_SELECTED_DAY.dayName]}</label>
            <textarea class="form-control" id="trDayContent" rows="3" maxlength="2000" placeholder="What happened today?">${escapeHtml((logsByDate[TR_SELECTED_DAY.date] || {}).content || '')}</textarea>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="trSaveDayBtn"><i class="bi bi-save"></i> Submit Report</button>

        <hr>
        <h6>Week ${openWeek} Summary <small class="text-muted fw-normal">(submit once every working day above is logged)</small></h6>
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label small">Strengths</label>
                <select id="trStrengths" class="form-select form-select-sm">
                    <option value="">-- select --</option>
                    ${strengthOptions}
                    <option value="__other__">Other (specify)</option>
                </select>
                <input type="text" id="trStrengthsOther" class="form-control form-control-sm mt-1" style="display:none;" maxlength="500" placeholder="Specify strength...">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Areas for Improvement</label>
                <select id="trImprovements" class="form-select form-select-sm">
                    <option value="">-- select --</option>
                    ${improvementOptions}
                    <option value="__other__">Other (specify)</option>
                </select>
                <input type="text" id="trImprovementsOther" class="form-control form-control-sm mt-1" style="display:none;" maxlength="500" placeholder="Specify area for improvement...">
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label small">Attendance/Punctuality Notes</label>
                <input type="text" id="trAttendanceNotes" class="form-control form-control-sm" maxlength="1000">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Performance Rating</label>
                <select id="trPerformanceRating" class="form-select form-select-sm">
                    <option value="">-- none --</option>
                    <option value="excellent">Excellent</option>
                    <option value="good">Good</option>
                    <option value="satisfactory">Satisfactory</option>
                    <option value="needs_improvement">Needs Improvement</option>
                    <option value="poor">Poor</option>
                </select>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label small">Recommendation</label>
            <input type="text" class="form-control form-control-sm" id="trRecommendation" maxlength="30" placeholder="e.g. Continue training">
        </div>
        ${missingDates.length ? `<div class="text-muted small mb-2">Log every working day before submitting the week.</div>` : ''}
        <button type="button" class="btn btn-yellow-primary btn-sm" id="trSubmitWeekBtn" ${missingDates.length ? 'disabled' : ''}>Submit Week Report</button>
    `;

    document.querySelectorAll('#trDayGrid .tr-day-box[role="button"]').forEach(box => {
        box.addEventListener('click', function () {
            TR_SELECTED_DAY = { date: this.dataset.date, dayName: this.dataset.dayName, weekNumber: openWeek };
            document.querySelectorAll('#trDayGrid .tr-day-box').forEach(b => b.classList.remove('tr-day-selected'));
            this.classList.add('tr-day-selected');
            document.getElementById('trDayContentLabel').textContent = `Report for ${dayLabels[this.dataset.dayName]}`;
            document.getElementById('trDayContent').value = (logsByDate[this.dataset.date] || {}).content || '';
        });
    });

    document.getElementById('trSaveDayBtn').addEventListener('click', function () {
        saveDailyLog(traineeId, openWeek);
    });
    document.getElementById('trStrengths').addEventListener('change', function () {
        document.getElementById('trStrengthsOther').style.display = this.value === '__other__' ? 'block' : 'none';
    });
    document.getElementById('trImprovements').addEventListener('change', function () {
        document.getElementById('trImprovementsOther').style.display = this.value === '__other__' ? 'block' : 'none';
    });
    document.getElementById('trSubmitWeekBtn').addEventListener('click', function () {
        submitWeeklyReport(traineeId, openWeek);
    });
}

function saveDailyLog(traineeId, weekNumber) {
    const content = document.getElementById('trDayContent').value.trim();
    if (!content) {
        Swal.fire({ icon: 'warning', title: 'Entry required', text: 'Please describe this day before submitting.' });
        return;
    }
    fetch('?page=api_trainee_save_daily_log', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ trainee_id: traineeId, week_number: weekNumber, log_date: TR_SELECTED_DAY.date, content })
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Day Logged', timer: 1500, showConfirmButton: false });
                loadTrainerTimeline(traineeId);
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' }));
}

function submitWeeklyReport(traineeId, weekNumber) {
    const strengthsSelect = document.getElementById('trStrengths').value;
    const improvementsSelect = document.getElementById('trImprovements').value;
    const strengthsOther = document.getElementById('trStrengthsOther').value.trim();
    const improvementsOther = document.getElementById('trImprovementsOther').value.trim();

    if (strengthsSelect === '__other__' && !strengthsOther) {
        Swal.fire({ icon: 'warning', title: 'Strengths Required', text: 'Please specify the strength.' });
        return;
    }
    if (improvementsSelect === '__other__' && !improvementsOther) {
        Swal.fire({ icon: 'warning', title: 'Areas for Improvement Required', text: 'Please specify the area for improvement.' });
        return;
    }

    const payload = {
        trainee_id: traineeId,
        week_number: weekNumber,
        performance_rating: document.getElementById('trPerformanceRating').value,
        recommendation: document.getElementById('trRecommendation').value.trim(),
        strengths: strengthsSelect === '__other__' ? strengthsOther : strengthsSelect,
        improvements: improvementsSelect === '__other__' ? improvementsOther : improvementsSelect,
        attendance_notes: document.getElementById('trAttendanceNotes').value.trim()
    };
    fetch('?page=api_trainee_submit_report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Report Submitted', text: result.message, timer: 2000, showConfirmButton: false });
                TR_SELECTED_DAY = null;
                loadTrainerTimeline(traineeId);
                if (typeof updateTraineeReportBadge === 'function') updateTraineeReportBadge();
            } else {
                Swal.fire({ icon: 'error', title: 'Submission Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' }));
}

function renderTimeline(reports) {
    const container = document.getElementById('trTrainerTimeline');
    if (!reports.length) {
        container.innerHTML = '<p class="text-muted small">No reports submitted yet.</p>';
        return;
    }
    container.innerHTML = reports.map(r => `
        <div class="tr-timeline-item">
            <div class="d-flex justify-content-between align-items-start">
                <strong>Week ${r.week_number}</strong>
                <span class="tr-status-chip ${escapeHtml(r.status)}">${trStatusLabel(r.status)}</span>
            </div>
            <p class="mb-1 small">${escapeHtml(r.report_content).replace(/\n/g, '<br>')}</p>
            ${r.reviewer_observation ? `<p class="mb-1 small text-muted"><strong>${escapeHtml(trRoleLabel(r.reviewer_role))}'s note:</strong> ${escapeHtml(r.reviewer_observation)}</p>` : ''}
            ${r.hr_head_notes ? `<p class="mb-0 small text-muted"><strong>HR Head's note:</strong> ${escapeHtml(r.hr_head_notes)}</p>` : ''}
        </div>
    `).join('');
}

// ============================================
// REVIEW BOARD: trainee list -> week tabs -> content panel
// Shared by dept head (submitted), HR Staff (screening), and HR Head
// (final review) -- which stage(s) a trainee's weeks are actionable at is
// decided server-side (get_review_board.php) via each week's
// actionable_by_me flag, so this code doesn't need to know the viewer's role.
// ============================================

let TR_BOARD_DATA = { active_trainees: [], history_trainees: [] };
let TR_BOARD_MODE = 'active'; // 'active' | 'history'
let TR_BOARD_SELECTED_TRAINEE = null;
let TR_BOARD_SELECTED_WEEK = null;
const TR_BOARD_REPORTS_CACHE = {};

function loadReviewBoard() {
    return fetch('?page=api_trainee_get_review_board')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return false;
            TR_BOARD_DATA = data.data;
            const hasAny = TR_BOARD_DATA.active_trainees.length > 0 || TR_BOARD_DATA.history_trainees.length > 0;
            if (!hasAny) return false;
            document.getElementById('trReviewBoard').classList.remove('d-none');
            renderBoardTraineeList();
            return true;
        })
        .catch(() => false);
}

function currentBoardList() {
    return TR_BOARD_MODE === 'history' ? TR_BOARD_DATA.history_trainees : TR_BOARD_DATA.active_trainees;
}

function renderBoardTraineeList() {
    const search = (document.getElementById('trBoardSearch').value || '').toLowerCase();
    const container = document.getElementById('trBoardTraineeList');
    const list = currentBoardList().filter(t => t.name.toLowerCase().includes(search));

    if (!list.length) {
        container.innerHTML = `<p class="text-muted small p-2 mb-0">No ${TR_BOARD_MODE === 'history' ? 'past' : 'active'} trainees.</p>`;
    } else {
        container.innerHTML = list.map(t => {
            const weeks = t.weeks || [];
            const needCount = weeks.filter(w => w.actionable_by_me).length;
            const finalized = weeks.filter(w => w.status === 'hr_reviewed').length;
            const totalWeeks = weeks.length || 12;
            // Right-aligned marker: for active trainees, an amber "needs you"
            // counter takes priority so reviewers can triage the list at a
            // glance; otherwise fall back to the finalized x/N progress. Past
            // trainees show their accepted/failed outcome instead.
            let marker = '';
            if (t.outcome) {
                marker = `<span class="tr-board-outcome ${t.outcome}">${t.outcome === 'accepted' ? 'Accepted' : 'Failed'}</span>`;
            } else if (needCount > 0) {
                marker = `<span class="tr-trainee-need" title="${needCount} week${needCount === 1 ? '' : 's'} need your action">${needCount} to review</span>`;
            } else {
                marker = `<span class="tr-trainee-progress">${finalized}/${totalWeeks}</span>`;
            }
            return `
            <button type="button" class="tr-board-trainee-row list-group-item ${TR_BOARD_SELECTED_TRAINEE === t.trainee_id ? 'active' : ''}" data-trainee-id="${t.trainee_id}">
                <div class="d-flex justify-content-between align-items-center">
                    <span>${escapeHtml(t.name)}</span>
                    ${marker}
                </div>
                <div class="text-muted small">${escapeHtml(t.target_role)}</div>
            </button>
        `;
        }).join('');
    }

    container.querySelectorAll('.tr-board-trainee-row').forEach(btn => {
        btn.addEventListener('click', () => selectBoardTrainee(parseInt(btn.dataset.traineeId, 10)));
    });

    const histToggle = document.getElementById('trBoardHistoryToggle');
    const histLabel = histToggle.querySelector('.tr-history-toggle-label');
    const histIcon = histToggle.querySelector('i');
    if (histLabel) histLabel.textContent = TR_BOARD_MODE === 'history' ? 'Back to Active' : 'View History';
    if (histIcon) histIcon.className = (TR_BOARD_MODE === 'history' ? 'bi bi-arrow-left' : 'bi bi-clock-history') + ' me-1';
}

function selectBoardTrainee(traineeId) {
    TR_BOARD_SELECTED_TRAINEE = traineeId;
    TR_BOARD_SELECTED_WEEK = null;
    renderBoardTraineeList();
    renderBoardWeekTabs();
    const trainee = findBoardTrainee(traineeId);
    document.getElementById('trBoardContent').innerHTML =
        renderTrainerPanel(trainee) +
        renderEligibilityPanel(trainee) +
        '<div class="tr-content-placeholder"><i class="bi bi-calendar-week"></i><p class="small mb-0">Pick a week on the left to view its report.</p></div>';
}

// Reassign Trainer: open to any HR role that can manage trainees (not just
// HR Head), reusing the existing update_trainee.php reassign_trainer action
// that the old standalone Trainees page used to expose.
function renderTrainerPanel(trainee) {
    if (!trainee || TR_BOARD_MODE === 'history' || !window.HR_CAN_MANAGE_TRAINEES) return '';
    return `
        <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background: var(--bg-card-subtle);">
            <span class="small"><strong>Trainer:</strong> ${trainee.trainer_name ? escapeHtml(trainee.trainer_name) : '<span class="text-muted">Not assigned</span>'}</span>
            <button type="button" class="btn btn-sm btn-outline-warning tr-reassign-trainer-btn" data-trainee-id="${trainee.trainee_id}" data-role="${escapeHtml(trainee.target_role)}" data-name="${escapeHtml(trainee.name)}">
                <i class="bi bi-arrow-repeat"></i> Reassign Trainer
            </button>
        </div>
    `;
}

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.tr-reassign-trainer-btn');
    if (btn) openReassignTrainerModal(btn.dataset.traineeId, btn.dataset.role, btn.dataset.name);
});

function openReassignTrainerModal(traineeId, targetRole, name) {
    const selectId = 'trReassignTrainerSelect';
    let modalEl = document.getElementById('trReassignTrainerModal');
    if (!modalEl) {
        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="trReassignTrainerModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reassign Trainer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Trainee:</strong> <span id="trReassignTraineeName"></span></p>
                            <input type="hidden" id="trReassignTraineeId">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">New Trainer</label>
                                <select id="${selectId}" class="form-select"><option value="">Loading trainers...</option></select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-yellow-primary btn-sm" id="trConfirmReassignBtn">Reassign</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        modalEl = document.getElementById('trReassignTrainerModal');
        document.getElementById('trConfirmReassignBtn').addEventListener('click', submitReassignTrainer);
    }
    document.getElementById('trReassignTraineeId').value = traineeId;
    document.getElementById('trReassignTraineeName').textContent = name;
    const select = document.getElementById(selectId);
    select.innerHTML = '<option value="">Loading trainers...</option>';
    fetch(`?page=api_get_trainers_by_role&role=${encodeURIComponent(targetRole)}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data.trainers.length > 0) {
                select.innerHTML = '<option value="" disabled selected hidden>Select a trainer...</option>' +
                    data.data.trainers.map(t => `<option value="${t.user_id}">${escapeHtml(t.first_name)} ${escapeHtml(t.last_name)}</option>`).join('');
            } else {
                select.innerHTML = '<option value="">No trainers available for this role</option>';
            }
        })
        .catch(() => { select.innerHTML = '<option value="">Error loading trainers</option>'; });
    new bootstrap.Modal(modalEl).show();
}

function submitReassignTrainer() {
    const traineeId = document.getElementById('trReassignTraineeId').value;
    const trainerId = document.getElementById('trReassignTrainerSelect').value;
    if (!trainerId) {
        Swal.fire({ icon: 'warning', title: 'Please Select a Trainer', text: 'Choose a trainer to reassign.' });
        return;
    }
    fetch('?page=api_update_trainee', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ trainee_id: traineeId, action: 'reassign_trainer', trainer_id: trainerId })
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('trReassignTrainerModal'))?.hide();
                Swal.fire({ icon: 'success', title: 'Trainer Reassigned!', text: result.message, timer: 1500, showConfirmButton: false })
                    .then(() => loadReviewBoard());
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
        });
}

// Eligibility decision: only once every week has reached HR Head's final
// review (status 'hr_reviewed'), only for HR Head/SuperAdmin, and only if
// not already decided. Reuses the existing api_update_trainee 'complete'/
// 'terminate' actions (already gated server-side to HR Head).
function renderEligibilityPanel(trainee) {
    if (!trainee || TR_BOARD_MODE === 'history' || !window.HR_IS_HEAD) return '';
    if (trainee.eligible_for_contract) {
        return `
            <div class="alert alert-primary d-flex justify-content-between align-items-center mb-3">
                <span><i class="bi bi-check2-circle"></i> Eligible for a Hiring Contract.</span>
                <a class="btn btn-sm btn-success" href="?page=hr_interviews&schedule_final=${encodeURIComponent(trainee.applicant_id)}&name=${encodeURIComponent(trainee.name)}"><i class="bi bi-calendar-check"></i> Schedule Final Interview</a>
            </div>
        `;
    }
    const allReviewed = trainee.weeks.length > 0 && trainee.weeks.every(w => w.status === 'hr_reviewed');
    if (!allReviewed) return '';
    return `
        <div class="alert alert-success d-flex justify-content-between align-items-center mb-3">
            <span><i class="bi bi-check2-circle"></i> All 12 weeks reviewed. Decide eligibility for a Hiring Contract.</span>
            <span>
                <button type="button" class="btn btn-sm btn-success tr-eligible-btn" data-trainee-id="${trainee.trainee_id}" data-name="${escapeHtml(trainee.name)}">Eligible for Hiring Contract</button>
                <button type="button" class="btn btn-sm btn-outline-danger tr-terminate-btn" data-trainee-id="${trainee.trainee_id}" data-name="${escapeHtml(trainee.name)}">Terminate</button>
            </span>
        </div>
    `;
}

document.addEventListener('click', function(e) {
    const eligibleBtn = e.target.closest('.tr-eligible-btn');
    const terminateBtn = e.target.closest('.tr-terminate-btn');
    if (eligibleBtn) traineeEligibilityDecision(parseInt(eligibleBtn.dataset.traineeId, 10), 'complete', eligibleBtn.dataset.name);
    if (terminateBtn) traineeEligibilityDecision(parseInt(terminateBtn.dataset.traineeId, 10), 'terminate', terminateBtn.dataset.name);
});

function traineeEligibilityDecision(traineeId, action, name, override = false, reason = '') {
    Swal.fire({ title: 'Processing...', text: 'Please wait.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('?page=api_update_trainee', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ trainee_id: traineeId, action, override, reason })
    })
        .then(r => r.json())
        .then(result => {
            Swal.close();
            if (result.success) {
                const msg = action === 'complete'
                    ? `<p><strong>${escapeHtml(name)}</strong> is now eligible for the Final Interview.</p><p class="text-muted">Next step: go to the <strong>Interviews</strong> page and schedule their Final Interview.</p>`
                    : `<p><strong>${escapeHtml(name)}</strong> has been terminated.</p><p class="text-muted">The trainee account has been deactivated and the trainer released.</p>`;
                Swal.fire({ icon: 'success', title: action === 'complete' ? 'Trainee Marked Eligible!' : 'Trainee Terminated', html: msg, confirmButtonText: 'OK' })
                    .then(() => loadReviewBoard());
            } else if (result.data && result.data.missing_weeks && action === 'complete') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Reports Incomplete',
                    html: `<p>${escapeHtml(result.message)}</p><textarea id="overrideReason" class="swal2-textarea" placeholder="Reason for overriding..."></textarea>`,
                    showCancelButton: true,
                    confirmButtonText: 'Override & Mark Eligible',
                    preConfirm: () => {
                        const r = document.getElementById('overrideReason').value.trim();
                        if (!r) { Swal.showValidationMessage('An override reason is required.'); return false; }
                        return r;
                    }
                }).then(r2 => { if (r2.isConfirmed) traineeEligibilityDecision(traineeId, action, name, true, r2.value); });
            } else {
                Swal.fire({ icon: 'error', title: 'Action Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => {
            Swal.close();
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' });
        });
}

function findBoardTrainee(traineeId) {
    return currentBoardList().find(t => t.trainee_id === traineeId);
}

// Short per-status label shown at the right edge of a week row. Kept terse
// so it fits the narrow week column.
function trWeekStateLabel(status) {
    switch (status) {
        case 'submitted': return 'Submitted';
        case 'forwarded': return 'Forwarded';
        case 'hr_staff_reviewed': return 'Screened';
        case 'hr_reviewed': return '<i class="bi bi-check-lg"></i> Done';
        default: return '&mdash;';
    }
}

function renderBoardWeekTabs() {
    const wrap = document.getElementById('trBoardWeekTabs');
    const trainee = findBoardTrainee(TR_BOARD_SELECTED_TRAINEE);
    if (!trainee) {
        wrap.innerHTML = '';
        return;
    }

    const weeks = trainee.weeks;
    const total = weeks.length || 12;
    const finalized = weeks.filter(w => w.status === 'hr_reviewed').length;
    const needCount = weeks.filter(w => w.actionable_by_me).length;
    const pct = total ? Math.round((finalized / total) * 100) : 0;

    const rows = weeks.map(w => {
        const classes = ['tr-week-tab'];
        if (w.status !== 'not_submitted') classes.push('tr-week-clickable');
        if (w.actionable_by_me) classes.push('tr-week-actionable');
        if (w.status === 'hr_reviewed') classes.push('tr-week-done');
        if (TR_BOARD_SELECTED_WEEK === w.week_number) classes.push('tr-week-selected');
        const dotClass = w.actionable_by_me ? 'actionable' : w.status;
        return `<div class="${classes.join(' ')}" data-week="${w.week_number}">
            <span class="tr-week-dot ${dotClass}"></span>
            <span class="tr-week-label">Week ${w.week_number}</span>
            <span class="tr-week-state">${trWeekStateLabel(w.status)}</span>
        </div>`;
    }).join('');

    wrap.innerHTML = `
        <div class="tr-week-progress">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="tr-week-progress-label">Progress</span>
                <span class="tr-week-progress-count">${finalized}/${total}</span>
            </div>
            <div class="tr-week-bar"><span style="width:${pct}%"></span></div>
            ${needCount > 0 ? `<div class="mt-2"><span class="tr-week-need-pill"><i class="bi bi-bell-fill"></i> ${needCount} need${needCount === 1 ? 's' : ''} you</span></div>` : ''}
        </div>
        <div class="tr-week-scroll">${rows}</div>
        <div class="tr-week-legend">
            <span class="tr-week-legend-item"><span class="tr-week-dot actionable"></span>Needs you</span>
            <span class="tr-week-legend-item"><span class="tr-week-dot submitted"></span>In review</span>
            <span class="tr-week-legend-item"><span class="tr-week-dot hr_reviewed"></span>Done</span>
            <span class="tr-week-legend-item"><span class="tr-week-dot"></span>Not submitted</span>
        </div>
    `;

    wrap.querySelectorAll('.tr-week-tab.tr-week-clickable').forEach(tab => {
        tab.addEventListener('click', () => selectBoardWeek(parseInt(tab.dataset.week, 10)));
    });
}

function selectBoardWeek(weekNumber) {
    TR_BOARD_SELECTED_WEEK = weekNumber;
    renderBoardWeekTabs();

    const content = document.getElementById('trBoardContent');
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

    const loadReports = TR_BOARD_REPORTS_CACHE[TR_BOARD_SELECTED_TRAINEE]
        ? Promise.resolve(TR_BOARD_REPORTS_CACHE[TR_BOARD_SELECTED_TRAINEE])
        : fetch(`?page=api_trainee_get_reports&trainee_id=${TR_BOARD_SELECTED_TRAINEE}`).then(r => r.json()).then(data => {
            const reports = data.success ? (data.data.reports || []) : [];
            TR_BOARD_REPORTS_CACHE[TR_BOARD_SELECTED_TRAINEE] = reports;
            return reports;
        });

    loadReports.then(reports => {
        const report = reports.find(r => Number(r.week_number) === weekNumber);
        const weekEntry = findBoardTrainee(TR_BOARD_SELECTED_TRAINEE).weeks.find(w => w.week_number === weekNumber);
        renderBoardContent(report, weekEntry);
    }).catch(() => {
        content.innerHTML = '<p class="text-danger small mb-0">Failed to load this week.</p>';
    });
}

function renderBoardContent(report, weekEntry) {
    const content = document.getElementById('trBoardContent');

    if (!report) {
        content.innerHTML = '<p class="text-muted small mb-0">This week has not been submitted by the trainer yet.</p>';
        return;
    }

    const hrSkippedBanner = report.department === 'hr'
        ? `<div class="alert alert-info py-2 px-3 small mb-3">HR Staff screening is skipped for this trainee -- their trainer is already HR Staff, so this report goes straight to HR Head.</div>`
        : '';

    const readOnlyHtml = `
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0">Week ${report.week_number}</h6>
            <span class="tr-status-chip ${escapeHtml(report.status)}">${trStatusLabel(report.status)}</span>
        </div>
        <p class="small text-muted mb-2">Submitted ${new Date(report.submitted_at).toLocaleString()} by ${escapeHtml(report.trainer_first)} ${escapeHtml(report.trainer_last)}</p>
        <p class="mb-2">${escapeHtml(report.report_content).replace(/\n/g, '<br>')}</p>
        ${report.performance_rating ? `<p class="small mb-1"><strong>Rating:</strong> ${escapeHtml(report.performance_rating)}</p>` : ''}
        ${report.strengths ? `<p class="small mb-1"><strong>Strengths:</strong> ${escapeHtml(report.strengths)}</p>` : ''}
        ${report.improvements ? `<p class="small mb-1"><strong>Improvements:</strong> ${escapeHtml(report.improvements)}</p>` : ''}
        ${report.attendance_notes ? `<p class="small mb-1"><strong>Attendance:</strong> ${escapeHtml(report.attendance_notes)}</p>` : ''}
        ${report.recommendation ? `<p class="small mb-2"><strong>Recommendation:</strong> ${escapeHtml(report.recommendation)}</p>` : ''}
        ${report.reviewer_observation ? `<p class="small text-muted mb-1"><strong>${escapeHtml(trRoleLabel(report.reviewer_role))}'s observation:</strong> ${escapeHtml(report.reviewer_observation)}</p>` : ''}
        ${report.hr_staff_notes ? `<p class="small text-muted mb-1"><strong>HR Staff's note:</strong> ${escapeHtml(report.hr_staff_notes)}</p>` : ''}
        ${report.hr_head_notes ? `<p class="small text-muted mb-1"><strong>HR Head's note:</strong> ${escapeHtml(report.hr_head_notes)}</p>` : ''}
        <hr>
    `;

    if (!weekEntry || !weekEntry.actionable_by_me) {
        content.innerHTML = hrSkippedBanner + readOnlyHtml + '<p class="text-muted small mb-0">Nothing for you to do on this week right now.</p>';
        return;
    }

    if (report.status === 'submitted') {
        const forwardLabel = report.department === 'hr' ? 'Forward to HR Head' : 'Forward to HR Staff';
        content.innerHTML = hrSkippedBanner + readOnlyHtml + `
            <div class="mb-2">
                <label class="form-label small fw-semibold">Your Observation (optional)</label>
                <textarea class="form-control" id="trObservationInput" rows="3" maxlength="3000"></textarea>
            </div>
            <button type="button" class="btn btn-yellow-primary btn-sm" id="trForwardBtn">${forwardLabel}</button>
        `;
        document.getElementById('trForwardBtn').addEventListener('click', () => forwardReport(report.id));
    } else if (report.status === 'forwarded') {
        content.innerHTML = hrSkippedBanner + readOnlyHtml + `
            <div class="mb-2">
                <label class="form-label small fw-semibold">Screening Notes (optional)</label>
                <textarea class="form-control" id="trHrStaffNotesInput" rows="3" maxlength="3000"></textarea>
            </div>
            <button type="button" class="btn btn-yellow-primary btn-sm" id="trHrStaffForwardBtn">Forward to HR Head</button>
        `;
        document.getElementById('trHrStaffForwardBtn').addEventListener('click', () => screenReport(report.id));
    } else {
        content.innerHTML = hrSkippedBanner + readOnlyHtml + `
            <div class="mb-2">
                <label class="form-label small fw-semibold">HR Head Notes (optional)</label>
                <textarea class="form-control" id="trHrNotesInput" rows="3" maxlength="2000">${escapeHtml(report.hr_head_notes || '')}</textarea>
            </div>
            <button type="button" class="btn btn-yellow-primary btn-sm" id="trHrSaveBtn">Finalize Review</button>
        `;
        document.getElementById('trHrSaveBtn').addEventListener('click', () => saveHrReview(report.id));
    }
}

function trBoardRefreshAfterAction() {
    delete TR_BOARD_REPORTS_CACHE[TR_BOARD_SELECTED_TRAINEE];
    if (typeof updateTraineeReportBadge === 'function') updateTraineeReportBadge();
    loadReviewBoard().then(() => {
        if (TR_BOARD_SELECTED_TRAINEE && TR_BOARD_SELECTED_WEEK) {
            renderBoardWeekTabs();
            selectBoardWeek(TR_BOARD_SELECTED_WEEK);
        }
    });
}

function forwardReport(reportId) {
    const observation = document.getElementById('trObservationInput').value.trim();
    fetch('?page=api_trainee_add_observation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_id: reportId, observation })
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Forwarded', timer: 1800, showConfirmButton: false });
                trBoardRefreshAfterAction();
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' }));
}

function screenReport(reportId) {
    const notes = document.getElementById('trHrStaffNotesInput').value.trim();
    fetch('?page=api_trainee_add_hr_staff_screening', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_id: reportId, notes })
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Forwarded to HR Head', timer: 1800, showConfirmButton: false });
                trBoardRefreshAfterAction();
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' }));
}

function saveHrReview(reportId) {
    const notes = document.getElementById('trHrNotesInput').value.trim();
    fetch('?page=api_trainee_hr_head_review_report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_id: reportId, notes })
    })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Saved', timer: 1800, showConfirmButton: false });
                trBoardRefreshAfterAction();
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: result.message || 'Please try again.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong. Please try again.' }));
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('trBoardSearch');
    if (searchInput) searchInput.addEventListener('input', renderBoardTraineeList);

    const historyToggle = document.getElementById('trBoardHistoryToggle');
    if (historyToggle) {
        historyToggle.addEventListener('click', function () {
            TR_BOARD_MODE = TR_BOARD_MODE === 'history' ? 'active' : 'history';
            TR_BOARD_SELECTED_TRAINEE = null;
            TR_BOARD_SELECTED_WEEK = null;
            renderBoardTraineeList();
            document.getElementById('trBoardWeekTabs').innerHTML = '';
            document.getElementById('trBoardContent').innerHTML = '<div class="tr-content-placeholder"><i class="bi bi-clipboard2-check"></i><p class="small mb-0">Select a trainee, then a week,<br>to view their report.</p></div>';
        });
    }
});
