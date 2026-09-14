<?php
use App\Core\Auth;
use App\Models\JobPosting;

$postingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$posting = null;

if ($postingId > 0) {
    $posting = (new JobPosting())->getById($postingId);
    if (!$posting) {
        http_response_code(404);
        die('Job posting not found.');
    }
    $isOwner = (int)$posting['created_by'] === (int)Auth::userId();
    $isHead = Auth::isHRHead() || Auth::isSuperAdmin();
    // Mirrors update_job_posting.php's own edit-ability rule exactly, so a
    // direct link never lands on a page that then fails to save: HR Head
    // may open anything that isn't archived; HR Staff only their own
    // draft/rejected postings.
    $canEdit = $isHead || ($isOwner && in_array($posting['status'], ['draft', 'rejected'], true));
    if ($posting['status'] === 'archived' || !$canEdit) {
        http_response_code(403);
        die('You do not have permission to edit this job posting.');
    }
}

$title = ($postingId ? 'Edit' : 'New') . ' Job Posting - ShelfSense HR';
$pageTitle = 'Job Postings';
$activePage = 'job_postings';
$isHRHead = Auth::isHRHead() || Auth::isSuperAdmin();
$isHRHeadJs = $isHRHead ? 'true' : 'false';
$postingJson = $posting ? json_encode($posting, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : 'null';

$content = '<script>window.__POSTING__ = ' . $postingJson . ';</script>' . <<<EOT
<div class="jp-page-header">
    <div class="jp-page-header-top">
        <div class="jp-page-title-row">
            <a href="?page=hr_job_postings" class="jp-back-link" title="Back to Job Postings"><i class="bi bi-arrow-left"></i></a>
            <h4 class="mb-0" id="jpFormPageTitle"><i class="bi bi-megaphone"></i> New Job Posting</h4>
        </div>
        <div class="jp-page-header-actions">
            <a href="?page=hr_job_postings" class="btn btn-secondary" id="cancelFormBtn">Cancel</a>
            <button type="submit" form="postingForm" class="btn btn-outline-secondary" id="saveDraftBtn">Save as Draft</button>
            <button type="button" class="btn btn-yellow-primary" id="saveAndSubmitBtn">Save &amp; Submit for Approval</button>
        </div>
    </div>
    <p class="text-muted small mb-0">Fill in the details below to open a new position for applicants.</p>
</div>

<div class="jp-checklist" id="jpChecklist">
    <div class="jp-checklist-header">
        <h6 class="mb-0"><i class="bi bi-list-check"></i> Publishing Checklist</h6>
        <div class="jp-checklist-legend">
            <span><i class="bi bi-asterisk text-danger"></i> Required</span>
            <span><i class="bi bi-exclamation-triangle-fill text-warning"></i> Warning</span>
            <span><i class="bi bi-lightbulb-fill text-info"></i> Suggestion</span>
        </div>
        <button type="button" class="jp-checklist-collapse-btn" id="jpChecklistCollapseBtn" title="Collapse"><i class="bi bi-chevron-up"></i></button>
    </div>
    <div class="jp-checklist-cards-wrap" id="jpChecklistCardsWrap">
        <div class="jp-checklist-cards" id="jpChecklistCards"></div>
        <div class="jp-checklist-fade" id="jpChecklistFade"></div>
    </div>
    <div class="jp-checklist-clear" id="jpChecklistClear">
        <i class="bi bi-check-circle-fill"></i> Everything looks good — this posting is ready to submit for approval.
    </div>
</div>

<form id="postingForm">
    <input type="hidden" id="postingId" value="{$postingId}">
    <div class="jp-split">
        <div class="jp-split-form">

            <div class="jp-form-section">
                <h6 class="jp-section-title"><i class="bi bi-briefcase"></i> Position Details</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                        <input type="text" id="postingTitle" class="form-control" maxlength="100" placeholder="e.g. Front Desk Cashier">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select id="postingDepartmentGroup" class="form-select searchable-select" data-placeholder="Select department...">
                            <option value=""></option>
                            <option value="Front Department">Front Department</option>
                            <option value="Human Resources Department">Human Resources Department</option>
                            <option value="Finance Department">Finance Department</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Position <span class="text-danger">*</span></label>
                        <select id="postingDepartment" class="form-select searchable-select" data-placeholder="Select department first..." disabled>
                            <option value=""></option>
                        </select>
                        <div class="form-text">Options depend on the selected department.</div>
                    </div>
                </div>
            </div>

            <div class="jp-form-section">
                <h6 class="jp-section-title"><i class="bi bi-card-text"></i> Job Details</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
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
                        <textarea id="postingDescription" class="form-control jp-md-textarea" rows="6" maxlength="5000"></textarea>
                    </div>
                    <div class="form-text">This editor supports <a href="https://www.markdownguide.org/basic-syntax/" target="_blank" rel="noopener noreferrer">Markdown formatting</a> and keyboard shortcuts. See the live preview on the right.</div>
                </div>
            </div>

            <div class="jp-form-section">
                <h6 class="jp-section-title"><i class="bi bi-geo-alt"></i> Posting Details</h6>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                        <input type="text" id="postingLocation" class="form-control" maxlength="150" placeholder="e.g. Main Store, Dasmarinas">
                        <div class="form-text" id="postingLocationHint"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Open Slots</label>
                        <input type="number" id="postingSlots" class="form-control" min="1" max="299" step="1" placeholder="Unlimited">
                        <div class="form-text">1&ndash;299, or blank for unlimited.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Qualifications <span class="jp-field-tag" id="postingRequirementsTag">Suggested</span></label>
                        <textarea id="postingRequirements" class="form-control" rows="3" maxlength="5000" placeholder="One qualification per line"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Min Salary <span class="jp-field-tag">Suggested</span></label>
                        <input type="number" id="postingSalaryMin" class="form-control" step="0.01" min="0" placeholder="e.g. 15000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Max Salary <span class="jp-field-tag">Suggested</span></label>
                        <input type="number" id="postingSalaryMax" class="form-control" step="0.01" min="0" placeholder="e.g. 20000">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Key Responsibilities &amp; Duties <span class="jp-field-tag">Suggested</span></label>
                        <textarea id="postingResponsibilities" class="form-control" rows="3" maxlength="5000" placeholder="One responsibility per line"></textarea>
                    </div>
                </div>
            </div>

            <div class="jp-form-section jp-form-section-last">
                <h6 class="jp-section-title"><i class="bi bi-calendar-event"></i> Timeline</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Closing Date <span class="text-danger">*</span></label>
                        <input type="date" id="postingOpenUntil" class="form-control"
                               min="<?php echo date('Y-m-d'); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+6 months')); ?>"
                               oninput="validatePostingDate(this)"
                               onblur="validatePostingDate(this)">
                        <div class="form-text">Must be within the next 6 months.</div>
                    </div>
                </div>
            </div>

EOT;

if ($postingId && in_array($posting['status'], ['draft', 'rejected'], true)) {
    $content .= <<<EOT
            <div class="jp-danger-zone">
                <h6 class="jp-danger-title"><i class="bi bi-exclamation-octagon-fill"></i> Danger Zone</h6>
                <div class="jp-danger-row">
                    <div>
                        <strong>Delete this job posting</strong>
                        <p class="text-muted small mb-0">Permanently deletes this {$posting['status']} posting. This cannot be undone.</p>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="deletePostingBtn"><i class="bi bi-trash"></i> Delete Posting</button>
                </div>
            </div>

EOT;
} elseif ($postingId) {
    $content .= <<<EOT
            <div class="jp-danger-zone">
                <h6 class="jp-danger-title"><i class="bi bi-exclamation-octagon-fill"></i> Danger Zone</h6>
                <div class="jp-danger-row">
                    <div>
                        <strong>Delete this job posting</strong>
                        <p class="text-muted small mb-0">Only a draft or rejected posting can be permanently deleted. This one has been submitted for review, so archive it from the Job Postings list instead once it's no longer active.</p>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-trash"></i> Delete Posting</button>
                </div>
            </div>

EOT;
}

$content .= <<<EOT
        </div>
        <div class="jp-split-preview">
            <div class="jp-preview-heading"><i class="bi bi-eye"></i> Live Preview</div>
            <div id="postingFullPreview" class="jp-full-preview"></div>
        </div>
    </div>
</form>

<!-- Leave Confirmation Modal (Discard vs Save as Draft) -->
<div class="modal fade" id="jpLeaveConfirmModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-circle"></i> Unsaved Changes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">You have unsaved changes to this job posting. Do you want to save it as a draft before leaving, or discard your changes?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-sm" id="jpLeaveDiscardBtn"><i class="bi bi-trash"></i> Discard</button>
                <button type="button" class="btn btn-yellow-primary btn-sm" id="jpLeaveSaveDraftBtn"><i class="bi bi-save"></i> Save as Draft</button>
            </div>
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

<script>const HR_IS_HEAD = {$isHRHeadJs};</script>
<script src="/ShelfSense/public/assets/js/components/searchable-select.js?v=20260908530000"></script>
<script src="/ShelfSense/public/assets/js/shared/markdown.js?v=20260908440000"></script>
<script src="/ShelfSense/public/assets/js/hr/job_posting_form.js?v=20260914330000"></script>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';
