<?php
use App\Core\Auth;
use App\Models\JobPosting;

// job_posting_approvals.php sets $approvalsMode = true and requires this
// same file, rather than duplicating the list/detail-drawer markup and JS
// for what is otherwise the identical page just pre-filtered to pending
// postings and stripped of the authoring-only controls.
$approvalsMode = $approvalsMode ?? false;
$canAuthor = Auth::isHRStaff() || Auth::isSuperAdmin();

$title = ($approvalsMode ? 'Approvals' : 'Job Postings') . ' - ShelfSense HR';
$pageTitle = $approvalsMode ? 'Approvals' : 'Job Postings';
$activePage = $approvalsMode ? 'job_posting_approvals' : 'job_postings';
$isHRHead = Auth::isHRHead() || Auth::isSuperAdmin();
$isHRHeadJs = $isHRHead ? 'true' : 'false';
$approvalsModeJs = $approvalsMode ? 'true' : 'false';

$jpModel = new JobPosting();
$initialFilters = $approvalsMode ? ['status' => 'pending_approval', 'search' => ''] : ['status' => 'all', 'search' => ''];
$jpInitial = $jpModel->getAll(1, 10, $initialFilters);
foreach ($jpInitial['postings'] as &$jpInitialPosting) {
    $jpInitialPosting['can_edit'] = jobPostingCanEdit($jpInitialPosting);
}
unset($jpInitialPosting);
$initialData = [
    'postings' => $jpInitial['postings'],
    'pagination' => $jpInitial['pagination'],
    'counts' => $jpModel->getStatusCounts()
];
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$headerActions = $approvalsMode
    ? '<button class="btn btn-yellow-outline" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>'
    : '<button class="btn btn-yellow-outline" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        <button class="btn btn-yellow-outline" id="viewArchivedBtn"><i class="bi bi-archive"></i> Archived</button>'
        . ($canAuthor ? '<button class="btn btn-yellow-outline" id="myDraftsBtn"><i class="bi bi-file-earmark-text"></i> My Drafts</button>
            <a href="?page=hr_job_posting_form" class="btn btn-yellow-primary"><i class="bi bi-plus-circle"></i> New Job Posting</a>' : '');

$headerTitle = $approvalsMode ? 'Approvals' : 'Recruitment Dashboard';
$headerIcon = $approvalsMode ? 'bi-patch-check' : 'bi-megaphone';
$headerSubtitle = $approvalsMode
    ? 'Review job postings HR Staff submitted for approval -- approve or reject with an optional moderation message.'
    : 'Manage every job posting from draft to hired -- create, review, and track recruitment activity here.';

// Approvals is always a pending-only queue -- the status filter and the
// global (all-status) stat tiles don't apply, so they're left out of the
// markup entirely rather than shown disabled/stale.
$statusFilterHtml = $approvalsMode ? '' : <<<HTML
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
HTML;
$searchColClass = $approvalsMode ? 'col-md-12' : 'col-md-8';

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>' . <<<EOT
<div class="jp-page-header">
    <div class="jp-page-header-top">
        <div>
            <h2 class="mb-0"><i class="bi {$headerIcon}"></i> {$headerTitle}</h2>
            <p class="text-muted small mb-0">{$headerSubtitle}</p>
        </div>
        <div class="jp-page-header-actions">
            {$headerActions}
        </div>
    </div>
</div>

<div class="row g-2 mb-3">
    {$statusFilterHtml}
    <div class="{$searchColClass}">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by title, department, role..." maxlength="100">
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

EOT;
if (!$approvalsMode) {
    $content .= <<<EOT
<div class="row g-2 mb-3" id="statsRow">
    <div class="col"><div class="modern-card p-2 text-center jp-stat-clickable" data-status="draft" title="View draft postings"><small class="text-muted">Draft</small><h5 class="mb-0" id="statDraft">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center jp-stat-clickable" data-status="pending_approval" title="View pending postings"><small class="text-muted">Pending</small><h5 class="mb-0 text-warning" id="statPending">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center jp-stat-clickable" data-status="approved" title="View approved postings"><small class="text-muted">Approved</small><h5 class="mb-0 text-success" id="statApproved">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center jp-stat-clickable" data-status="rejected" title="View rejected postings"><small class="text-muted">Rejected</small><h5 class="mb-0 text-danger" id="statRejected">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center jp-stat-clickable" data-status="closed" title="View closed postings"><small class="text-muted">Closed</small><h5 class="mb-0" id="statClosed">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center jp-stat-clickable" data-status="archived" title="View archived postings"><small class="text-muted">Archived</small><h5 class="mb-0 text-muted" id="statArchived">0</h5></div></div>
</div>
EOT;
}
$content .= <<<EOT

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
<div class="offcanvas offcanvas-end detail-drawer" id="postingDetailModal" tabindex="-1" style="--bs-offcanvas-width: 640px;">
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
                <label class="form-label fw-semibold">Moderation Message (Required)</label>
                <div class="jp-md-toolbar" data-target="rejectPostingReason">
                    <button type="button" class="jp-md-btn" data-md="bold" title="Bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="jp-md-btn" data-md="italic" title="Italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="jp-md-btn" data-md="list" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
                </div>
                <textarea id="rejectPostingReason" class="form-control" rows="3" maxlength="500" required></textarea>
                <div class="form-text">Tell the HR Staff who submitted this what to fix. Supports Markdown.</div>
                <div class="invalid-feedback" id="rejectPostingReasonError">A moderation message is required when rejecting.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmRejectPostingBtn">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approvePostingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Job Posting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Moderation Message (Optional)</label>
                <div class="jp-md-toolbar" data-target="approvePostingMessage">
                    <button type="button" class="jp-md-btn" data-md="bold" title="Bold"><i class="bi bi-type-bold"></i></button>
                    <button type="button" class="jp-md-btn" data-md="italic" title="Italic"><i class="bi bi-type-italic"></i></button>
                    <button type="button" class="jp-md-btn" data-md="list" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
                </div>
                <textarea id="approvePostingMessage" class="form-control" rows="3" maxlength="500"></textarea>
                <div class="form-text">Anything the HR Staff who submitted this should know. Supports Markdown.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="confirmApprovePostingBtn">Confirm Approval</button>
            </div>
        </div>
    </div>
</div>

<script>const HR_IS_HEAD = {$isHRHeadJs}; const JP_APPROVALS_MODE = {$approvalsModeJs};</script>
<script src="/ShelfSense/public/assets/js/shared/markdown.js?v=20260908440000"></script>
<script src="/ShelfSense/public/assets/js/hr/job_postings.js?v=20260923160000"></script>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';
