<?php
$title = 'Interviews - ShelfSense HR';
$pageTitle = 'Interviews';
$activePage = 'interviews';

// Date restrictions
$minDate = date('Y-m-d\TH:i', strtotime('+1 day'));
$maxDate = date('Y-m-d\TH:i', strtotime('+3 months'));

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
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Contract Scheduled</small>
            <h5 class="mb-0 text-secondary" id="statContractScheduled">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Contract Completed</small>
            <h5 class="mb-0 text-dark" id="statContractCompleted">0</h5>
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
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="contract-tab" data-bs-toggle="pill" data-bs-target="#contract-interviews" type="button" role="tab">
            <i class="bi bi-file-earmark-text"></i> Contract
            <span class="badge bg-danger ms-1" id="contractTabBadge" style="display:none;">0</span>
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

    <!-- ============================================ -->
    <!-- CONTRACT INTERVIEWS TAB -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="contract-interviews" role="tabpanel">
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <!-- ✅ SCHEDULED default active -->
            <button class="btn btn-sm btn-outline-warning filter-btn-contract active" data-status="scheduled">
                Scheduled <span class="badge bg-warning text-dark ms-1" id="contractScheduledBadge">0</span>
            </button>
            <button class="btn btn-sm btn-outline-success filter-btn-contract" data-status="completed">
                Completed <span class="badge bg-success text-white ms-1" id="contractCompletedBadge">0</span>
            </button>
            <div class="ms-auto"><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill px-3 py-2" id="contractCount">0</span></div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="autocomplete-wrapper">
                    <input type="text" id="contractSearchInput" class="form-control" placeholder="Search by name..." maxlength="100" autocomplete="off">
                    <div class="autocomplete-dropdown" id="contractAutocompleteDropdown"></div>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-yellow-outline btn-sm w-100" id="refreshContractBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
        </div>
        <div class="modern-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Applicant</th><th>HR</th><th>Date & Time</th><th>Status</th><th>Result</th><th class="text-center">Actions</th></tr></thead>
                        <tbody id="contractInterviewsTableBody"><tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="contractTableInfo">Loading...</span>
                    <nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0" id="contractPaginationContainer"><li class="page-item disabled"><span class="page-link">1</span></li></ul></nav>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Interview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleInterviewForm">
                <input type="hidden" name="interview_type" id="scheduleTypeHidden" value="final">
                <div class="modal-body">
                    <input type="hidden" id="scheduleApplicantId" name="applicant_id">
                    <div class="mb-3">
                        <label class="form-label">Interview Type</label>
                        <select name="interview_type_visible" id="scheduleType" class="form-select" disabled>
                            <option value="final">Final Interview</option>
                        </select>
                        <small class="text-muted">Type is locked for this action.</small>
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
<div class="modal fade" id="interviewDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Interview Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="interviewDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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
                            <input type="number" id="traineeSalaryMin" class="form-control" placeholder="Min" value="3900" step="100" min="3900" max="4500">
                        </div>
                        <div class="col-6">
                            <input type="number" id="traineeSalaryMax" class="form-control" placeholder="Max" value="4500" step="100" min="3900" max="4500">
                        </div>
                    </div>
                    <div class="form-text">Range: ₱3,900 – ₱4,500</div>
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

<script src="/ShelfSense/public/assets/js/hr/interviews.js?v=20260830123402"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';