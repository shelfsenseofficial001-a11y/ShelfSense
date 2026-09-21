<?php
// views/pages/hr/job_posting_approvals.php
// HR Head's dedicated review queue -- same list/detail-drawer markup and JS
// as job_postings.php (see $approvalsMode there), just pre-filtered to
// pending_approval and stripped of the authoring-only controls so there is
// one clear place to make approve/reject decisions.
$approvalsMode = true;
require __DIR__ . '/job_postings.php';
