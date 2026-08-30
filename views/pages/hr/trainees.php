<?php
use App\Core\Auth;

$title = 'Trainees - ShelfSense HR';
$pageTitle = 'Trainees';
$activePage = 'trainees';
$isHRHeadJs = (Auth::isHRHead() || Auth::isSuperAdmin()) ? 'true' : 'false';

// Date restrictions
$minDate = date('Y-m-d\TH:i', strtotime('+1 day'));
$maxDate = date('Y-m-d\TH:i', strtotime('+3 months'));

// Fetch trainers for the dropdown (only available ones)
$db = \App\Core\Database::getInstance()->getConnection();
$trainers = $db->query("SELECT user_id, first_name, last_name, role FROM users WHERE is_active = 1 AND can_train = 1 AND role != 'trainee' ORDER BY first_name");
$trainerOptions = '';
while ($trainer = $trainers->fetch()) {
    $trainerOptions .= '<option value="' . $trainer['user_id'] . '">' . $trainer['first_name'] . ' ' . $trainer['last_name'] . ' (' . $trainer['role'] . ')</option>';
}

$content = '
<!-- Filters -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select searchable-select" data-placeholder="Filter by status..." maxlength="40">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="terminated">Terminated</option>
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
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email..." maxlength="100" autocomplete="off">
            <div class="autocomplete-dropdown" id="autocompleteDropdown"></div>
        </div>
    </div>
    <div class="col-md-2">
        <button class="btn btn-yellow-outline btn-sm w-100" id="refreshBtn">
            <i class="bi bi-arrow-clockwise"></i> Refresh
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
            <small class="text-muted">Active</small>
            <h5 class="mb-0 text-warning" id="statActive">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Completed</small>
            <h5 class="mb-0 text-success" id="statCompleted">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Terminated</small>
            <h5 class="mb-0 text-danger" id="statTerminated">0</h5>
        </div>
    </div>
</div>

<!-- Trainees Table -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Role</th>
                        <th>Employee #</th>
                        <th>Trainer</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="traineesTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading trainees...</p>
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

<!-- Assign Trainer Modal -->
<div class="modal fade" id="assignTrainerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Trainer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignTrainerForm">
                <div class="modal-body">
                    <input type="hidden" id="assignTraineeId" name="trainee_id">
                    <div class="mb-3">
                        <label class="form-label">Trainee</label>
                        <p class="fw-semibold" id="assignTraineeName">-</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Trainer</label>
                        <select name="trainer_id" id="assignTrainerSelect" class="form-select" required>
                            <option value="">Select a trainer...</option>
                            ' . $trainerOptions . '
                        </select>
                        <div class="form-text">Only trainers with <strong>"Available to Train"</strong> status are shown.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Assign Trainer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Trainee Detail Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="traineeDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="traineeDetailBody">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>

<!-- Reports Modal -->
<div class="modal fade" id="reportsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trainee Reports – <span id="reportsTraineeName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportsBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading reports...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Contract Interview Modal -->
<div class="modal fade" id="scheduleContractInterviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Contract Interview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleContractInterviewForm">
                <input type="hidden" name="interview_type" value="contract">
                <div class="modal-body">
                    <input type="hidden" id="scheduleContractApplicantId" name="applicant_id">
                    <div class="mb-3">
                        <label class="form-label">Trainee</label>
                        <p class="fw-semibold" id="scheduleContractTraineeName">-</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" 
                               name="scheduled_date" 
                               id="scheduleContractDate" 
                               class="form-control" 
                               required
                               min="<?php echo $minDate; ?>"
                               max="<?php echo $maxDate; ?>"
                               oninput="validateDateInput(this)"
                               onblur="validateDateInput(this)">
                        <small class="text-muted">Min: Tomorrow, Max: 3 months from now</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gmeet Link (optional)</label>
                        <input type="url" name="gmeet_link" id="scheduleContractGmeet" class="form-control" placeholder="https://meet.google.com/xxx-xxxx-xxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message to Trainee</label>
                        <textarea name="message" id="scheduleContractMessage" class="form-control" rows="3" maxlength="250" placeholder="Please join the interview via the link above..."></textarea>
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

<script>
    window.CURRENT_USER_ID = ' . (int)($_SESSION['user_id'] ?? 0) . ';
    window.CURRENT_USER_ROLE = ' . json_encode($_SESSION['role'] ?? '') . ';
    window.HR_IS_HEAD = ' . $isHRHeadJs . ';
</script>
<script src="/ShelfSense/public/assets/js/hr/trainees.js?v=20260831061347"></script>
';

require_once __DIR__ . '/../../layouts/hr.php';