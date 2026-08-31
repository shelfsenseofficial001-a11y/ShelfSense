<?php
$title = 'Interviews - ShelfSense HR';
$pageTitle = 'Interviews';
$activePage = 'interviews';

// Date restrictions
$minDate = date('Y-m-d\TH:i', strtotime('+1 day'));
$maxDate = date('Y-m-d\TH:i', strtotime('+3 months'));
$todayDate = date('Y-m-d');

// The Owner is always required at the Final Interview -- shown as a
// read-only confirmation row on the scheduling modal (same format as the
// Applicant/Target Role rows on the Trainee Contract and Finalize Hire
// modals), matching whoever schedule_interview.php actually notifies.
$db = \App\Core\Database::getInstance()->getConnection();
$owners = $db->query("SELECT first_name, last_name FROM users WHERE role = 'owner' AND is_active = 1 ORDER BY first_name")->fetchAll();
$ownerNames = implode(', ', array_map(fn($o) => $o['first_name'] . ' ' . $o['last_name'], $owners));
$ownerDisplay = $ownerNames !== '' ? $ownerNames : 'No active Owner account found';

$content = '
<!-- Stats Row -->
<div class="row g-2 mb-3" id="statsRow">
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Initial Scheduled</small>
            <h5 class="mb-0 text-warning" id="statInitialScheduled">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Initial Completed</small>
            <h5 class="mb-0 text-info" id="statInitialCompleted">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Final Scheduled</small>
            <h5 class="mb-0 text-primary" id="statFinalScheduled">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Final Completed</small>
            <h5 class="mb-0 text-success" id="statFinalCompleted">0</h5>
        </div>
    </div>
</div>

<!-- Interview Type Tabs with Badges -->
<ul class="nav nav-pills mb-3" id="interviewTypeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="initial-tab" data-bs-toggle="pill" data-bs-target="#initial-interviews" type="button" role="tab">
            <i class="bi bi-chat"></i> Initial
            <span class="badge bg-danger ms-1" id="initialTabBadge" style="display:none;">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="final-tab" data-bs-toggle="pill" data-bs-target="#final-interviews" type="button" role="tab">
            <i class="bi bi-chat-dots"></i> Final
            <span class="badge bg-danger ms-1" id="finalTabBadge" style="display:none;">0</span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- ============================================ -->
    <!-- INITIAL INTERVIEWS TAB -->
    <!-- ============================================ -->
    <div class="tab-pane fade show active" id="initial-interviews" role="tabpanel">
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <!-- ✅ SCHEDULED is now default active, ALL button removed -->
            <button class="btn btn-sm btn-outline-warning filter-btn-initial active" data-status="scheduled">
                Scheduled <span class="badge bg-warning text-dark ms-1" id="initialScheduledBadge">0</span>
            </button>
            <button class="btn btn-sm btn-outline-success filter-btn-initial" data-status="completed">
                Completed <span class="badge bg-success text-white ms-1" id="initialCompletedBadge">0</span>
            </button>
            <div class="ms-auto"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-2" id="initialCount">0</span></div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="autocomplete-wrapper">
                    <input type="text" id="initialSearchInput" class="form-control" placeholder="Search by name..." maxlength="100" autocomplete="off">
                    <div class="autocomplete-dropdown" id="initialAutocompleteDropdown"></div>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-yellow-outline btn-sm w-100" id="refreshInitialBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="modern-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Applicant</th><th>HR</th><th>Date & Time</th><th>Status</th><th>Result</th><th class="text-center">Actions</th></tr></thead>
                        <tbody id="initialInterviewsTableBody"><tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="initialTableInfo">Loading...</span>
                    <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0" id="initialPaginationContainer"><li class="page-item disabled"><span class="page-link">1</span></li></ul></nav>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- FINAL INTERVIEWS TAB -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="final-interviews" role="tabpanel">
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <!-- ✅ SCHEDULED default active -->
            <button class="btn btn-sm btn-outline-warning filter-btn-final active" data-status="scheduled">
                Scheduled <span class="badge bg-warning text-dark ms-1" id="finalScheduledBadge">0</span>
            </button>
            <button class="btn btn-sm btn-outline-success filter-btn-final" data-status="completed">
                Completed <span class="badge bg-success text-white ms-1" id="finalCompletedBadge">0</span>
            </button>
            <div class="ms-auto"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-2" id="finalCount">0</span></div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="autocomplete-wrapper">
                    <input type="text" id="finalSearchInput" class="form-control" placeholder="Search by name..." maxlength="100" autocomplete="off">
                    <div class="autocomplete-dropdown" id="finalAutocompleteDropdown"></div>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-yellow-outline btn-sm w-100" id="refreshFinalBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="modern-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Applicant</th><th>HR</th><th>Date & Time</th><th>Status</th><th>Result</th><th class="text-center">Actions</th></tr></thead>
                        <tbody id="finalInterviewsTableBody"><tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="finalTableInfo">Loading...</span>
                    <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0" id="finalPaginationContainer"><li class="page-item disabled"><span class="page-link">1</span></li></ul></nav>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ============================================ -->
<!-- MODALS                                      -->
<!-- ============================================ -->

<!-- Set Result Modal -->
<div class="modal fade" id="setResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Interview Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="setResultForm">
                <div class="modal-body">
                    <input type="hidden" id="resultInterviewId" name="interview_id">
                    <div class="mb-3">
                        <label class="form-label">Result</label>
                        <select name="result" id="resultValue" class="form-select" required>
                            <option value="">Select result...</option>
                            <option value="passed">✅ Passed</option>
                            <option value="failed">❌ Failed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" id="resultNotes" class="form-control" rows="3" placeholder="Any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Save Result</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Interview Modal -->
<div class="modal fade" id="scheduleInterviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Interview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleInterviewForm">
                <input type="hidden" name="interview_type" id="scheduleTypeHidden" value="final">
                <div class="modal-body">
                    <input type="hidden" id="scheduleApplicantId" name="applicant_id">
                    <p><strong>Applicant:</strong> <span id="scheduleApplicantName"></span></p>
                    <p><strong>Owner (required for questioning):</strong> <span class="' . ($ownerNames !== '' ? 'text-success' : 'text-danger') . '">' . htmlspecialchars($ownerDisplay) . '</span></p>
                    <div class="mb-3">
                        <label class="form-label">Interview Type</label>
                        <p class="form-control-plaintext fw-semibold mb-0"><i class="bi bi-chat-dots"></i> Final Interview</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" 
                               name="scheduled_date" 
                               id="scheduleDate" 
                               class="form-control" 
                               required
                               min="<?php echo $minDate; ?>"
                               max="<?php echo $maxDate; ?>"
                               oninput="validateDateInput(this)"
                               onblur="validateDateInput(this)">
                        <small class="text-muted">Min: Tomorrow, Max: 3 months from now</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gmeet Link</label>
                        <input type="url" name="gmeet_link" id="scheduleGmeet" class="form-control" placeholder="https://meet.google.com/xxx-xxxx-xxx" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message to Applicant</label>
                        <textarea name="message" id="scheduleMessage" class="form-control" rows="3" maxlength="250" placeholder="Please join the interview via the link above..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Schedule Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Interview Detail Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="interviewDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="interviewDetailBody">
        <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
    </div>
</div>

<!-- Trainer Selection Modal (reused from applicants) -->
<div class="modal fade" id="trainerSelectionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Trainer for Trainee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Applicant:</strong> <span id="trainerApplicantName"></span></p>
                <p><strong>Target Role:</strong> <span id="trainerTargetRole"></span></p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Trainer</label>
                    <select id="trainerSelect" class="form-select">
                        <option value="">Loading trainers...</option>
                    </select>
                    <div class="form-text">Trainer must have the same role as the trainee\'s target role.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Trainee Salary Range</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" id="traineeSalaryMin" class="form-control" placeholder="Min" value="3900" step="100" min="1">
                        </div>
                        <div class="col-6">
                            <input type="number" id="traineeSalaryMax" class="form-control" placeholder="Max" value="4500" step="100" min="1">
                        </div>
                    </div>
                    <div class="form-text">Enter the range discussed with the trainee.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Working Hours (as discussed in the interview)</label>
                    <input type="time" id="traineeScheduleStart" class="form-control" value="10:00">
                    <div class="form-text">Trainee shifts are fixed at exactly <strong>5 hours/day</strong> &mdash; end time is set automatically (<span id="traineeScheduleEndPreview">3:00 PM</span>).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rest Days (maximum 2)</label>
                    <div class="d-flex flex-wrap gap-3" id="traineeRestDaysGroup">
                        <div class="form-check"><input class="form-check-input trainee-rest-day" type="checkbox" value="monday" id="restMonday"><label class="form-check-label" for="restMonday">Mon</label></div>
                        <div class="form-check"><input class="form-check-input trainee-rest-day" type="checkbox" value="tuesday" id="restTuesday"><label class="form-check-label" for="restTuesday">Tue</label></div>
                        <div class="form-check"><input class="form-check-input trainee-rest-day" type="checkbox" value="wednesday" id="restWednesday"><label class="form-check-label" for="restWednesday">Wed</label></div>
                        <div class="form-check"><input class="form-check-input trainee-rest-day" type="checkbox" value="thursday" id="restThursday"><label class="form-check-label" for="restThursday">Thu</label></div>
                        <div class="form-check"><input class="form-check-input trainee-rest-day" type="checkbox" value="friday" id="restFriday"><label class="form-check-label" for="restFriday">Fri</label></div>
                        <div class="form-check"><input class="form-check-input trainee-rest-day" type="checkbox" value="saturday" id="restSaturday" checked><label class="form-check-label" for="restSaturday">Sat</label></div>
                        <div class="form-check"><input class="form-check-input trainee-rest-day" type="checkbox" value="sunday" id="restSunday" checked><label class="form-check-label" for="restSunday">Sun</label></div>
                    </div>
                    <div class="form-text">Cannot select more than 2 rest days.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-yellow-primary btn-sm" id="confirmCreateTraineeBtn">
                    <i class="bi bi-person-plus"></i> Create Trainee Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Finalize Hire Modal (after Final Interview passes) -->
<div class="modal fade" id="finalizeHireModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Finalize Hire</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Record the terms discussed with the Owner and the Trainee at the Final Interview. The applicant will receive this as a Hired Contract to accept or decline themselves.</p>
                <p><strong>Applicant:</strong> <span id="finalizeApplicantName"></span></p>
                <input type="hidden" id="finalizeApplicantId">
                <input type="hidden" id="finalizeInterviewId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Shift</label>
                    <select id="finalizeShift" class="form-select">
                        <option value="opening">Opening (8:00 AM – 5:00 PM)</option>
                        <option value="midshift">Mid-shift (10:00 AM – 6:00 PM)</option>
                        <option value="closing">Closing (2:00 PM – 10:00 PM)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Salary Range</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" id="finalizeSalaryMin" class="form-control" placeholder="Min" step="100" min="1">
                        </div>
                        <div class="col-6">
                            <input type="number" id="finalizeSalaryMax" class="form-control" placeholder="Max" step="100" min="1">
                        </div>
                    </div>
                    <div class="form-text">The midpoint of this range is used as the base rate for payroll; overtime, bonuses, and deductions may bring the final monthly pay above or below it.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" id="finalizeStartDate" class="form-control" min="<?php echo $todayDate; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rest Days (maximum 2)</label>
                    <div class="d-flex flex-wrap gap-3" id="finalizeRestDaysGroup">
                        <div class="form-check"><input class="form-check-input finalize-rest-day" type="checkbox" value="monday" id="finalizeRestMonday"><label class="form-check-label" for="finalizeRestMonday">Mon</label></div>
                        <div class="form-check"><input class="form-check-input finalize-rest-day" type="checkbox" value="tuesday" id="finalizeRestTuesday"><label class="form-check-label" for="finalizeRestTuesday">Tue</label></div>
                        <div class="form-check"><input class="form-check-input finalize-rest-day" type="checkbox" value="wednesday" id="finalizeRestWednesday"><label class="form-check-label" for="finalizeRestWednesday">Wed</label></div>
                        <div class="form-check"><input class="form-check-input finalize-rest-day" type="checkbox" value="thursday" id="finalizeRestThursday"><label class="form-check-label" for="finalizeRestThursday">Thu</label></div>
                        <div class="form-check"><input class="form-check-input finalize-rest-day" type="checkbox" value="friday" id="finalizeRestFriday"><label class="form-check-label" for="finalizeRestFriday">Fri</label></div>
                        <div class="form-check"><input class="form-check-input finalize-rest-day" type="checkbox" value="saturday" id="finalizeRestSaturday" checked><label class="form-check-label" for="finalizeRestSaturday">Sat</label></div>
                        <div class="form-check"><input class="form-check-input finalize-rest-day" type="checkbox" value="sunday" id="finalizeRestSunday" checked><label class="form-check-label" for="finalizeRestSunday">Sun</label></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Leave / Other Notes (from the Meet)</label>
                    <textarea id="finalizeJobDetails" class="form-control" rows="3" maxlength="2000" placeholder="e.g. sick/vacation/maternity leave terms, any other agreements discussed"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-yellow-primary btn-sm" id="confirmFinalizeHireBtn">
                    <i class="bi bi-file-earmark-check"></i> Create Hired Contract
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/ShelfSense/public/assets/js/hr/interviews.js?v=20260831170000"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';