<?php
// views/pages/auth/apply.php
require_once __DIR__ . '/../../../app/core/Database.php';
require_once __DIR__ . '/../../../app/models/JobPosting.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';

use App\Models\JobPosting;

$title = 'Apply Now - ShelfSense';

// Real, approved, currently-hiring job postings with remaining slots only --
// the exact same eligibility rule the public Landing Page uses, so the two
// pages can never disagree about what's open.
$eligibleJobs = [];
try {
    $eligibleJobs = (new JobPosting())->getPublicListings();
} catch (Exception $e) {
    error_log('apply.php: failed to load eligible job postings: ' . $e->getMessage());
}

// A landing-page "Apply Now" card links here with the specific job's id.
$preselectId = isset($_GET['job_posting_id']) ? intval($_GET['job_posting_id']) : 0;

$positionOptionsHtml = '';
foreach ($eligibleJobs as $job) {
    $labelParts = [$job['title']];
    if (!empty($job['department'])) $labelParts[] = $job['department'];
    if (!empty($job['location'])) $labelParts[] = $job['location'];
    if (!empty($job['employment_type'])) $labelParts[] = $job['employment_type'];
    if ($job['remaining_slots'] !== null) {
        $labelParts[] = $job['remaining_slots'] . ' slot' . ($job['remaining_slots'] == 1 ? '' : 's') . ' left';
    }
    $label = htmlspecialchars(implode(' — ', [$labelParts[0]]) . (count($labelParts) > 1 ? ' (' . implode(' • ', array_slice($labelParts, 1)) . ')' : ''), ENT_QUOTES, 'UTF-8');
    $positionOptionsHtml .= '<option value="' . (int)$job['id'] . '">' . $label . '</option>';
}

// All job data the left panel/carousel need, for client-side rendering when
// the user switches jobs (no page reload). Content is passed as data, never
// as raw HTML -- the JS below escapes everything before inserting it.
$jobsJson = json_encode(array_map(function ($j) {
    return [
        'id' => (int)$j['id'],
        'title' => $j['title'],
        'department' => $j['department'],
        'location' => $j['location'],
        'employment_type' => $j['employment_type'],
        'description' => $j['description'],
        'requirements' => $j['requirements'],
        'responsibilities' => $j['responsibilities'],
        'salary_min' => $j['salary_range_min'],
        'salary_max' => $j['salary_range_max'],
        'slots' => $j['slots'],
        'remaining_slots' => $j['remaining_slots'],
    ];
}, $eligibleJobs), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$hasJobs = !empty($eligibleJobs);

$content = '
<style>
    /* Theme Variables for Seamless Light/Dark Compatibility */
    :root {
        --card-bg: #ffffff;
        --card-border: #e5e7eb;
        --text-main: #111827;
        --text-subtle: #6b7280;
        --input-bg: #f9fafb;
        --input-border: #d1d5db;
        --input-text: #111827;
        --brand-accent: #f45b35;
        --brand-accent-hover: #df4d29;

        /* Visual Panel Variables (Light Mode) */
        --panel-bg: linear-gradient(135deg, #fde8e2 0%, #fbcfc3 100%);
        --panel-title: #1f2937;
        --panel-text: #4b5563;
        --panel-feature-text: #374151;
        --panel-badge-bg: rgba(223, 77, 41, 0.15);
        --panel-badge-border: rgba(223, 77, 41, 0.3);
        --panel-badge-text: #b23d1f;
        --panel-footer-text: #6b7280;
        --panel-divider: rgba(107, 114, 128, 0.2);
        --panel-glow: rgba(244, 91, 53, 0.35);
        --panel-card-bg: rgba(255, 255, 255, 0.55);
        --panel-dot-inactive: rgba(107, 114, 128, 0.35);
    }

    [data-bs-theme="dark"] {
        --card-bg: #181611;
        --card-border: #383321;
        --text-main: #f9fafb;
        --text-subtle: #9ca3af;
        --input-bg: #1f1c13;
        --input-border: #383321;
        --input-text: #f4f4f5;

        /* Visual Panel Variables (Dark Mode) */
        --panel-bg: linear-gradient(135deg, #2a1f1a 0%, #1c1310 100%);
        --panel-title: #ffffff;
        --panel-text: #d1d5db;
        --panel-feature-text: #9ca3af;
        --panel-badge-bg: rgba(244, 91, 53, 0.15);
        --panel-badge-border: rgba(244, 91, 53, 0.3);
        --panel-badge-text: #ff9270;
        --panel-footer-text: #9ca3af;
        --panel-divider: rgba(255, 255, 255, 0.15);
        --panel-glow: rgba(244, 91, 53, 0.2);
        --panel-card-bg: rgba(0, 0, 0, 0.2);
        --panel-dot-inactive: rgba(255, 255, 255, 0.25);
    }

    /* Container Alignment */
    .auth-card {
        max-width: 1040px !important;
        width: 100% !important;
        margin: 0 auto;
        position: relative;
        overflow: visible !important;
    }

    /* Wrapper Card — overflow left visible (not hidden) so the carousel
       arrows can float half outside the visual panel; the panel itself
       clips its own background/glow to a matching rounded corner instead
       (see .left-visual-panel border-radius below). */
    .two-column-wrapper {
        display: flex;
        flex-direction: row;
        width: 100%;
        min-height: 560px;
        border-radius: 16px;
        position: relative;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    /* Visual panel wrapper — a plain, non-clipping positioning shell around
       .left-visual-panel so the arrow buttons can float half outside that
       panels own edges (left/right on desktop, still left/right on mobile
       since the panel is full-width there) without being cut off by
       .left-visual-panel own overflow:hidden (used for its glow effect)
       or .two-column-wrapper own overflow:hidden (used for its rounded corners). */
    .visual-panel-wrap {
        flex: 1;
        position: relative;
    }

    /* Carousel Arrows — float half outside the visual panel, themed for light/dark */
    .carousel-arrow-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 1px solid var(--card-border);
        background: var(--card-bg);
        color: var(--brand-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        cursor: pointer;
        z-index: 5;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
    }

    .carousel-arrow-btn:hover {
        background: var(--brand-accent);
        color: #ffffff;
        border-color: var(--brand-accent);
        transform: translateY(-50%) scale(1.06);
    }

    .carousel-arrow-btn:focus-visible {
        outline: 2px solid var(--brand-accent);
        outline-offset: 2px;
    }

    .carousel-arrow-prev {
        left: -22px;
    }

    .carousel-arrow-next {
        right: -20px;
    }

    @media (max-width: 768px) {
        .carousel-arrow-btn {
            width: 36px;
            height: 36px;
            font-size: 0.95rem;
        }
        .carousel-arrow-prev {
            left: -14px;
        }
        .carousel-arrow-next {
            right: -14px;
        }
    }

    /* Adaptive Visual Panel */
    .left-visual-panel {
        width: 100%;
        height: 100%;
        background: var(--panel-bg);
        padding: 32px 28px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
        border-radius: 16px 0 0 16px;
        transition: background 0.3s ease;
    }

    /* Decorative Glow Accent */
    .left-visual-panel::before {
        content: "";
        position: absolute;
        top: -20%;
        right: -20%;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, var(--panel-glow) 0%, rgba(0,0,0,0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .left-visual-panel .brand-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--panel-badge-bg);
        border: 1px solid var(--panel-badge-border);
        color: var(--panel-badge-text);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        width: fit-content;
        position: relative;
        z-index: 1;
    }

    .job-panel-scroll {
        position: relative;
        z-index: 1;
        max-height: 420px;
        overflow-y: auto;
        padding-right: 4px;
    }

    #jobDetailPanel {
        background: var(--panel-card-bg);
        border-radius: 12px;
        padding: 18px;
        margin-top: 16px;
    }

    #jobDetailPanel h2 {
        font-family: "Space Grotesk", sans-serif;
        color: var(--panel-title);
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 10px;
        line-height: 1.25;
    }

    .job-meta-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 14px;
    }

    .job-meta-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--panel-badge-bg);
        border: 1px solid var(--panel-badge-border);
        color: var(--panel-badge-text);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .job-detail-section {
        margin-bottom: 14px;
    }

    .job-detail-section:last-child {
        margin-bottom: 0;
    }

    .job-detail-section h6 {
        color: var(--panel-title);
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .job-detail-section p,
    .job-detail-section .job-detail-list {
        color: var(--panel-text);
        font-size: 0.85rem;
        margin: 0;
        line-height: 1.5;
    }

    .job-detail-list {
        list-style: none;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .job-detail-list li {
        color: var(--panel-feature-text);
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .job-detail-list li::before {
        content: "\2713";
        color: #df4d29;
        font-weight: 700;
        flex-shrink: 0;
    }

    [data-bs-theme="dark"] .job-detail-list li::before {
        color: #f45b35;
    }

    /* Carousel Dots */
    .carousel-dots {
        display: flex;
        gap: 8px;
        margin-top: 16px;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }

    .carousel-dot {
        border: none;
        background: var(--panel-dot-inactive);
        width: 10px;
        height: 10px;
        border-radius: 50%;
        padding: 0;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .carousel-dot:hover {
        background: var(--brand-accent-hover);
    }

    .carousel-dot:focus-visible {
        outline: 2px solid var(--brand-accent);
        outline-offset: 2px;
    }

    .carousel-dot[aria-current="true"] {
        background: var(--brand-accent);
        width: 26px;
        border-radius: 6px;
    }

    .left-visual-panel .panel-footer {
        margin-top: 20px;
        padding-top: 14px;
        border-top: 1px solid var(--panel-divider);
        position: relative;
        z-index: 1;
    }

    .left-visual-panel .panel-footer small {
        color: var(--panel-footer-text);
        display: block;
    }

    .empty-jobs-state {
        text-align: center;
        padding: 32px 12px;
        color: var(--panel-text);
        position: relative;
        z-index: 1;
    }

    .empty-jobs-state i {
        font-size: 2rem;
        display: block;
        margin-bottom: 10px;
        color: var(--panel-badge-text);
    }

    /* Right Form Panel */
    .right-form-panel {
        flex: 1.1;
        padding: 36px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .form-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: var(--text-main);
    }

    .form-header p {
        color: var(--text-subtle);
        font-size: 0.85rem;
        margin-bottom: 20px;
    }

    .form-label {
        color: var(--text-main);
        font-size: 0.85rem;
        margin-bottom: 4px;
    }

    /* Back Button — given a visible pill container so it does not get
       lost against the page background */
    .back-btn-link {
        color: var(--brand-accent) !important;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 999px;
        border: 1px solid var(--card-border);
        background: var(--input-bg);
        margin-bottom: 12px;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .back-btn-link:hover {
        background: var(--brand-accent);
        border-color: var(--brand-accent);
        color: #ffffff !important;
    }

    /* Dynamic Theme Form Controls */
    .form-control,
    .form-select {
        background-color: var(--input-bg) !important;
        border-color: var(--input-border) !important;
        color: var(--input-text) !important;
        font-size: 0.9rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--brand-accent) !important;
        box-shadow: 0 0 0 0.25rem rgba(244, 91, 53, 0.2) !important;
    }

    .form-control::placeholder {
        color: var(--text-subtle) !important;
        opacity: 0.7;
    }

    /* Custom File Input */
    .custom-file-input::file-selector-button {
        background-color: var(--input-border);
        color: var(--input-text);
        border: none;
        padding: 0.375rem 0.75rem;
        margin-right: 0.75rem;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .custom-file-input::file-selector-button:hover {
        background-color: var(--brand-accent);
        color: #ffffff;
    }

    /* Submit Button */
    .btn-yellow-primary {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        color: #000000 !important;
        font-weight: 600;
        padding: 10px;
        transition: background-color 0.2s;
    }

    .btn-yellow-primary:hover {
        background-color: var(--brand-accent-hover) !important;
        border-color: var(--brand-accent-hover) !important;
        color: #ffffff !important;
    }

    .btn-yellow-primary:disabled {
        opacity: 0.6;
    }

    @media (max-width: 768px) {
        .two-column-wrapper {
            flex-direction: column;
        }
        .left-visual-panel {
            min-height: auto;
            padding: 24px;
            border-radius: 16px 16px 0 0;
        }
        .job-panel-scroll {
            max-height: 320px;
        }
        .right-form-panel {
            padding: 24px;
        }
    }
</style>

<div class="two-column-wrapper">
    <!-- Left Column: Dynamic Job Details Panel -->
    <div class="visual-panel-wrap">
        <div class="left-visual-panel">
            <div class="job-panel-scroll">
                <div class="brand-badge">
                    <i class="bi bi-box-seam-fill"></i> ShelfSense Careers
                </div>

                <div id="jobDetailPanel" style="display: ' . ($hasJobs ? 'block' : 'none') . ';"></div>

                <div id="noJobsMessage" class="empty-jobs-state" style="display: ' . ($hasJobs ? 'none' : 'block') . ';">
                    <i class="bi bi-inbox"></i>
                    No positions are currently open for applications. Please check back later.
                </div>
            </div>

            <div>
                <nav class="carousel-dots" id="carouselDots" aria-label="Select a job posting"></nav>
                <div class="panel-footer">
                    <small>ShelfSense Portal &mdash; Smart inventory control at your fingertips.</small>
                    <small>&copy; ShelfSense Portal. All rights reserved.</small>
                </div>
            </div>
        </div>
        ' . (count($eligibleJobs) > 1 ? '
        <button type="button" class="carousel-arrow-btn carousel-arrow-prev" id="carouselPrevBtn" aria-label="Previous position">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button type="button" class="carousel-arrow-btn carousel-arrow-next" id="carouselNextBtn" aria-label="Next position">
            <i class="bi bi-chevron-right"></i>
        </button>
        ' : '') . '
    </div>

    <!-- Right Column: Application Form -->
    <div class="right-form-panel">
        <div>
            <a href="?page=home" class="back-btn-link text-decoration-none">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>

        <div class="form-header">
            <h3>Start Your Career</h3>
            <p>Fill in your details below to submit an application.</p>
        </div>

        ' . ($hasJobs ? '
        <form method="POST" action="?page=api_apply" enctype="multipart/form-data" id="applyForm">
            <input type="hidden" name="job_posting_id" id="jobPostingIdInput" value="">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="applyFirstName">First Name *</label>
                    <input type="text" id="applyFirstName" name="first_name" class="form-control" required maxlength="50">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="applyLastName">Last Name *</label>
                    <input type="text" id="applyLastName" name="last_name" class="form-control" required maxlength="50">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold" for="applyMiddleName">Middle Name</label>
                    <input type="text" id="applyMiddleName" name="middle_name" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="applyEmail">Email *</label>
                    <input type="email" id="applyEmail" name="email" class="form-control" required maxlength="100">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="applyPhone">Phone Number *</label>
                    <input type="text" id="applyPhone" name="phone" class="form-control" placeholder="09123456789" required maxlength="12" pattern="[0-9]{10,12}">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold" for="positionSelect">Position Applying For *</label>
                    <select id="positionSelect" class="form-select searchable-select" data-placeholder="Search for a position..." required>
                        <option value=""></option>
                        ' . $positionOptionsHtml . '
                    </select>
                    <div class="form-text">Type to search. Selecting a position updates the details panel.</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold" for="applyResume">Upload Resume *</label>
                    <input type="file"
                        id="applyResume"
                        name="resume"
                        class="form-control custom-file-input"
                        accept=".pdf,.doc,.docx"
                        required>
                    <small class="text-muted d-block mt-1">PDF, DOC, DOCX files only. Max 5MB.</small>
                </div>
            </div>
            <button type="submit" id="applySubmitBtn" class="btn btn-yellow-primary w-100 mt-3 rounded-3">
                <i class="bi bi-send me-2"></i>Submit Application
            </button>
        </form>
        ' : '
        <div class="alert alert-warning small mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            There are no open positions to apply for right now. Please check back later.
        </div>
        ') . '
    </div>
</div>

<script src="/ShelfSense/public/assets/js/components/searchable-select.js"></script>
<script>
(function () {
    var APPLY_JOBS = ' . $jobsJson . ';
    var PRESELECT_ID = ' . (int)$preselectId . ';

    if (!APPLY_JOBS.length) {
        return; // Empty state already rendered server-side; nothing to wire up.
    }

    var jobsById = {};
    APPLY_JOBS.forEach(function (j) { jobsById[j.id] = j; });

    function escapeHtml(str) {
        var div = document.createElement("div");
        div.textContent = str == null ? "" : String(str);
        return div.innerHTML;
    }

    // Mirrors the server-side rendering rule for requirements/responsibilities:
    // multiple non-empty lines become a bullet list, a single line/paragraph
    // stays as escaped text with line breaks preserved. Never inserts raw HTML.
    function renderListOrParagraph(text, fallback) {
        text = (text || "").trim();
        if (!text) {
            return \'<p class="text-muted mb-0 small fst-italic">\' + escapeHtml(fallback) + "</p>";
        }
        var lines = text.split(/\\r\\n|\\r|\\n/).map(function (l) { return l.trim(); }).filter(Boolean);
        if (!lines.length) {
            return \'<p class="text-muted mb-0 small fst-italic">\' + escapeHtml(fallback) + "</p>";
        }
        if (lines.length > 1) {
            var items = lines.map(function (l) {
                return "<li>" + escapeHtml(l.replace(/^[-\\u2022*]\\s+/, "")) + "</li>";
            }).join("");
            return \'<ul class="job-detail-list mb-0">\' + items + "</ul>";
        }
        return \'<p class="mb-0">\' + escapeHtml(lines[0]).replace(/\\n/g, "<br>") + "</p>";
    }

    function formatMoney(n) {
        return "₱" + Number(n).toLocaleString("en-US", { maximumFractionDigits: 0 });
    }

    function renderPanel(job) {
        var badges = [];
        if (job.department) badges.push(\'<span class="job-meta-badge"><i class="bi bi-building"></i> \' + escapeHtml(job.department) + "</span>");
        if (job.location) badges.push(\'<span class="job-meta-badge"><i class="bi bi-geo-alt"></i> \' + escapeHtml(job.location) + "</span>");
        if (job.employment_type) badges.push(\'<span class="job-meta-badge"><i class="bi bi-briefcase"></i> \' + escapeHtml(job.employment_type) + "</span>");
        if (job.remaining_slots !== null && job.remaining_slots !== undefined) {
            badges.push(\'<span class="job-meta-badge"><i class="bi bi-people"></i> \' + job.remaining_slots + " slot" + (job.remaining_slots == 1 ? "" : "s") + " left</span>");
        }

        var salaryHtml = "";
        if (job.salary_min || job.salary_max) {
            salaryHtml = \'<div class="job-detail-section"><h6><i class="bi bi-cash-stack"></i> Salary Range</h6><p>\'
                + formatMoney(job.salary_min || 0) + " - " + formatMoney(job.salary_max || 0) + " per month</p></div>";
        }

        var responsibilitiesHtml = "";
        if ((job.responsibilities || "").trim()) {
            responsibilitiesHtml = \'<div class="job-detail-section"><h6><i class="bi bi-list-check"></i> Responsibilities</h6>\'
                + renderListOrParagraph(job.responsibilities, "Responsibilities not provided.") + "</div>";
        }

        document.getElementById("jobDetailPanel").innerHTML =
            "<h2>" + escapeHtml(job.title) + "</h2>" +
            \'<div class="job-meta-badges">\' + badges.join("") + "</div>" +
            \'<div class="job-detail-section"><h6><i class="bi bi-file-text"></i> Job Description</h6>\'
                + renderListOrParagraph(job.description, "Description not provided.") + "</div>" +
            \'<div class="job-detail-section"><h6><i class="bi bi-check2-square"></i> Requirements</h6>\'
                + renderListOrParagraph(job.requirements, "Requirements not provided.") + "</div>" +
            responsibilitiesHtml +
            salaryHtml;
    }

    function renderDots(activeId) {
        var nav = document.getElementById("carouselDots");
        nav.innerHTML = "";
        APPLY_JOBS.forEach(function (job, idx) {
            var dot = document.createElement("button");
            dot.type = "button";
            dot.className = "carousel-dot";
            dot.setAttribute("aria-label", "View " + job.title + " position details");
            dot.setAttribute("aria-current", job.id === activeId ? "true" : "false");
            dot.dataset.jobId = job.id;
            dot.addEventListener("click", function () { selectJob(job.id, true); markUserChoice(); });
            nav.appendChild(dot);
        });
    }

    // Reflects a job selection in the panel, dots, and hidden form field.
    // Does NOT touch the select element value -- callers that need the
    // select synced (the carousel) do that separately to avoid a feedback loop
    // with the selects own change listener below.
    function applySelection(jobId) {
        jobId = parseInt(jobId, 10);
        var job = jobsById[jobId];
        if (!job) return;
        currentJobId = jobId;
        renderPanel(job);
        document.getElementById("jobPostingIdInput").value = jobId;
        Array.prototype.forEach.call(document.querySelectorAll(".carousel-dot"), function (dot) {
            dot.setAttribute("aria-current", parseInt(dot.dataset.jobId, 10) === jobId ? "true" : "false");
        });
    }

    // Keeps the panel, dots, and hidden field from showing a stale job after
    // the searchable-selects own "clear" (X) button empties the select --
    // that button is a generic filter-clear affordance from the shared
    // component, not aware this field requires exactly one selection.
    function clearSelectionState() {
        document.getElementById("jobDetailPanel").innerHTML =
            \'<p class="text-muted mb-0 small fst-italic">Select a position above to see its details.</p>\';
        document.getElementById("jobPostingIdInput").value = "";
        Array.prototype.forEach.call(document.querySelectorAll(".carousel-dot"), function (dot) {
            dot.setAttribute("aria-current", "false");
        });
    }

    function selectJob(jobId, fromDot) {
        jobId = parseInt(jobId, 10);
        if (!jobsById[jobId]) return;
        if (fromDot) {
            var select = document.getElementById("positionSelect");
            select.value = jobId;
            if (window.refreshSearchableSelect) window.refreshSearchableSelect(select);
        }
        applySelection(jobId);
    }

    // ------------------------------------------------------------------
    // Carousel: arrows + auto-swipe
    // ------------------------------------------------------------------
    var currentJobId = null;
    var autoSwipeTimer = null;
    var AUTO_SWIPE_MS = 6000;
    // Once the user makes a deliberate choice (dropdown, arrow, or dot),
    // auto-swipe stops for good -- it should never carry the user away from
    // the position they picked.
    var userHasChosen = false;

    function goToRelative(offset) {
        var idx = APPLY_JOBS.findIndex(function (j) { return j.id === currentJobId; });
        if (idx === -1) idx = 0;
        var nextIdx = (idx + offset + APPLY_JOBS.length) % APPLY_JOBS.length;
        selectJob(APPLY_JOBS[nextIdx].id, true);
    }

    function startAutoSwipe() {
        if (APPLY_JOBS.length < 2 || userHasChosen) return;
        stopAutoSwipe();
        autoSwipeTimer = setInterval(function () { goToRelative(1); }, AUTO_SWIPE_MS);
    }

    function stopAutoSwipe() {
        if (autoSwipeTimer) {
            clearInterval(autoSwipeTimer);
            autoSwipeTimer = null;
        }
    }

    function markUserChoice() {
        userHasChosen = true;
        stopAutoSwipe();
    }

    document.addEventListener("DOMContentLoaded", function () {
        renderDots(null);

        var select = document.getElementById("positionSelect");
        select.addEventListener("change", function () {
            if (this.value) {
                applySelection(this.value);
                markUserChoice();
            } else {
                clearSelectionState();
            }
        });

        var prevBtn = document.getElementById("carouselPrevBtn");
        var nextBtn = document.getElementById("carouselNextBtn");
        if (prevBtn) prevBtn.addEventListener("click", function () { goToRelative(-1); markUserChoice(); });
        if (nextBtn) nextBtn.addEventListener("click", function () { goToRelative(1); markUserChoice(); });

        var visualPanel = document.querySelector(".left-visual-panel");
        if (visualPanel) {
            visualPanel.addEventListener("mouseenter", stopAutoSwipe);
            visualPanel.addEventListener("mouseleave", startAutoSwipe);
        }
        document.addEventListener("visibilitychange", function () {
            if (document.hidden) stopAutoSwipe(); else startAutoSwipe();
        });

        var initialId = (PRESELECT_ID && jobsById[PRESELECT_ID]) ? PRESELECT_ID : APPLY_JOBS[0].id;
        if (PRESELECT_ID && jobsById[PRESELECT_ID]) userHasChosen = true;
        select.value = initialId;
        if (window.refreshSearchableSelect) window.refreshSearchableSelect(select);
        applySelection(initialId);
        startAutoSwipe();
    });

    document.getElementById("applyForm").addEventListener("submit", async function (e) {
        e.preventDefault();

        var submitBtn = document.getElementById("applySubmitBtn");
        var originalText = submitBtn.innerHTML;

        var jobId = document.getElementById("jobPostingIdInput").value;
        if (!jobId) {
            Swal.fire({ icon: "warning", title: "Select a position", text: "Please choose a position to apply for." });
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = \'<span class="spinner-border spinner-border-sm me-2"></span>Submitting...\';

        try {
            var formData = new FormData(this);
            var response = await fetch(this.action, { method: "POST", body: formData });
            var result = await response.json();

            if (result.success) {
                var job = jobsById[parseInt(jobId, 10)];
                Swal.fire({
                    icon: "success",
                    title: "Application Submitted!",
                    html: "<p>Thank you, <strong>" + escapeHtml(formData.get("first_name")) + "</strong>!</p>" +
                        "<p>Our HR team will review your application for the <strong>" + escapeHtml(job ? job.title : "selected") + "</strong> position.</p>" +
                        \'<p class="text-muted mt-2">You will receive a confirmation email shortly.</p>\',
                    confirmButtonText: "OK"
                }).then(function () {
                    window.location.href = "?page=home";
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Submission Failed",
                    text: result.message || "Please check your inputs and try again.",
                    confirmButtonText: "OK"
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error("Submission error:", error);
            Swal.fire({
                icon: "error",
                title: "Connection Error",
                text: "Something went wrong. Please check your internet connection and try again.",
                confirmButtonText: "OK"
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
})();
</script>
';

require_once __DIR__ . '/../../layouts/auth.php';
