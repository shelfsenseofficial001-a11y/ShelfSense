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

<!-- Create/Edit Modal -->
<div class="modal fade" id="postingFormModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content jp-form-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="postingFormTitle">New Job Posting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="postingForm">
                <input type="hidden" id="postingId">
                <div class="modal-body p-0">
                    <div class="jp-split">
                    <div class="jp-split-form">
                    <div id="postingFormAlert"></div>

                    <div class="jp-form-section">
                        <h6 class="jp-section-title"><i class="bi bi-briefcase"></i> Position Details</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Job Title</label>
                                <input type="text" id="postingTitle" class="form-control" maxlength="100" required placeholder="e.g. Front Desk Cashier">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Department</label>
                                <select id="postingDepartmentGroup" class="form-select searchable-select" data-placeholder="Select department..." required>
                                    <option value=""></option>
                                    <option value="Front Department">Front Department</option>
                                    <option value="Human Resources Department">Human Resources Department</option>
                                    <option value="Finance Department">Finance Department</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Position</label>
                                <select id="postingDepartment" class="form-select searchable-select" data-placeholder="Select department first..." required disabled>
                                    <option value=""></option>
                                </select>
                                <div class="form-text">Options depend on the selected department.</div>
                            </div>
                        </div>
                    </div>

                    <div class="jp-form-section">
                        <h6 class="jp-section-title"><i class="bi bi-geo-alt"></i> Posting Details</h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Location</label>
                                <input type="text" id="postingLocation" class="form-control" maxlength="150" placeholder="e.g. Main Store, Dasmarinas">
                                <div class="form-text" id="postingLocationHint"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Open Slots</label>
                                <input type="number" id="postingSlots" class="form-control" min="1" max="299" step="1" placeholder="Unlimited">
                                <div class="form-text">1&ndash;299, or blank for unlimited.</div>
                            </div>
                        </div>
                    </div>

                    <div class="jp-form-section">
                        <h6 class="jp-section-title"><i class="bi bi-card-text"></i> Job Details</h6>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <div class="jp-md-editor">
                                <div class="jp-md-toolbar">
                                    <div class="jp-md-toolbar-group">
                                        <button type="button" class="jp-md-btn" data-md="h1" title="Heading 1 (Ctrl+Alt+1)">H1</button>
                                        <button type="button" class="jp-md-btn" data-md="h2" title="Heading 2 (Ctrl+Alt+2)">H2</button>
                                        <button type="button" class="jp-md-btn" data-md="h3" title="Heading 3 (Ctrl+Alt+3)">H3</button>
                                    </div>
                                    <div class="jp-md-toolbar-group">
                                        <button type="button" class="jp-md-btn" data-md="bold" title="Bold (Ctrl+B)"><i class="bi bi-type-bold"></i></button>
                                        <button type="button" class="jp-md-btn" data-md="italic" title="Italic (Ctrl+I)"><i class="bi bi-type-italic"></i></button>
                                        <button type="button" class="jp-md-btn" data-md="strike" title="Strikethrough (Ctrl+Shift+X)"><i class="bi bi-type-strikethrough"></i></button>
                                        <button type="button" class="jp-md-btn" data-md="code" title="Inline code (Ctrl+E)"><i class="bi bi-code-slash"></i></button>
                                    </div>
                                    <div class="jp-md-toolbar-group">
                                        <button type="button" class="jp-md-btn" data-md="ul" title="Bullet list (Ctrl+Shift+8)"><i class="bi bi-list-ul"></i></button>
                                        <button type="button" class="jp-md-btn" data-md="ol" title="Numbered list (Ctrl+Shift+7)"><i class="bi bi-list-ol"></i></button>
                                        <button type="button" class="jp-md-btn" data-md="link" title="Insert link (Ctrl+K)"><i class="bi bi-link-45deg"></i></button>
                                    </div>
                                    <div class="jp-md-toolbar-group">
                                        <button type="button" class="jp-md-btn" id="jpMdUndoBtn" title="Undo (Ctrl+Z)" disabled><i class="bi bi-arrow-counterclockwise"></i></button>
                                        <button type="button" class="jp-md-btn" id="jpMdRedoBtn" title="Redo (Ctrl+Y)" disabled><i class="bi bi-arrow-clockwise"></i></button>
                                    </div>
                                </div>
                                <textarea id="postingDescription" class="form-control jp-md-textarea" rows="6" maxlength="5000" required></textarea>
                            </div>
                            <div class="form-text">This editor supports <a href="https://www.markdownguide.org/basic-syntax/" target="_blank" rel="noopener noreferrer">Markdown formatting</a> and keyboard shortcuts. See the live preview on the right.</div>
                        </div>
                    </div>

                    <div class="jp-form-section jp-form-section-last">
                        <h6 class="jp-section-title"><i class="bi bi-calendar-event"></i> Timeline</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Closing Date</label>
                                <input type="date" id="postingOpenUntil" class="form-control" required
                                       min="<?php echo date('Y-m-d'); ?>"
                                       max="<?php echo date('Y-m-d', strtotime('+6 months')); ?>"
                                       oninput="validatePostingDate(this)"
                                       onblur="validatePostingDate(this)">
                                <div class="form-text">Must be within the next 6 months.</div>
                            </div>
                        </div>
                    </div>

                    </div>
                    <div class="jp-split-preview">
                        <div class="jp-preview-heading"><i class="bi bi-eye"></i> Live Preview</div>
                        <div id="postingFullPreview" class="jp-full-preview"></div>
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

<!-- Insert Link Modal (for the Description markdown editor) -->
<div class="modal fade" id="jpLinkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content jp-link-modal">
            <div class="modal-header">
                <h5 class="modal-title">Insert link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Label</label>
                    <div class="jp-link-input">
                        <i class="bi bi-text-left"></i>
                        <input type="text" id="jpLinkLabel" class="form-control" placeholder="Enter label...">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">URL <span class="text-danger">*</span></label>
                    <div class="jp-link-input">
                        <i class="bi bi-link-45deg"></i>
                        <input type="url" id="jpLinkUrl" class="form-control" placeholder="Enter the link's URL...">
                    </div>
                </div>
                <div>
                    <label class="form-label fw-semibold">Preview</label>
                    <div id="jpLinkPreview" class="jp-link-preview">
                        <span class="text-muted small fst-italic">Nothing to preview yet.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="jpLinkInsertBtn" disabled><i class="bi bi-plus-lg"></i> Insert</button>
            </div>
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
<script src="/ShelfSense/public/assets/js/shared/markdown.js?v=20260908440000"></script>
<script src="/ShelfSense/public/assets/js/hr/job_postings.js?v=20260908600001"></script>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';
