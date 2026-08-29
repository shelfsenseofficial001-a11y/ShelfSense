<?php
$title = 'Contracts - ShelfSense HR';
$pageTitle = 'Contracts';
$activePage = 'contracts';

// Build trainee options for the dropdown
$traineeOptions = '<option value="">Select trainee...</option>';
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $traineeQuery = "SELECT a.id, a.first_name, a.last_name, a.target_role FROM applicants a WHERE a.status = 'screening_success' ORDER BY a.first_name";
    $traineeStmt = $db->query($traineeQuery);
    while ($trainee = $traineeStmt->fetch()) {
        $traineeOptions .= '<option value="' . $trainee['id'] . '">' . $trainee['first_name'] . ' ' . $trainee['last_name'] . ' (' . $trainee['target_role'] . ')</option>';
    }
} catch (Exception $e) {
    $traineeOptions .= '<option value="" disabled>Error loading trainees</option>';
}

$content = '
<style>
    /* Rest days toggle indicator */
    .rest-day-toggle.btn-primary,
    .edit-rest-day-toggle.btn-primary {
        background-color: var(--brand-yellow) !important;
        color: var(--brand-yellow-btn-text) !important;
        border-color: var(--brand-yellow) !important;
        font-weight: 600;
    }
    .rest-day-toggle.btn-primary::after,
    .edit-rest-day-toggle.btn-primary::after {
        content: " ✓";
        font-weight: 700;
    }
    .rest-day-toggle.btn-outline-secondary,
    .edit-rest-day-toggle.btn-outline-secondary {
        background-color: transparent !important;
        color: var(--text-muted) !important;
        border-color: var(--border-color) !important;
    }
</style>

<!-- Filters -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select searchable-select" data-placeholder="Filter by status..." maxlength="40">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="accepted">Accepted</option>
            <option value="declined">Declined</option>
        </select>
    </div>
    <div class="col-md-4">
        <div class="autocomplete-wrapper">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email..." maxlength="100" autocomplete="off">
            <div class="autocomplete-dropdown" id="autocompleteDropdown"></div>
        </div>
    </div>
    <div class="col-md-2">
        <button class="btn btn-yellow-outline btn-sm w-100" id="refreshBtn">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
    <div class="col-md-3 text-end">
        <button class="btn btn-yellow-primary btn-sm" id="createContractBtn">
            <i class="bi bi-plus-circle"></i> Offer Contract
        </button>
    </div>
</div>

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
            <small class="text-muted">Accepted</small>
            <h5 class="mb-0 text-success" id="statAccepted">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Declined</small>
            <h5 class="mb-0 text-danger" id="statDeclined">0</h5>
        </div>
    </div>
</div>

<!-- Contracts Table -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Role</th>
                        <th>Shift</th>
                        <th>Salary</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="contractsTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading contracts...</p>
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

<!-- ============================================ -->
<!-- CREATE CONTRACT MODAL                       -->
<!-- ============================================ -->
<div class="modal fade" id="createContractModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Offer Contract</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createContractForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Trainee</label>
                        <select name="applicant_id" id="contractApplicant" class="form-select" required>
                            ' . $traineeOptions . '
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shift</label>
                        <select name="shift" id="contractShift" class="form-select" required>
                            <option value="">Select shift...</option>
                            <option value="opening">Opening (6am-2pm)</option>
                            <option value="closing">Closing (2pm-10pm)</option>
                            <option value="midshift">MidShift (10am-6pm)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Salary (₱)</label>
                        <input type="number" name="salary" id="contractSalary" class="form-control" step="0.01" min="0" placeholder="e.g., 15000.00" required>
                        <div class="form-text" id="salaryHint">Enter a valid salary for the selected role.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="contractStartDate" class="form-control" required min="<?= date("Y-m-d") ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Job Details (optional)</label>
                        <textarea name="job_details" id="contractJobDetails" class="form-control" rows="3" placeholder="Additional job details..."></textarea>
                    </div>
                    <!-- Rest Days Toggle (Create) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rest Days (Select 2)</label>
                        <div class="d-flex flex-wrap gap-2" id="restDaysContainer">
                            <button type="button" class="btn btn-outline-secondary btn-sm rest-day-toggle" data-day="monday">Mon</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rest-day-toggle" data-day="tuesday">Tue</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rest-day-toggle" data-day="wednesday">Wed</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rest-day-toggle" data-day="thursday">Thu</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rest-day-toggle" data-day="friday">Fri</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rest-day-toggle" data-day="saturday">Sat</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rest-day-toggle" data-day="sunday">Sun</button>
                        </div>
                        <input type="hidden" name="rest_days" id="contractRestDays" value="">
                        <small class="text-muted">Select up to 2 rest days for this employee.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Decision Deadline</label>
                        <select name="decision_deadline" id="contractDeadline" class="form-select" required>
                            <option value="3">3 days</option>
                            <option value="4">4 days</option>
                            <option value="5" selected>5 days</option>
                            <option value="6">6 days</option>
                            <option value="7">7 days</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Offer Contract</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- EDIT CONTRACT MODAL                          -->
<!-- ============================================ -->
<div class="modal fade" id="editContractModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Contract – <span id="editContractName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editContractForm">
                <div class="modal-body">
                    <input type="hidden" id="editContractId" name="contract_id">
                    <input type="hidden" id="editContractRole" name="role">
                    
                    <div class="mb-3">
                        <label class="form-label">Shift</label>
                        <select name="shift" id="editContractShift" class="form-select" required>
                            <option value="">Select shift...</option>
                            <option value="opening">Opening (6am-2pm)</option>
                            <option value="closing">Closing (2pm-10pm)</option>
                            <option value="midshift">MidShift (10am-6pm)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Salary (₱)</label>
                        <input type="number" name="salary" id="editContractSalary" class="form-control" step="0.01" min="0" required>
                        <div class="form-text" id="salaryRangeHint">Range: ₱0 – ₱0</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="editContractStartDate" class="form-control" required min="<?= date("Y-m-d") ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Job Details</label>
                        <textarea name="job_details" id="editContractJobDetails" class="form-control" rows="3" placeholder="Job details..."></textarea>
                    </div>

                    <!-- Rest Days Toggle (Edit) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rest Days (Select 2)</label>
                        <div class="d-flex flex-wrap gap-2" id="editRestDaysContainer">
                            <button type="button" class="btn btn-outline-secondary btn-sm edit-rest-day-toggle" data-day="monday">Mon</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm edit-rest-day-toggle" data-day="tuesday">Tue</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm edit-rest-day-toggle" data-day="wednesday">Wed</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm edit-rest-day-toggle" data-day="thursday">Thu</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm edit-rest-day-toggle" data-day="friday">Fri</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm edit-rest-day-toggle" data-day="saturday">Sat</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm edit-rest-day-toggle" data-day="sunday">Sun</button>
                        </div>
                        <input type="hidden" name="rest_days" id="editContractRestDays" value="">
                        <small class="text-muted">Select up to 2 rest days for this employee.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Decision Deadline</label>
                        <select name="decision_deadline" id="editContractDeadline" class="form-select" required>
                            <option value="3">3 days</option>
                            <option value="4">4 days</option>
                            <option value="5" selected>5 days</option>
                            <option value="6">6 days</option>
                            <option value="7">7 days</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTRACT DETAIL MODAL                        -->
<!-- ============================================ -->
<div class="modal fade" id="contractDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contract Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contractDetailBody">
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

<script src="/ShelfSense/public/assets/js/hr/contracts.js?v=20260829181202"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';