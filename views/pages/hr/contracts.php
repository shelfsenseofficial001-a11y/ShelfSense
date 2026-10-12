<?php
define('SHELFSENSE_INTERNAL_INCLUDE', true);
require_once __DIR__ . '/../../../app/handlers/hr/get_contracts.php';

$title = 'Contracts - ShelfSense HR';
$pageTitle = 'Contracts';
$activePage = 'contracts';

$db = \App\Core\Database::getInstance()->getConnection();
$initialData = hr_contracts_build_data($db, 1, 15, 'all', '', 'trainee');
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// Build trainee options for the Hiring Contract dropdown (passed Final Interview)
$traineeOptions = '<option value="">Select trainee...</option>';
try {
    $traineeQuery = "SELECT a.id, a.first_name, a.last_name, a.target_role FROM applicants a WHERE a.status = 'final_passed' ORDER BY a.first_name";
    $traineeStmt = $db->query($traineeQuery);
    while ($trainee = $traineeStmt->fetch()) {
        $traineeOptions .= '<option value="' . $trainee['id'] . '">' . $trainee['first_name'] . ' ' . $trainee['last_name'] . ' (' . $trainee['target_role'] . ')</option>';
    }
} catch (Exception $e) {
    $traineeOptions .= '<option value="" disabled>Error loading trainees</option>';
}


$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>
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

<!-- Contract Type Tabs -->
<ul class="nav nav-pills mb-3" id="contractTypeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="contractTypeTrainee" data-contract-type="trainee" type="button">
            <i class="bi bi-mortarboard"></i> Trainee Contract
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="contractTypeHired" data-contract-type="hired" type="button">
            <i class="bi bi-briefcase"></i> Hiring Contract
        </button>
    </li>
</ul>

<div class="interview-subnav-layout">
    <div class="interview-subnav-sidebar">
        <button class="interview-subnav-btn active filter-btn-contract" data-status="pending">
            <i class="bi bi-hourglass-split"></i> Pending
        </button>
        <button class="interview-subnav-btn filter-btn-contract" data-status="accepted">
            <i class="bi bi-check-circle"></i> Accepted
        </button>
        <button class="interview-subnav-btn filter-btn-contract" data-status="declined">
            <i class="bi bi-x-circle"></i> Rejected / Cancelled
        </button>
    </div>
    <div class="interview-subnav-content">
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="autocomplete-wrapper">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by name..." maxlength="100" autocomplete="off">
                    <div class="autocomplete-dropdown" id="autocompleteDropdown"></div>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-yellow-outline btn-sm w-100" id="refreshBtn">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-yellow-primary btn-sm" id="createContractBtn" style="display:none;">
                    <i class="bi bi-plus-circle"></i> Offer Contract
                </button>
            </div>
        </div>

        <div class="active-filter-chips" id="activeFilterChips"></div>

        <!-- Contracts Table -->
        <div class="modern-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Trainee</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="contractsTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-4">
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
                        <label class="form-label">Salary Range (₱)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="salary_min" id="contractSalaryMin" class="form-control" step="0.01" min="0" placeholder="Min" required>
                            </div>
                            <div class="col-6">
                                <input type="number" name="salary_max" id="contractSalaryMax" class="form-control" step="0.01" min="0" placeholder="Max" required>
                            </div>
                        </div>
                        <div class="form-text" id="salaryHint">Enter a valid salary range for the selected role. The midpoint is used as the base rate for payroll.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="contractStartDate" class="form-control" required min="<?= date("Y-m-d") ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HR Notes (optional)</label>
                        <textarea name="job_details" id="contractJobDetails" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
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
<!-- CREATE TRAINEE CONTRACT MODAL                -->
<!-- (separate from the Hiring Contract above --   -->
<!-- offered to an applicant who passed Initial    -->
<!-- Interview, before any trainer is assigned.)   -->
<!-- ============================================ -->
<div class="modal fade" id="createTraineeContractModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Offer Trainee Contract</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createTraineeContractForm">
                <div class="modal-body">
                    <p><strong>Applicant:</strong> <span id="traineeContractApplicantName"></span></p>
                    <input type="hidden" name="applicant_id" id="traineeContractApplicantId">
                    <div class="mb-3">
                        <label class="form-label">Salary Range (₱)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="salary_min" id="traineeContractSalaryMin" class="form-control" step="1" min="1" placeholder="Min" value="3900" required>
                            </div>
                            <div class="col-6">
                                <input type="number" name="salary_max" id="traineeContractSalaryMax" class="form-control" step="1" min="1" placeholder="Max" value="4500" required>
                            </div>
                        </div>
                        <div class="form-text">Indicative range only -- trainees are paid per hour. The exact rate is set later, after their account is created.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Working Hours (as discussed with the applicant)</label>
                        <input type="time" name="schedule_start" id="traineeContractScheduleStart" class="form-control" value="10:00">
                        <div class="form-text">Trainee shifts are fixed at exactly <strong>5 hours/day</strong> &mdash; end time is set automatically.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rest Days (Select up to 2)</label>
                        <div class="d-flex flex-wrap gap-2" id="traineeContractRestDaysContainer">
                            <button type="button" class="btn btn-outline-secondary btn-sm trainee-contract-rest-day-toggle" data-day="monday">Mon</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm trainee-contract-rest-day-toggle" data-day="tuesday">Tue</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm trainee-contract-rest-day-toggle" data-day="wednesday">Wed</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm trainee-contract-rest-day-toggle" data-day="thursday">Thu</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm trainee-contract-rest-day-toggle" data-day="friday">Fri</button>
                            <button type="button" class="btn btn-primary btn-sm trainee-contract-rest-day-toggle" data-day="saturday">Sat</button>
                            <button type="button" class="btn btn-primary btn-sm trainee-contract-rest-day-toggle" data-day="sunday">Sun</button>
                        </div>
                        <input type="hidden" name="rest_days" id="traineeContractRestDays" value="saturday,sunday">
                        <small class="text-muted">Select up to 2 rest days for this trainee.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HR Notes (optional)</label>
                        <textarea name="notes" id="traineeContractNotes" class="form-control" rows="3" maxlength="500" placeholder="Additional notes..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Decision Deadline</label>
                        <select name="decision_deadline_days" id="traineeContractDeadline" class="form-select" required>
                            <option value="3">3 days</option>
                            <option value="4">4 days</option>
                            <option value="5" selected>5 days</option>
                            <option value="6">6 days</option>
                            <option value="7">7 days</option>
                        </select>
                        <div class="form-text">If the applicant does not respond by then, this offer is automatically declined.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Offer Trainee Contract</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- CONTRACT DETAIL MODAL                        -->
<!-- ============================================ -->
<div class="offcanvas offcanvas-end detail-drawer" id="contractDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="contractDetailBody">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>

<script src="/ShelfSense/public/assets/js/hr/contracts.js?v=20261013000001"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';