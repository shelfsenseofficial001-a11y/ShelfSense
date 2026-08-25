<?php
use App\Core\Auth;

$title = 'Job Postings - ShelfSense HR';
$pageTitle = 'Job Postings';
$activePage = 'job_postings';
$isHRHead = Auth::isHRHead() || Auth::isSuperAdmin();
$isHRHeadJs = $isHRHead ? 'true' : 'false';

$content = <<<EOT
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="filterStatus" class="form-select searchable-select" data-placeholder="Filter by status...">
            <option value="all">All Status</option>
            <option value="draft">Draft</option>
            <option value="pending_approval">Pending Approval</option>
            <option value="approved">Approved (Active)</option>
            <option value="rejected">Rejected</option>
            <option value="closed">Closed (Not Hiring)</option>
            <option value="archived">Archived</option>
        </select>
    </div>
    <div class="col-md-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by title, department, role..." maxlength="100">
    </div>
    <div class="col-md-2">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="mineOnly">
            <label class="form-check-label small" for="mineOnly">My postings only</label>
        </div>
    </div>
    <div class="col-md-3 text-end">
        <button class="btn btn-yellow-outline btn-sm" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        <button class="btn btn-yellow-primary btn-sm" id="createBtn"><i class="bi bi-plus-circle"></i> New Job Posting</button>
    </div>
</div>

<div class="row g-2 mb-3" id="statsRow">
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Draft</small><h5 class="mb-0" id="statDraft">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Pending</small><h5 class="mb-0 text-warning" id="statPending">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Approved</small><h5 class="mb-0 text-success" id="statApproved">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Rejected</small><h5 class="mb-0 text-danger" id="statRejected">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Closed</small><h5 class="mb-0" id="statClosed">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center"><small class="text-muted">Archived</small><h5 class="mb-0 text-muted" id="statArchived">0</h5></div></div>
</div>

<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Closing Date</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="postingsTableBody">
                    <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="tableInfo">Loading...</span>
            <nav><ul class="pagination pagination-sm mb-0" id="paginationContainer"></ul></nav>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="postingFormModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postingFormTitle">New Job Posting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="postingForm">
                <input type="hidden" id="postingId">
                <div class="modal-body">
                    <div id="postingFormAlert"></div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold">Job Title</label>
                            <input type="text" id="postingTitle" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" id="postingDepartment" class="form-control" maxlength="50" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label fw-semibold">Role Key</label>
                            <input type="text" id="postingRole" class="form-control" maxlength="50" required placeholder="e.g. cashier">
                            <div class="form-text">Used internally; matches the applicant's target role.</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea id="postingDescription" class="form-control" rows="4" maxlength="5000" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Requirements / Qualifications / Responsibilities</label>
                        <textarea id="postingRequirements" class="form-control" rows="4" maxlength="5000"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-semibold">Min Salary</label>
                            <input type="number" id="postingSalaryMin" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-semibold">Max Salary</label>
                            <input type="number" id="postingSalaryMax" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label fw-semibold">Closing Date</label>
                            <input type="date" id="postingOpenUntil" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-outline-secondary btn-sm" id="saveDraftBtn">Save as Draft</button>
                    <button type="button" class="btn btn-yellow-primary btn-sm" id="saveAndSubmitBtn">Save &amp; Submit for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Detail / Review Modal -->
<div class="modal fade" id="postingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Job Posting Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="postingDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer" id="postingDetailFooter"></div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectPostingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Job Posting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Reason for Rejection (Required)</label>
                <textarea id="rejectPostingReason" class="form-control" rows="3" maxlength="500" required></textarea>
                <div class="invalid-feedback" id="rejectPostingReasonError">A rejection reason is required.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmRejectPostingBtn">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<script>const HR_IS_HEAD = {$isHRHeadJs};</script>
<script src="/ShelfSense/public/assets/js/hr/job_postings.js"></script>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';
