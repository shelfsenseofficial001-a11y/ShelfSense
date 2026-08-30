<?php
$title = 'Applicants - ShelfSense HR';
$pageTitle = 'Applicants';
$activePage = 'applicants';

// Date restrictions
$minDate = date('Y-m-d\TH:i', strtotime('+1 day'));
$maxDate = date('Y-m-d\TH:i', strtotime('+3 months'));

$content = '
<!-- Filters -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select searchable-select" data-placeholder="Filter by status..." maxlength="40">
            <option value="all" selected>All Status</option>
            <option value="pending">New Applicant</option>
            <option value="initial_scheduled">Initial Interview Pending</option>
            <option value="initial_passed">Initial Interview Passed</option>
            <option value="initial_failed">Initial Interview Failed</option>
            <option value="screening">Trainee Contract (In Training)</option>
            <option value="screening_success">Trainee Contract Completed</option>
            <option value="screening_failed">Trainee Contract Failed</option>
            <option value="final_scheduled">Final Interview Pending</option>
            <option value="final_passed">Final Interview Passed</option>
            <option value="final_failed">Final Interview Failed</option>
            <option value="contract_offered">Job Offer Pending</option>
            <option value="contract_declined">Offer Declined</option>
            <option value="hired">Hired</option>
            <option value="withdrawn">Withdrawn</option>
        </select>
    </div>
    <div class="col-md-3">
        <select id="filterRole" class="form-select searchable-select" data-placeholder="Filter by role..." maxlength="40">
            <option value="all">All Roles</option>
            <option value="Employee">Cashier</option>
            <option value="HR Staff">HR Staff</option>
            <option value="Finance Staff">Finance Staff</option>
            <option value="Head HR">Head HR</option>
            <option value="Head Finance">Head Finance</option>
        </select>
    </div>
    <div class="col-md-4">
        <div class="autocomplete-wrapper">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email..." maxlength="100" autocomplete="off">
            <div class="autocomplete-dropdown" id="autocompleteDropdown"></div>
        </div>
    </div>
    <div class="col-md-2">
        <button class="btn btn-yellow-outline btn-sm w-100" id="refreshBtn">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- Active Filter Chips (Modrinth-style removable filter pills) -->
<div class="active-filter-chips" id="activeFilterChips"></div>

<!-- Stats Row -->
<div class="row g-2 mb-3" id="statsRow">
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Total</small>
            <h5 class="mb-0" id="statTotal">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Pending</small>
            <h5 class="mb-0 text-warning" id="statPending">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Scheduled</small>
            <h5 class="mb-0 text-info" id="statScheduled">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Passed</small>
            <h5 class="mb-0 text-success" id="statPassed">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Rejected</small>
            <h5 class="mb-0 text-danger" id="statRejected">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Hired</small>
            <h5 class="mb-0 text-primary" id="statHired">0</h5>
        </div>
    </div>
</div>

<!-- Applicants Table -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="applicantsTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading applicants...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="tableInfo">Loading...</span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                    <li class="page-item disabled"><span class="page-link">1</span></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Applicant Detail Modal -->
<div class="modal fade" id="applicantDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Applicant Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="applicantDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Trainer Selection Modal -->
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

<!-- Schedule Interview Modal -->
<div class="modal fade" id="scheduleInterviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Interview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleInterviewForm">
                <input type="hidden" name="interview_type" id="scheduleTypeHidden" value="initial">
                <div class="modal-body">
                    <input type="hidden" id="scheduleApplicantId" name="applicant_id">
                    <div class="mb-3">
                        <label class="form-label">Interview Type</label>
                        <p class="form-control-plaintext fw-semibold mb-0"><i class="bi bi-chat"></i> Initial Interview</p>
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

<!-- Autocomplete Styles -->
<style>
.autocomplete-wrapper {
    position: relative;
    width: 100%;
}

.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 0 0 8px 8px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.autocomplete-dropdown.show {
    display: block;
}

.autocomplete-dropdown .item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.autocomplete-dropdown .item:hover {
    background: var(--light-yellow-subtle);
}

.autocomplete-dropdown .item.selected {
    background: var(--light-yellow-subtle);
}

.autocomplete-dropdown .item .item-name {
    font-weight: 500;
}

.autocomplete-dropdown .item .item-email {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.autocomplete-dropdown .item .item-role {
    font-size: 0.7rem;
    color: var(--text-muted);
}

.autocomplete-dropdown .no-results {
    padding: 12px;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.85rem;
}

/* Date input error state */
input[type="datetime-local"].error {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* Interview history timeline (replaces the old table view) */
.interview-timeline {
    position: relative;
    background: var(--bg-card-subtle);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 6px 8px;
}

.interview-timeline .timeline-item {
    position: relative;
    display: flex;
    gap: 14px;
    padding: 10px 12px;
    border-radius: 10px;
    transition: background-color 0.15s ease;
}

.interview-timeline .timeline-item.is-current {
    background: var(--light-yellow-accent);
}

.interview-timeline .timeline-item.is-upcoming {
    opacity: 0.75;
}

.interview-timeline .timeline-dot-col {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.interview-timeline .timeline-dot {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
    z-index: 1;
}

.interview-timeline .timeline-dot.passed {
    background: var(--success);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(91, 170, 122, 0.18);
}

.interview-timeline .timeline-dot.failed {
    background: var(--danger);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(223, 91, 85, 0.18);
}

.interview-timeline .timeline-dot.pending {
    background: var(--brand-yellow);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(244, 91, 53, 0.18);
}

.interview-timeline .timeline-dot.upcoming {
    width: 12px;
    height: 12px;
    margin: 8px;
    background: var(--bg-card);
    border: 2px solid var(--border-color);
}

.interview-timeline .timeline-connector {
    width: 2px;
    flex: 1 1 auto;
    background: var(--border-color);
    margin: 2px 0;
}

.interview-timeline .timeline-connector.passed {
    background: var(--success);
    opacity: 0.4;
}

.interview-timeline .timeline-connector.failed {
    background: var(--danger);
    opacity: 0.4;
}

.interview-timeline .timeline-item:last-child .timeline-connector {
    display: none;
}

.interview-timeline .timeline-content {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
    padding-bottom: 4px;
}

.interview-timeline .timeline-title {
    font-weight: 600;
    color: var(--text-main);
}

.interview-timeline .timeline-item.is-current .timeline-title {
    color: var(--brand-yellow-hover);
}

.interview-timeline .timeline-item.is-upcoming .timeline-title {
    font-weight: 500;
    color: var(--text-muted);
}

.interview-timeline .timeline-sub {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 2px;
}

.interview-timeline .timeline-date {
    font-size: 0.8rem;
    color: var(--text-muted);
    white-space: nowrap;
    flex-shrink: 0;
}
</style>

<script src="/ShelfSense/public/assets/js/hr/applicants.js?v=20260830120721"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';