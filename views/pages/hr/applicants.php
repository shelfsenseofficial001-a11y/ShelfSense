<?php
$title = 'Applicants - ShelfSense HR';
$pageTitle = 'Applicants';
$activePage = 'applicants';

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

<!-- Applicant Detail Drawer -->
<div class="offcanvas offcanvas-end detail-drawer applicant-drawer" id="applicantDetailModal" tabindex="-1">
    <div class="offcanvas-header" id="applicantDetailHeader">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="applicantDetailBody">
        <div class="applicant-skeleton">
            <div class="skeleton-row">
                <div class="skeleton-avatar"></div>
                <div class="flex-grow-1">
                    <div class="skeleton-line" style="width:60%;height:18px;"></div>
                    <div class="skeleton-line" style="width:40%;margin-top:8px;"></div>
                </div>
            </div>
            <div class="skeleton-line" style="width:100%;height:60px;margin-top:20px;"></div>
            <div class="skeleton-line" style="width:100%;height:60px;margin-top:10px;"></div>
            <div class="skeleton-line" style="width:100%;height:100px;margin-top:20px;"></div>
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

/* Generic drawer shell (width, close button, transition-under-reduced-
   motion fix) now lives in the shared .detail-drawer class in
   dashboard-theme.css. Only applicant-specific content styling stays
   here. */

.applicant-drawer-hero {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}
.applicant-drawer-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.3rem;
    background: var(--light-yellow-accent);
    color: var(--brand-yellow-hover);
}
.applicant-drawer-name {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}
.applicant-drawer-role {
    color: var(--text-muted);
    font-size: 0.88rem;
    margin-top: 2px;
}
.applicant-drawer-status {
    margin-top: 8px;
}

.applicant-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin: 20px 0 8px;
}
.applicant-section-title:first-of-type {
    margin-top: 0;
}

.applicant-info-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}
.applicant-info-row:last-child {
    border-bottom: none;
}
.applicant-info-row .icon-box-sm {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: var(--bg-card-subtle);
    color: var(--brand-yellow-hover);
}
.applicant-info-row .info-label {
    font-size: 0.75rem;
    color: var(--text-muted);
}
.applicant-info-row .info-value {
    font-weight: 500;
    word-break: break-word;
}

.applicant-rejection-box {
    margin-top: 16px;
    padding: 12px 14px;
    border-radius: 10px;
    background: #fecaca;
    color: #991b1b;
    font-size: 0.85rem;
}
[data-bs-theme="dark"] .applicant-rejection-box {
    background: #7f1d1d;
    color: #fca5a5;
}

.applicant-drawer-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 20px;
    margin-bottom: 20px;
}

.applicant-inline-schedule {
    background: var(--bg-card-subtle);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 14px 16px;
    margin-top: 20px;
    margin-bottom: 20px;
}
.applicant-inline-schedule .applicant-section-title {
    margin-top: 0;
}

/* Skeleton loading state, shown while the drawer data is in flight */
.applicant-skeleton .skeleton-row {
    display: flex;
    align-items: center;
    gap: 14px;
}
.skeleton-avatar,
.skeleton-line {
    border-radius: 8px;
    background: linear-gradient(90deg, var(--bg-card-subtle) 25%, var(--border-color) 50%, var(--bg-card-subtle) 75%);
    background-size: 400px 100%;
    animation: applicant-skeleton-shimmer 1.4s ease-in-out infinite;
}
.skeleton-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    flex-shrink: 0;
}
.skeleton-line {
    height: 12px;
}
@keyframes applicant-skeleton-shimmer {
    0% { background-position: -400px 0; }
    100% { background-position: 400px 0; }
}
</style>

<script src="/ShelfSense/public/assets/js/hr/applicants.js?v=20260831061347"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';