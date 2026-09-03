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
    $label = htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8');
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
        'role' => $j['role'],
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

// The full set of position-specific skills questionnaires, for the client
// to pick from once a position is selected (see jobPostingToQuestionnaireKey()
// in app/helpers/functions.php for the same mapping, mirrored in JS below).
$questionnairesJson = json_encode(SKILL_QUESTIONNAIRES, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

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

    /* Locked once the position is confirmed (past step 1) -- these
       controls live outside the step system, so without this they would
       stay clickable (and able to silently swap the selected job) on
       every later step. */
    .carousel-arrow-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }

    .carousel-dots.carousel-dots-locked {
        opacity: 0.4;
        pointer-events: none;
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

    /* Native scrollbar styling (::-webkit-scrollbar-*, scrollbar-color)
       turned out to render inconsistently across browsers -- some kept
       showing their own dark up/down arrow buttons no matter what was
       overridden. Instead, the native scrollbar is hidden completely and
       .job-scroll-indicator (a plain absolutely-positioned bar, updated by
       JS on scroll) draws an identical-everywhere thumb on top of it.

       The scrolling itself also moved from the *outer* wrapper down to
       #jobDetailPanelInner, a plain unstyled div nested INSIDE the rounded
       #jobDetailPanel card. Scrolling the outer wrapper meant the card own
       white background scrolled along with the text, so wherever the
       viewport clipped mid-content the cut looked flat/square instead of
       showing the card actual rounded edge. Now the card itself never
       scrolls or resizes -- only the plain div inside its padding does --
       so all four corners stay rounded no matter the scroll position. */
    .job-panel-scroll-wrap {
        position: relative;
    }

    .job-panel-scroll {
        position: relative;
        z-index: 1;
    }

    .job-scroll-indicator {
        position: absolute;
        top: 6px;
        bottom: 6px;
        right: 4px;
        width: 14px;
        z-index: 2;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .job-scroll-indicator.show {
        opacity: 1;
    }

    .job-scroll-thumb {
        position: absolute;
        left: 4px;
        width: 6px;
        border-radius: 999px;
        background: var(--brand-accent);
        cursor: grab;
        transition: background-color 0.15s ease;
    }

    .job-scroll-thumb:hover,
    .job-scroll-indicator.dragging .job-scroll-thumb {
        background: var(--brand-accent-hover);
    }

    .job-scroll-indicator.dragging .job-scroll-thumb {
        cursor: grabbing;
    }

    #jobDetailPanel {
        background: var(--panel-card-bg);
        border-radius: 12px;
        margin-top: 16px;
        max-height: 380px;
        overflow: hidden;
        position: relative;
    }

    #jobDetailPanelInner {
        max-height: 380px;
        overflow-y: auto;
        padding: 18px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    #jobDetailPanelInner::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
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

    .required-asterisk {
        color: #dc3545;
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

    /* Resume upload -- modern drag-and-drop dropzone. The real <input
       type="file"> stays part of the layout (not display:none) so its
       native `required` validation still works and reportValidity() can
       still focus/show its bubble -- it is just made invisible and
       stretched to cover the whole box, so a click anywhere opens the
       file picker while the dropzone below renders the visible UI. */
    .resume-dropzone {
        position: relative;
        border: 2px dashed var(--input-border);
        border-radius: 12px;
        background: var(--input-bg);
        padding: 26px 16px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .resume-dropzone:hover,
    .resume-dropzone.dragover {
        border-color: var(--brand-accent);
        background: rgba(244, 91, 53, 0.06);
    }

    .resume-dropzone input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 1;
    }

    .resume-dropzone-empty i {
        font-size: 1.8rem;
        color: var(--brand-accent);
        display: block;
        margin-bottom: 6px;
    }

    .resume-dropzone-text {
        font-size: 0.9rem;
        color: var(--text-main);
    }

    .resume-dropzone-text strong {
        color: var(--brand-accent);
    }

    .resume-dropzone-hint {
        font-size: 0.76rem;
        color: var(--text-subtle);
        margin-top: 4px;
    }

    .resume-dropzone-file {
        display: flex;
        align-items: center;
        gap: 12px;
        text-align: left;
    }

    .resume-dropzone-file i.bi-file-earmark-check-fill {
        font-size: 1.6rem;
        color: var(--brand-accent);
        flex-shrink: 0;
    }

    .resume-dropzone-file-info {
        flex: 1;
        min-width: 0;
    }

    .resume-dropzone-file-name {
        font-weight: 600;
        color: var(--text-main);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .resume-dropzone-file-size {
        font-size: 0.76rem;
        color: var(--text-subtle);
    }

    .resume-dropzone-remove {
        position: relative;
        z-index: 2;
        background: none;
        border: none;
        color: var(--text-subtle);
        cursor: pointer;
        padding: 6px;
        border-radius: 50%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .resume-dropzone-remove:hover {
        color: #dc3545;
        background: rgba(220, 53, 69, 0.12);
    }

    /* Submit Button */
    .btn-yellow-primary {
        background-color: var(--brand-accent) !important;
        border-color: var(--brand-accent) !important;
        color: #000000 !important;
        font-weight: 600;
        padding: 10px;
        border-radius: 10px;
        box-shadow: 0 4px 14px rgba(244, 91, 53, 0.35);
        transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .btn-yellow-primary:hover {
        background-color: var(--brand-accent-hover) !important;
        border-color: var(--brand-accent-hover) !important;
        color: #ffffff !important;
        box-shadow: 0 6px 18px rgba(244, 91, 53, 0.45);
        transform: translateY(-1px);
    }

    .btn-yellow-primary:active {
        transform: translateY(0);
        box-shadow: 0 3px 10px rgba(244, 91, 53, 0.35);
    }

    .btn-yellow-primary:disabled {
        opacity: 0.6;
        box-shadow: none;
        transform: none;
    }

    /* Step navigation buttons (Next/Back) -- rounded and themed to match
       the modernized resume dropzone, instead of the plain gray Bootstrap
       outline-secondary default. */
    .step-actions .btn {
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    .step-back-btn {
        background: var(--input-bg) !important;
        border: 1px solid var(--input-border) !important;
        color: var(--text-main) !important;
    }

    .step-back-btn:hover {
        background: var(--bg-card) !important;
        border-color: var(--brand-accent) !important;
        color: var(--brand-accent) !important;
        transform: translateY(-1px);
    }

    .step-back-btn:active {
        transform: translateY(0);
    }

    /* Application step timeline (Personal Info -> Home Address -> Position & Resume) */
    .apply-timeline {
        display: flex;
        align-items: flex-start;
        margin-bottom: 22px;
    }

    .apply-timeline-step {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        text-align: center;
    }

    .apply-timeline-step:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 18px;
        left: calc(50% + 22px);
        width: calc(100% - 44px);
        height: 2px;
        background: var(--card-border);
        z-index: 1;
        transition: background-color 0.25s ease;
    }

    .apply-timeline-step.completed:not(:last-child)::after {
        background: var(--brand-accent);
    }

    .apply-timeline-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid var(--card-border);
        background: var(--input-bg);
        color: var(--text-subtle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        position: relative;
        z-index: 2;
        padding: 0;
        cursor: default;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }

    .apply-timeline-step.completed .apply-timeline-circle {
        background: var(--brand-accent);
        border-color: var(--brand-accent);
        color: #ffffff;
        cursor: pointer;
    }

    .apply-timeline-step.active .apply-timeline-circle {
        border-color: var(--brand-accent);
        color: var(--brand-accent);
        background: var(--input-bg);
        box-shadow: 0 0 0 4px rgba(244, 91, 53, 0.18);
    }

    .apply-timeline-label {
        margin-top: 8px;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-subtle);
        line-height: 1.25;
        padding: 0 4px;
    }

    .apply-timeline-step.active .apply-timeline-label,
    .apply-timeline-step.completed .apply-timeline-label {
        color: var(--text-main);
    }

    /* Multi-step form panels: only the current step is shown, so a form
       with many fields (name, address, position + resume) never has to
       cram everything into one tall, overflowing panel. */
    .form-step {
        display: none;
    }

    .form-step.active {
        display: block;
        animation: applyStepFade 0.25s ease;
    }

    @keyframes applyStepFade {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .step-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 18px;
    }

    /* Skills self-assessment (Likert scale) */
    .skills-table-wrap {
        max-height: 340px;
        overflow-y: auto;
        overflow-x: hidden;
        border: 1px solid var(--card-border);
        border-radius: 10px;
    }

    .skills-likert-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .skills-likert-table thead th {
        position: sticky;
        top: 0;
        background: var(--input-bg);
        color: var(--text-subtle);
        font-weight: 600;
        text-align: center;
        padding: 10px 4px;
        border-bottom: 1px solid var(--card-border);
        white-space: normal;
        overflow-wrap: break-word;
        overflow: hidden;
        z-index: 1;
    }

    .skills-likert-table thead th small {
        display: block;
        font-weight: 400;
        font-size: 0.65rem;
        color: var(--text-subtle);
        opacity: 0.85;
    }

    .skills-likert-table thead th.skill-col-head {
        text-align: left;
        width: 40%;
    }

    .skills-likert-table tbody td {
        padding: 10px 6px;
        text-align: center;
        border-bottom: 1px solid var(--card-border);
        vertical-align: middle;
    }

    .skills-likert-table tbody tr:last-child td {
        border-bottom: none;
    }

    .skills-likert-table tbody tr.skill-row-missing td {
        background: rgba(244, 91, 53, 0.08);
    }

    .skills-likert-table .skill-col {
        text-align: left;
        color: var(--text-main);
        font-weight: 500;
        word-wrap: break-word;
    }

    .skills-likert-table input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: var(--brand-accent);
        cursor: pointer;
        margin: 0;
    }

    .skills-empty-state {
        padding: 20px 14px;
        text-align: center;
        color: var(--text-subtle);
        font-size: 0.85rem;
        border: 1px dashed var(--card-border);
        border-radius: 10px;
    }

    @media (max-width: 768px) {
        .apply-timeline-circle {
            width: 30px;
            height: 30px;
            font-size: 0.8rem;
        }
        .apply-timeline-step:not(:last-child)::after {
            top: 15px;
        }
        .apply-timeline-label {
            font-size: 0.62rem;
        }
        .step-actions .btn {
            padding-left: 14px;
            padding-right: 14px;
        }
        .skills-table-wrap {
            max-height: 300px;
        }
        .skills-likert-table {
            font-size: 0.76rem;
        }
        .skills-likert-table thead th.skill-col-head {
            width: 34%;
        }
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
        #jobDetailPanel,
        #jobDetailPanelInner {
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
            <div class="job-panel-scroll-wrap">
                <div class="job-panel-scroll">
                    <div class="brand-badge">
                        <i class="bi bi-box-seam-fill"></i> ShelfSense Careers
                    </div>

                    <div id="jobDetailPanel" style="display: ' . ($hasJobs ? 'block' : 'none') . ';">
                        <div id="jobDetailPanelInner"></div>
                        <div class="job-scroll-indicator" id="jobScrollIndicator" aria-hidden="true">
                            <div class="job-scroll-thumb" id="jobScrollThumb"></div>
                        </div>
                    </div>

                    <div id="noJobsMessage" class="empty-jobs-state" style="display: ' . ($hasJobs ? 'none' : 'block') . ';">
                        <i class="bi bi-inbox"></i>
                        No positions are currently open for applications. Please check back later.
                    </div>
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
            <p>Complete the steps below to submit your application.</p>
        </div>

        ' . ($hasJobs ? '
        <form method="POST" action="?page=api_apply" enctype="multipart/form-data" id="applyForm">
            <input type="hidden" name="job_posting_id" id="jobPostingIdInput" value="">

            <nav class="apply-timeline" id="applyTimeline" aria-label="Application progress">
                <div class="apply-timeline-step active" data-step-nav="1">
                    <button type="button" class="apply-timeline-circle" aria-label="Go to Position &amp; Resume step"><i class="bi bi-file-earmark-text-fill"></i></button>
                    <span class="apply-timeline-label">Position &amp; Resume</span>
                </div>
                <div class="apply-timeline-step" data-step-nav="2">
                    <button type="button" class="apply-timeline-circle" aria-label="Go to Personal Info step"><i class="bi bi-person-fill"></i></button>
                    <span class="apply-timeline-label">Personal Info</span>
                </div>
                <div class="apply-timeline-step" data-step-nav="3">
                    <button type="button" class="apply-timeline-circle" aria-label="Go to Home Address step"><i class="bi bi-geo-alt-fill"></i></button>
                    <span class="apply-timeline-label">Home Address</span>
                </div>
                <div class="apply-timeline-step" data-step-nav="4">
                    <button type="button" class="apply-timeline-circle" aria-label="Go to Skills Assessment step"><i class="bi bi-bar-chart-steps"></i></button>
                    <span class="apply-timeline-label">Skills Assessment</span>
                </div>
            </nav>

            <div class="form-step active" data-step="1">
                <div class="row g-2">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold" for="positionSelect">Position Applying For <span class="required-asterisk">*</span></label>
                        <select id="positionSelect" class="form-select searchable-select" data-placeholder="Search for a position..." required>
                            <option value=""></option>
                            ' . $positionOptionsHtml . '
                        </select>
                        <div class="form-text">Type to search. Selecting a position updates the details panel. This locks in once you continue.</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold" for="applyResume">Upload Resume <span class="required-asterisk">*</span></label>
                        <div class="resume-dropzone" id="resumeDropzone">
                            <input type="file" id="applyResume" name="resume" accept=".pdf,.doc,.docx" required>
                            <div class="resume-dropzone-empty" id="resumeDropzoneEmpty">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <div class="resume-dropzone-text"><strong>Click to upload</strong> or drag and drop</div>
                                <div class="resume-dropzone-hint">PDF, DOC, DOCX &middot; Max 5MB</div>
                            </div>
                            <div class="resume-dropzone-file" id="resumeDropzoneFile" style="display:none;">
                                <i class="bi bi-file-earmark-check-fill"></i>
                                <div class="resume-dropzone-file-info">
                                    <div class="resume-dropzone-file-name" id="resumeFileName"></div>
                                    <div class="resume-dropzone-file-size" id="resumeFileSize"></div>
                                </div>
                                <button type="button" class="resume-dropzone-remove" id="resumeRemoveBtn" aria-label="Remove file">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <span></span>
                    <button type="button" class="btn btn-yellow-primary step-next-btn" data-goto="2">Next <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </div>

            <div class="form-step" data-step="2">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyFirstName">First Name <span class="required-asterisk">*</span></label>
                        <input type="text" id="applyFirstName" name="first_name" class="form-control" required maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyLastName">Last Name <span class="required-asterisk">*</span></label>
                        <input type="text" id="applyLastName" name="last_name" class="form-control" required maxlength="50">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold" for="applyMiddleName">Middle Name</label>
                        <input type="text" id="applyMiddleName" name="middle_name" class="form-control" maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyEmail">Email <span class="required-asterisk">*</span></label>
                        <input type="email" id="applyEmail" name="email" class="form-control" required maxlength="100">
                        <small class="text-muted d-block mt-1">We will reach out to you at this email about your application.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyPhone">Phone Number <span class="required-asterisk">*</span></label>
                        <input type="text" id="applyPhone" name="phone" class="form-control" placeholder="09123456789" required maxlength="12" pattern="[0-9]{10,12}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyBirthdate">Birthdate <span class="required-asterisk">*</span></label>
                        <input type="date" id="applyBirthdate" name="birthdate" class="form-control" required max="' . date('Y-m-d') . '">
                        <div class="invalid-feedback" id="applyBirthdateError"></div>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline-secondary step-back-btn" data-goto="1"><i class="bi bi-arrow-left me-1"></i> Back</button>
                    <button type="button" class="btn btn-yellow-primary step-next-btn" data-goto="3">Next <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </div>

            <div class="form-step" data-step="3">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyProvince">Province <span class="required-asterisk">*</span></label>
                        <select id="applyProvince" class="form-select searchable-select" data-placeholder="Select province..." required>
                            <option value=""></option>
                        </select>
                        <input type="hidden" id="applyProvinceCode" name="province_code">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyCity">City / Municipality <span class="required-asterisk">*</span></label>
                        <select id="applyCity" class="form-select searchable-select" data-placeholder="Select province first..." required disabled>
                            <option value=""></option>
                        </select>
                        <input type="hidden" id="applyCityCode" name="city_municipality_code">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyBarangay">Barangay <span class="required-asterisk">*</span></label>
                        <select id="applyBarangay" class="form-select searchable-select" data-placeholder="Select city/municipality first..." required disabled>
                            <option value=""></option>
                        </select>
                        <input type="hidden" id="applyBarangayCode" name="barangay_code">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyPostalCode">Postal Code <span class="required-asterisk">*</span></label>
                        <input type="text" id="applyPostalCode" name="postal_code" class="form-control" placeholder="e.g. 2900" required maxlength="4" pattern="[0-9]{4}" inputmode="numeric">
                        <div class="form-text">4-digit ZIP code for your barangay.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyHouseBlockLot">House / Block / Lot No. <span class="required-asterisk">*</span></label>
                        <input type="text" id="applyHouseBlockLot" name="house_block_lot" class="form-control" required maxlength="255" placeholder="e.g. Blk 4 Lot 12">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="applyStreet">Street <span class="required-asterisk">*</span></label>
                        <input type="text" id="applyStreet" name="street" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold" for="applySubdivision">Subdivision <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" id="applySubdivision" name="subdivision" class="form-control" maxlength="255">
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline-secondary step-back-btn" data-goto="2"><i class="bi bi-arrow-left me-1"></i> Back</button>
                    <button type="button" class="btn btn-yellow-primary step-next-btn" data-goto="4">Next <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </div>

            <div class="form-step" data-step="4">
                <input type="hidden" name="skill_ratings" id="skillRatingsInput" value="{}">
                <div id="skillsAssessmentContainer"></div>
                <div class="step-actions">
                    <button type="button" class="btn btn-outline-secondary step-back-btn" data-goto="3"><i class="bi bi-arrow-left me-1"></i> Back</button>
                    <button type="submit" id="applySubmitBtn" class="btn btn-yellow-primary">
                        <i class="bi bi-send me-2"></i>Submit Application
                    </button>
                </div>
            </div>
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
    var SKILL_QUESTIONNAIRES = ' . $questionnairesJson . ';
    // Mirrors app/helpers/functions.php::jobPostingToQuestionnaireKey() --
    // keep both in sync if a new controlled department is ever added.
    var DEPT_QUESTIONNAIRE_MAP = { "Cashier": "cashier", "HR Staff": "hr_staff", "Finance Staff": "finance_staff" };

    function questionnaireKeyForJob(job) {
        if (!job) return null;
        if (DEPT_QUESTIONNAIRE_MAP[job.department]) return DEPT_QUESTIONNAIRE_MAP[job.department];
        if ((job.role || "").toLowerCase() === "store_manager") return "store_manager";
        return null;
    }

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

        document.getElementById("jobDetailPanelInner").innerHTML =
            "<h2>" + escapeHtml(job.title) + "</h2>" +
            \'<div class="job-meta-badges">\' + badges.join("") + "</div>" +
            \'<div class="job-detail-section"><h6><i class="bi bi-file-text"></i> Job Description</h6>\'
                + renderListOrParagraph(job.description, "Description not provided.") + "</div>" +
            \'<div class="job-detail-section"><h6><i class="bi bi-check2-square"></i> Requirements</h6>\'
                + renderListOrParagraph(job.requirements, "Requirements not provided.") + "</div>" +
            responsibilitiesHtml +
            salaryHtml;

        // New content almost always changes the scrollable height, so the
        // indicator (thumb size/position, and whether it shows at all) has
        // to be recomputed every time the panel is re-rendered, not just on scroll.
        updateJobScrollIndicator();
    }

    // ------------------------------------------------------------------
    // Custom scroll indicator for the job-details panel, drawn on top of a
    // completely hidden native scrollbar so it looks identical in every
    // browser (see .job-scroll-indicator / .job-panel-scroll::-webkit-
    // scrollbar in the stylesheet for why the native one was dropped).
    // ------------------------------------------------------------------
    function updateJobScrollIndicator() {
        var scrollEl = document.getElementById("jobDetailPanelInner");
        var indicator = document.getElementById("jobScrollIndicator");
        var thumb = document.getElementById("jobScrollThumb");
        if (!scrollEl || !indicator || !thumb) return;

        var scrollable = scrollEl.scrollHeight - scrollEl.clientHeight;
        if (scrollable <= 1) {
            indicator.classList.remove("show");
            return;
        }

        indicator.classList.add("show");
        var trackHeight = indicator.clientHeight;
        var thumbHeight = Math.max(24, (scrollEl.clientHeight / scrollEl.scrollHeight) * trackHeight);
        var maxThumbTop = trackHeight - thumbHeight;
        var thumbTop = (scrollEl.scrollTop / scrollable) * maxThumbTop;

        thumb.style.height = thumbHeight + "px";
        thumb.style.top = thumbTop + "px";
    }

    // Since the real scrollbar is hidden entirely, the custom thumb has to
    // implement its own drag-to-scroll -- otherwise it is a purely visual
    // indicator that a mouse-down on it would just select the page text
    // underneath instead of scrolling.
    function setupJobScrollIndicatorDrag() {
        var scrollEl = document.getElementById("jobDetailPanelInner");
        var indicator = document.getElementById("jobScrollIndicator");
        var thumb = document.getElementById("jobScrollThumb");
        if (!scrollEl || !indicator || !thumb) return;

        var dragging = false;
        var dragStartY = 0;
        var dragStartScrollTop = 0;

        function clientYOf(e) {
            return (e.touches && e.touches.length) ? e.touches[0].clientY : e.clientY;
        }

        function beginDrag(e) {
            dragging = true;
            dragStartY = clientYOf(e);
            dragStartScrollTop = scrollEl.scrollTop;
            indicator.classList.add("dragging");
            document.body.style.userSelect = "none";
            e.preventDefault();
        }

        function duringDrag(e) {
            if (!dragging) return;
            var trackHeight = indicator.clientHeight;
            var thumbHeight = thumb.offsetHeight;
            var maxThumbTop = trackHeight - thumbHeight;
            if (maxThumbTop <= 0) return;

            var scrollable = scrollEl.scrollHeight - scrollEl.clientHeight;
            var deltaY = clientYOf(e) - dragStartY;
            var deltaScroll = (deltaY / maxThumbTop) * scrollable;
            scrollEl.scrollTop = dragStartScrollTop + deltaScroll;
            e.preventDefault();
        }

        function endDrag() {
            if (!dragging) return;
            dragging = false;
            indicator.classList.remove("dragging");
            document.body.style.userSelect = "";
        }

        thumb.addEventListener("mousedown", beginDrag);
        thumb.addEventListener("touchstart", beginDrag, { passive: false });
        document.addEventListener("mousemove", duringDrag);
        document.addEventListener("touchmove", duringDrag, { passive: false });
        document.addEventListener("mouseup", endDrag);
        document.addEventListener("touchend", endDrag);

        // Clicking the track itself (not the thumb) jumps straight to that
        // position, same as clicking an empty spot on a native scrollbar.
        indicator.addEventListener("mousedown", function (e) {
            if (e.target === thumb) return;
            var rect = indicator.getBoundingClientRect();
            var trackHeight = indicator.clientHeight;
            var thumbHeight = thumb.offsetHeight;
            var clickY = e.clientY - rect.top - thumbHeight / 2;
            var maxThumbTop = trackHeight - thumbHeight;
            var ratio = Math.min(1, Math.max(0, clickY / maxThumbTop));
            var scrollable = scrollEl.scrollHeight - scrollEl.clientHeight;
            scrollEl.scrollTop = ratio * scrollable;
        });
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

    // ------------------------------------------------------------------
    // Skills self-assessment (Likert scale), rebuilt whenever the selected
    // position changes since each position has its own question set (see
    // SKILL_QUESTIONNAIRES / questionnaireKeyForJob above).
    // ------------------------------------------------------------------
    function updateSkillRatingsInput() {
        var input = document.getElementById("skillRatingsInput");
        if (!input) return;
        var ratings = {};
        Array.prototype.forEach.call(document.querySelectorAll("#skillsAssessmentContainer input[type=radio]:checked"), function (radio) {
            ratings[radio.dataset.skillKey] = radio.value;
        });
        input.value = JSON.stringify(ratings);
    }

    function renderSkillsAssessment(job) {
        var container = document.getElementById("skillsAssessmentContainer");
        if (!container) return;
        var key = questionnaireKeyForJob(job);
        var skills = key ? (SKILL_QUESTIONNAIRES[key] || []) : [];

        if (!skills.length) {
            container.innerHTML = \'<div class="skills-empty-state"><i class="bi bi-check2-circle me-1"></i>No skills assessment is required for this position.</div>\';
            updateSkillRatingsInput();
            return;
        }

        var levels = [
            [1, "Not Proficient"], [2, "Slightly"], [3, "Moderately"], [4, "Highly"], [5, "Most Proficient"]
        ];
        var headHtml = \'<th class="skill-col-head">Skill / Competency</th>\' + levels.map(function (lvl) {
            return "<th>" + lvl[0] + "<small>" + lvl[1] + "</small></th>";
        }).join("");

        var rowsHtml = skills.map(function (skill) {
            var cells = levels.map(function (lvl) {
                return \'<td><input type="radio" name="skill_\' + skill.key + \'" value="\' + lvl[0] + \'" data-skill-key="\' + skill.key + \'"></td>\';
            }).join("");
            return \'<tr data-skill-row="\' + skill.key + \'"><td class="skill-col">\' + escapeHtml(skill.label) + "</td>" + cells + "</tr>";
        }).join("");

        container.innerHTML = \'<div class="skills-table-wrap"><table class="skills-likert-table"><thead><tr>\' + headHtml + "</tr></thead><tbody>" + rowsHtml + "</tbody></table></div>";

        Array.prototype.forEach.call(container.querySelectorAll("input[type=radio]"), function (radio) {
            radio.addEventListener("change", function () {
                var row = radio.closest("tr");
                if (row) row.classList.remove("skill-row-missing");
                updateSkillRatingsInput();
            });
        });

        updateSkillRatingsInput();
    }

    function validateSkillsStep() {
        var container = document.getElementById("skillsAssessmentContainer");
        if (!container) return true;
        var rows = Array.prototype.slice.call(container.querySelectorAll("tr[data-skill-row]"));
        if (!rows.length) return true; // no questionnaire required for this position

        var firstMissingRow = null;
        rows.forEach(function (row) {
            var checked = row.querySelector("input[type=radio]:checked");
            row.classList.toggle("skill-row-missing", !checked);
            if (!checked && !firstMissingRow) firstMissingRow = row;
        });

        if (firstMissingRow) {
            firstMissingRow.scrollIntoView({ behavior: "smooth", block: "center" });
            Swal.fire({ icon: "warning", title: "Missing ratings", text: "Please rate every skill before submitting." });
            return false;
        }
        return true;
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
        renderSkillsAssessment(job);
    }

    // Keeps the panel, dots, and hidden field from showing a stale job after
    // the searchable-selects own "clear" (X) button empties the select --
    // that button is a generic filter-clear affordance from the shared
    // component, not aware this field requires exactly one selection.
    function clearSelectionState() {
        document.getElementById("jobDetailPanelInner").innerHTML =
            \'<p class="text-muted mb-0 small fst-italic">Select a position above to see its details.</p>\';
        document.getElementById("jobPostingIdInput").value = "";
        Array.prototype.forEach.call(document.querySelectorAll(".carousel-dot"), function (dot) {
            dot.setAttribute("aria-current", "false");
        });
        renderSkillsAssessment(null);
        updateJobScrollIndicator();
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
        if (APPLY_JOBS.length < 2 || userHasChosen || positionLocked) return;
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

    // ------------------------------------------------------------------
    // Position lock: once the user moves past the "Position & Resume" step,
    // the carousel arrows/dots are disabled and auto-swipe is stopped for
    // good. Without this, those controls kept working on every later step
    // (they live outside the step system entirely) -- an accidental click
    // could silently swap which job the rest of the form gets submitted
    // for, discovered only after filling everything else in, by which point
    // resubmitting under the same email is blocked.
    // ------------------------------------------------------------------
    var positionLocked = false;

    function setPositionLocked(locked) {
        positionLocked = locked;
        var prevBtn = document.getElementById("carouselPrevBtn");
        var nextBtn = document.getElementById("carouselNextBtn");
        [prevBtn, nextBtn].forEach(function (btn) { if (btn) btn.disabled = locked; });
        var dotsNav = document.getElementById("carouselDots");
        if (dotsNav) dotsNav.classList.toggle("carousel-dots-locked", locked);

        if (locked) {
            stopAutoSwipe();
        } else if (!userHasChosen) {
            startAutoSwipe();
        }
    }

    // ------------------------------------------------------------------
    // Philippine address cascade: Province -> City/Municipality -> Barangay
    // Backed by app/handlers/api_ph_locations.php (live PSGC data, cached
    // server-side). Each <select> is a searchable-select whose visible
    // value is the PSGC code; a paired hidden input carries that same code
    // to the backend, which re-validates the whole chain on submit.
    // ------------------------------------------------------------------
    function setSelectPlaceholder(select, placeholder) {
        select.setAttribute("data-placeholder", placeholder);
        var instance = select.searchableSelectInstance;
        if (instance && instance.input && !instance.currentValue) {
            instance.input.placeholder = placeholder;
        }
    }

    function populateOptions(select, items, placeholder) {
        select.innerHTML = \'<option value="">\' + escapeHtml(placeholder) + "</option>";
        items.forEach(function (item) {
            var opt = document.createElement("option");
            opt.value = item.code;
            opt.textContent = item.name;
            select.appendChild(opt);
        });
        setSelectPlaceholder(select, placeholder);
    }

    function resetDependentSelect(select, hiddenInput, placeholder) {
        select.innerHTML = \'<option value=""></option>\';
        select.disabled = true;
        hiddenInput.value = "";
        if (window.refreshSearchableSelect) window.refreshSearchableSelect(select);
        setSelectPlaceholder(select, placeholder);
    }

    function setupAddressCascade() {
        var provinceSelect = document.getElementById("applyProvince");
        var provinceCode = document.getElementById("applyProvinceCode");
        var citySelect = document.getElementById("applyCity");
        var cityCode = document.getElementById("applyCityCode");
        var barangaySelect = document.getElementById("applyBarangay");
        var barangayCode = document.getElementById("applyBarangayCode");
        var postalCode = document.getElementById("applyPostalCode");

        if (!provinceSelect) return; // Form not rendered (no open jobs).

        fetch("?page=api_ph_locations&type=provinces")
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    populateOptions(provinceSelect, res.data, "Select province...");
                    if (window.refreshSearchableSelect) window.refreshSearchableSelect(provinceSelect);
                }
            })
            .catch(function () {
                Swal.fire({ icon: "error", title: "Could not load provinces", text: "Please refresh the page to try again." });
            });

        provinceSelect.addEventListener("change", function () {
            provinceCode.value = this.value;
            resetDependentSelect(barangaySelect, barangayCode, "Select city/municipality first...");
            postalCode.value = "";

            if (!this.value) {
                resetDependentSelect(citySelect, cityCode, "Select province first...");
                return;
            }

            citySelect.disabled = true;
            setSelectPlaceholder(citySelect, "Loading...");
            fetch("?page=api_ph_locations&type=cities&province_code=" + encodeURIComponent(this.value))
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        populateOptions(citySelect, res.data, "Select city/municipality...");
                        citySelect.disabled = false;
                        if (window.refreshSearchableSelect) window.refreshSearchableSelect(citySelect);
                    }
                })
                .catch(function () {
                    Swal.fire({ icon: "error", title: "Could not load cities/municipalities", text: "Please try selecting the province again." });
                });
        });

        citySelect.addEventListener("change", function () {
            cityCode.value = this.value;
            postalCode.value = "";

            if (!this.value) {
                resetDependentSelect(barangaySelect, barangayCode, "Select city/municipality first...");
                return;
            }

            barangaySelect.disabled = true;
            setSelectPlaceholder(barangaySelect, "Loading...");
            fetch("?page=api_ph_locations&type=barangays&city_code=" + encodeURIComponent(this.value))
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        populateOptions(barangaySelect, res.data, "Select barangay...");
                        barangaySelect.disabled = false;
                        if (window.refreshSearchableSelect) window.refreshSearchableSelect(barangaySelect);
                    }
                })
                .catch(function () {
                    Swal.fire({ icon: "error", title: "Could not load barangays", text: "Please try selecting the city/municipality again." });
                });
        });

        barangaySelect.addEventListener("change", function () {
            barangayCode.value = this.value;
        });
    }

    // ------------------------------------------------------------------
    // Resume upload: drag-and-drop dropzone wrapping the real file input.
    // ------------------------------------------------------------------
    function setupResumeDropzone() {
        var dropzone = document.getElementById("resumeDropzone");
        var input = document.getElementById("applyResume");
        if (!dropzone || !input) return;

        var emptyState = document.getElementById("resumeDropzoneEmpty");
        var fileState = document.getElementById("resumeDropzoneFile");
        var fileNameEl = document.getElementById("resumeFileName");
        var fileSizeEl = document.getElementById("resumeFileSize");
        var removeBtn = document.getElementById("resumeRemoveBtn");

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + " B";
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
            return (bytes / (1024 * 1024)).toFixed(1) + " MB";
        }

        function refresh() {
            var file = input.files && input.files[0];
            if (file) {
                fileNameEl.textContent = file.name;
                fileSizeEl.textContent = formatFileSize(file.size);
                emptyState.style.display = "none";
                fileState.style.display = "flex";
            } else {
                emptyState.style.display = "";
                fileState.style.display = "none";
            }
        }

        input.addEventListener("change", refresh);

        removeBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            input.value = "";
            refresh();
        });

        ["dragenter", "dragover"].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add("dragover");
            });
        });

        ["dragleave", "drop"].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove("dragover");
            });
        });

        dropzone.addEventListener("drop", function (e) {
            var files = e.dataTransfer.files;
            if (files && files.length) {
                input.files = files;
                refresh();
            }
        });

        refresh();
    }

    // ------------------------------------------------------------------
    // Multi-step form: Personal Info -> Home Address -> Position & Resume.
    // Only one .form-step is visible at a time so the panel never has to
    // fit every field (including the address cascade) on screen at once.
    // ------------------------------------------------------------------
    function setupStepper() {
        var steps = Array.prototype.slice.call(document.querySelectorAll(".form-step"));
        var navSteps = Array.prototype.slice.call(document.querySelectorAll(".apply-timeline-step"));
        if (!steps.length) return;
        var current = 1;

        function updateUI() {
            steps.forEach(function (el) {
                el.classList.toggle("active", parseInt(el.dataset.step, 10) === current);
            });
            navSteps.forEach(function (el) {
                var idx = parseInt(el.dataset.stepNav, 10);
                el.classList.toggle("completed", idx < current);
                el.classList.toggle("active", idx === current);
            });
        }

        // Real <select> elements powering the searchable-select widget stay
        // display:none, and hidden form controls are exempt from the native
        // HTML5 required check -- so those need a manual value check instead
        // of relying on checkValidity()/reportValidity().
        function validateStep(stepIndex) {
            if (stepIndex === 4) return validateSkillsStep();
            var stepEl = document.querySelector(\'.form-step[data-step="\' + stepIndex + \'"]\');
            if (!stepEl) return true;
            var fields = Array.prototype.slice.call(stepEl.querySelectorAll("[required]"));
            for (var i = 0; i < fields.length; i++) {
                var el = fields[i];
                if (el.tagName === "SELECT") {
                    if (!el.value) {
                        var instance = el.searchableSelectInstance;
                        if (instance && instance.input) instance.input.focus();
                        Swal.fire({ icon: "warning", title: "Missing information", text: "Please complete all required fields before continuing." });
                        return false;
                    }
                } else if (!el.checkValidity()) {
                    el.reportValidity();
                    return false;
                }
            }
            return true;
        }

        function goToStep(n) {
            current = n;
            updateUI();
            setPositionLocked(n !== 1);
        }

        document.querySelectorAll(".step-next-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                if (!validateStep(current)) return;
                goToStep(parseInt(btn.dataset.goto, 10));
            });
        });

        document.querySelectorAll(".step-back-btn").forEach(function (btn) {
            btn.addEventListener("click", function () {
                goToStep(parseInt(btn.dataset.goto, 10));
            });
        });

        navSteps.forEach(function (el) {
            el.querySelector(".apply-timeline-circle").addEventListener("click", function () {
                var idx = parseInt(el.dataset.stepNav, 10);
                if (idx < current) goToStep(idx); // only completed (earlier) steps are jumpable
            });
        });

        updateUI();
    }

    document.addEventListener("DOMContentLoaded", function () {
        renderDots(null);
        setupAddressCascade();
        setupResumeDropzone();
        setupStepper();

        var jobDetailPanelInnerEl = document.getElementById("jobDetailPanelInner");
        if (jobDetailPanelInnerEl) {
            jobDetailPanelInnerEl.addEventListener("scroll", updateJobScrollIndicator);
        }
        window.addEventListener("resize", updateJobScrollIndicator);
        setupJobScrollIndicatorDrag();

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

        if (!document.getElementById("applyProvinceCode").value
            || !document.getElementById("applyCityCode").value
            || !document.getElementById("applyBarangayCode").value) {
            Swal.fire({ icon: "warning", title: "Complete your address", text: "Please select your Province, City/Municipality, and Barangay." });
            return;
        }

        if (!validateSkillsStep()) {
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
