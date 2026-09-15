<?php
use App\Core\Auth;
use App\Models\JobPosting;

$title = 'Job Postings - ShelfSense HR';
$pageTitle = 'Job Postings';
$activePage = 'job_postings';
$isHRHead = Auth::isHRHead() || Auth::isSuperAdmin();
$isHRHeadJs = $isHRHead ? 'true' : 'false';

$jpModel = new JobPosting();
$jpInitial = $jpModel->getAll(1, 10, ['status' => 'all', 'search' => '']);
$initialData = [
    'postings' => $jpInitial['postings'],
    'pagination' => $jpInitial['pagination'],
    'counts' => $jpModel->getStatusCounts()
];
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>' . <<<EOT
<div class="jp-page-header">
    <div class="jp-page-header-top">
        <div>
            <h4 class="mb-0"><i class="bi bi-megaphone"></i> Recruitment Dashboard</h4>
            <p class="text-muted small mb-0">Manage every job posting from draft to hired -- create, review, and track recruitment activity here.</p>
        </div>
        <div class="jp-page-header-actions">
            <button class="btn btn-yellow-outline" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            <button class="btn btn-yellow-outline" id="myDraftsBtn"><i class="bi bi-file-earmark-text"></i> My Drafts</button>
            <a href="?page=hr_job_posting_form" class="btn btn-yellow-primary"><i class="bi bi-plus-circle"></i> New Job Posting</a>
        </div>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4">
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
    <div class="col-md-6">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by title, department, role..." maxlength="100">
    </div>
    <div class="col-md-2">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="mineOnly">
            <label class="form-check-label small" for="mineOnly">My postings only</label>
        </div>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

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

<!-- Detail / Review Modal -->
<div class="offcanvas offcanvas-end detail-drawer" id="postingDetailModal" tabindex="-1">
    <div class="offcanvas-header">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="postingDetailBody">
        <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
    </div>
    <div class="p-3 border-top" id="postingDetailFooter"></div>
</div>

<!-- My Drafts Modal -->
<div class="modal fade" id="myDraftsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> My Drafts</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="jp-drafts-select-btn" id="jpDraftsSelectBtn" title="Select multiple"><i class="bi bi-check2-square"></i></button>
                    <a href="?page=hr_job_posting_form" class="jp-drafts-new-btn" title="New Job Posting"><i class="bi bi-plus-lg"></i></a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" id="myDraftsBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer jp-drafts-bulk-footer" id="jpDraftsBulkFooter" style="display:none;">
                <span class="jp-drafts-bulk-count" id="jpDraftsBulkCount">0 selected</span>
                <button type="button" class="btn btn-secondary btn-sm" id="jpDraftsCancelSelectBtn">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="jpDraftsDeleteSelectedBtn" disabled><i class="bi bi-trash"></i> Delete Selected</button>
            </div>
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
<script src="/ShelfSense/public/assets/js/hr/job_postings.js?v=20260914240000"></script>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';
