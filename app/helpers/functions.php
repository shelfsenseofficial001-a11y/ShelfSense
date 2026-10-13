<?php

// Allowed position values for job postings (kept in sync with the
// searchable-select options in views/pages/hr/job_postings.php). This is the
// controlled value that actually drives an applicant's target_role -- see
// jobPostingDepartmentToTargetRole() below. Historically called "department"
// throughout the codebase; conceptually it is now the *position* nested one
// level below the department group.
define('JOB_POSTING_DEPARTMENTS', ['Cashier', 'HR Staff', 'Finance Staff']);

/**
 * Strips markdown syntax from a job posting's Description (written with the
 * toolbar in views/pages/hr/job_postings.php) down to plain text, then
 * truncates it -- for short, non-HTML teasers like the landing page's job
 * cards, where the full markdown-to-HTML render (mdToHtml() in
 * assets/js/shared/markdown.js) would be overkill and truncating raw
 * markdown mid-syntax (e.g. cutting off inside "**bold") looks broken, as
 * would leaving literal "##"/"**" characters visible in a plain-text card.
 */
function stripMarkdownPreview($text, $length = 220)
{
    $text = (string)$text;
    $text = preg_replace('/^#{1,6}\s+/m', '', $text);          // # / ## / ### headings
    $text = preg_replace('/^[-*]{3,}\s*$/m', '', $text);       // --- horizontal rules
    $text = preg_replace('/^[-*]\s+/m', '', $text);            // - / * bullet markers
    $text = preg_replace('/^\d+\.\s+/m', '', $text);           // 1. numbered markers
    $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);    // **bold**
    $text = preg_replace('/~~([^~]+)~~/', '$1', $text);        // ~~strikethrough~~
    $text = preg_replace('/`([^`]+)`/', '$1', $text);          // `code`
    $text = preg_replace('/\*([^*]+)\*/', '$1', $text);        // *italic*
    $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text); // [label](url)
    $text = preg_replace('/\n{2,}/', ' ', $text);              // collapse blank lines
    $text = trim(preg_replace('/\s+/', ' ', $text));

    return mb_strimwidth($text, 0, $length, '...');
}

// Top-level department groups shown to HR Staff when creating a posting.
// Each group scopes which JOB_POSTING_DEPARTMENTS positions are selectable,
// so the form is a two-step Department -> Position cascade rather than one
// flat list -- ready for more positions to be added under a group later
// without changing the target-role plumbing below.
define('JOB_POSTING_DEPARTMENT_GROUPS', ['Front Department', 'Human Resources Department', 'Finance Department']);

define('JOB_POSTING_GROUP_POSITIONS', [
    'Front Department' => ['Cashier'],
    'Human Resources Department' => ['HR Staff'],
    'Finance Department' => ['Finance Staff'],
]);

// Employment type is no longer chosen at posting time -- every posting is
// Full-Time. The column and constant are kept only so existing rows/reads
// don't need a schema change.
define('JOB_POSTING_EMPLOYMENT_TYPES', ['Full-Time', 'Part-Time', 'Contract', 'Internship']);

// Self-rated (1-5 Likert) skills questionnaire shown to an applicant on the
// public Apply page, right after they pick a position -- one set per
// position type, so HR can compare candidates for the same role on the same
// dimensions (see jobPostingToQuestionnaireKey() below for how a posting
// maps to one of these keys). Keys are stable identifiers stored in
// applicant_skill_ratings.skill_key; labels are display-only and can be
// edited here without touching any stored data.
define('SKILL_QUESTIONNAIRES', [
    'hr_staff' => [
        ['key' => 'ats_systems', 'label' => 'Applicant Tracking Systems (ATS) (e.g., Workday, BambooHR, Lever)'],
        ['key' => 'full_cycle_recruitment', 'label' => 'Full-Cycle Recruitment & Sourcing Strategies'],
        ['key' => 'labor_law_compliance', 'label' => 'Labor Law Compliance & HR Policy Execution'],
        ['key' => 'onboarding_offboarding', 'label' => 'Employee Onboarding & Offboarding Workflows'],
        ['key' => 'confidential_grievances', 'label' => 'Handling Confidential Employee Grievances'],
    ],
    'finance_staff' => [
        ['key' => 'advanced_excel', 'label' => 'Advanced Excel (Lookup functions, Pivot Tables, Macros)'],
        ['key' => 'erp_systems', 'label' => 'Accounting & ERP Systems (SAP, QuickBooks, NetSuite)'],
        ['key' => 'account_reconciliation', 'label' => 'Month-End Account Reconciliations & Audit Prep'],
        ['key' => 'ap_ar_management', 'label' => 'Managing Accounts Payable (AP) & Receivable (AR)'],
        ['key' => 'cash_flow_forecasting', 'label' => 'Cash Flow Forecasting & Budget Variance Analysis'],
    ],
    // No "POS Terminal Operation (Square/Clover/...)" question -- cashiers
    // here only ever use ShelfSense's own POS, never third-party terminals.
    'cashier' => [
        ['key' => 'cash_handling', 'label' => 'Cash Handling & Accurate Change Calculation'],
        ['key' => 'card_digital_payments', 'label' => 'Processing Card Terminals & Digital / Mobile Wallets'],
        ['key' => 'drawer_reconciliation', 'label' => 'End-of-Shift Cash Drawer Balancing & Reconciliation'],
        ['key' => 'register_speed_accuracy', 'label' => 'Maintaining Register Speed & Accuracy During Peak Hours'],
        ['key' => 'deescalating_customers', 'label' => 'De-escalating Difficult or Impatient Customers'],
    ],
    'store_manager' => [
        ['key' => 'sales_target_achievement', 'label' => 'Sales Target Achievement & Revenue Growth Driving'],
        ['key' => 'staff_coaching', 'label' => 'Recruiting, Onboarding & Staff Performance Coaching'],
        ['key' => 'labor_cost_scheduling', 'label' => 'Labor Cost Optimization & Shift Scheduling'],
        ['key' => 'inventory_shrinkage', 'label' => 'Inventory Management & Shrinkage / Loss Prevention'],
        ['key' => 'visual_merchandising', 'label' => 'Visual Merchandising & Planogram Execution'],
    ],
]);

// ============================================
// STRING HELPERS
// ============================================

function escape($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Role -> employee_number prefix. Shared by every place that mints a new
// number (trainee creation, direct hire, and the trainee-to-hired upgrade in
// activateEmployeeFromAcceptedContract()) so the scheme never drifts between
// call sites.
const EMPLOYEE_NUMBER_PREFIXES = [
    'owner' => 'SA',
    'hr_head' => 'HH',
    'hr_staff' => 'HS',
    'employee' => 'EM',
    'finance_head' => 'FH',
    'finance_staff' => 'FS',
    'trainee' => 'TR',
    'store_manager' => 'SM',
    'supplier' => 'SP',
];

/**
 * Generates a unique employee_number for $role (e.g. "EM-042"), retrying on
 * collision -- employee_number is UNIQUE in the DB, and the old
 * implementation used a bare rand(100,999) with no existence check, which
 * could throw a duplicate-key error under real concurrent use. Falls back to
 * a timestamp-suffixed number in the near-impossible case all retries collide.
 */
function generateEmployeeNumber($role)
{
    $prefix = EMPLOYEE_NUMBER_PREFIXES[$role] ?? 'USR';
    $db = \App\Core\Database::getInstance()->getConnection();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $number = $prefix . '-' . str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE employee_number = ?");
        $stmt->execute([$number]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $number;
        }
    }

    return $prefix . '-' . substr((string)time(), -3);
}

function generateDefaultPassword($firstName, $lastName)
{
    $password = $firstName . $lastName;
    if (strlen($password) < 8) {
        $password .= '1234';
    }
    return $password;
}

function getFullName($firstName, $lastName, $middleName = null)
{
    if ($middleName) {
        return $firstName . ' ' . $middleName . ' ' . $lastName;
    }
    return $firstName . ' ' . $lastName;
}

// ============================================
// DATE HELPERS
// ============================================

function formatDate($date, $format = 'F j, Y')
{
    return date($format, strtotime($date));
}

function formatTime($time, $format = 'h:i A')
{
    return date($format, strtotime($time));
}

function getCurrentDate()
{
    return date('Y-m-d');
}

function getCurrentDateTime()
{
    return date('Y-m-d H:i:s');
}

// ============================================
// FILE HELPERS
// ============================================

// MIME types accepted for each allowed extension, checked against the
// file's actual content (not just its client-supplied name) so a renamed
// executable can't slip through as a ".pdf".
const UPLOAD_MIME_WHITELIST = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
];

function uploadFile($file, $targetDir, $allowedTypes = [], $maxSize = 5242880)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!empty($allowedTypes) && !in_array($ext, $allowedTypes, true)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }

    if (isset(UPLOAD_MIME_WHITELIST[$ext])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) finfo_close($finfo);
        if (!$detectedMime || !in_array($detectedMime, UPLOAD_MIME_WHITELIST[$ext], true)) {
            return ['success' => false, 'message' => 'File content does not match the ' . strtoupper($ext) . ' format.'];
        }
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Fully random filename -- never derived from the client-supplied name,
    // so there is no path-traversal, collision, or enumeration risk.
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $path = $targetDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => true, 'path' => $path, 'filename' => $filename];
    }

    return ['success' => false, 'message' => 'Failed to upload file'];
}

// ============================================
// NOTIFICATION HELPERS
// ============================================

function createNotification($userId, $type, $message, $link = null)
{
    $db = \App\Core\Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, type, message, link) 
        VALUES (?, ?, ?, ?)
    ");

    return $stmt->execute([$userId, $type, $message, $link]);
}

// ============================================
// TRAINEE DEPARTMENT ROUTING
// ============================================

/**
 * Maps an applicant/trainee's display-form target_role (as stored by
 * api_apply.php -- 'Employee', 'HR Staff', 'Finance Staff', 'Head HR',
 * 'Head Finance') to the real users.role enum value. Used wherever a
 * trainer or reviewer's actual role must be matched against a trainee's
 * intended role.
 */
function mapDisplayRoleToDbRole($targetRole)
{
    $roleMap = [
        'Employee' => 'employee',
        'HR Staff' => 'hr_staff',
        'Finance Staff' => 'finance_staff',
        'Head HR' => 'hr_head',
        'Head Finance' => 'finance_head'
    ];
    return $roleMap[$targetRole] ?? $targetRole;
}

/**
 * Whether the currently logged-in user may edit this job posting's content
 * directly -- mirrors the exact rule enforced server-side in
 * update_job_posting.php, so the Edit icon shown in a postings table only
 * appears where clicking it would actually be allowed to save.
 */
function jobPostingCanEdit($posting)
{
    if (!\App\Core\Auth::check()) return false;
    if (\App\Core\Auth::isSuperAdmin()) return true;
    if ($posting['status'] === 'archived') return false;

    $isOwner = (int)$posting['created_by'] === (int)\App\Core\Auth::userId();
    $ownerEditable = $isOwner && in_array($posting['status'], ['draft', 'rejected'], true);
    $headEditable = \App\Core\Auth::isHRHead() && in_array($posting['status'], ['draft', 'rejected', 'pending_approval'], true);
    return $ownerEditable || $headEditable;
}

/**
 * Maps a job posting's controlled `department` value (JOB_POSTING_DEPARTMENTS
 * -- 'Cashier', 'HR Staff', 'Finance Staff') to the display-form target_role
 * an applicant record should carry, keeping it consistent with
 * mapDisplayRoleToDbRole() above. This is the authoritative source of an
 * application's target_role -- never the free-text job_postings.role field,
 * which HR types by hand and isn't a controlled value.
 */
function jobPostingDepartmentToTargetRole($department)
{
    $map = [
        'Cashier' => 'Employee',
        'HR Staff' => 'HR Staff',
        'Finance Staff' => 'Finance Staff',
    ];
    return $map[$department] ?? $department;
}

/**
 * Maps a job posting to a SKILL_QUESTIONNAIRES key, deciding which set of
 * skill questions an applicant sees on the Apply page. Controlled
 * departments map directly; a Store Manager posting has no controlled
 * department value (see JOB_POSTING_DEPARTMENTS), so it's identified by
 * job_postings.role instead. Anything else (e.g. a Supplier posting)
 * returns null -- no questionnaire is shown or required for it.
 */
function jobPostingToQuestionnaireKey($department, $role = null)
{
    $map = [
        'Cashier' => 'cashier',
        'HR Staff' => 'hr_staff',
        'Finance Staff' => 'finance_staff',
    ];
    if (isset($map[$department])) {
        return $map[$department];
    }
    if (strtolower(trim((string)$role)) === 'store_manager') {
        return 'store_manager';
    }
    return null;
}

/**
 * Maps a trainee's real target_role to the department bucket that decides
 * who reviews their weekly reports. Store is the safe default since most
 * trainees are store-facing roles (cashier, general store employee).
 */
function mapRoleToDepartment($targetRole)
{
    $role = strtolower(trim((string)$targetRole));
    if (in_array($role, ['finance staff', 'head finance', 'finance_staff', 'finance_head', 'finance'], true)) {
        return 'finance';
    }
    if (in_array($role, ['hr staff', 'head hr', 'hr_staff', 'hr_head', 'hr'], true)) {
        return 'hr';
    }
    return 'store';
}

/**
 * Who is authorized to add an observation and forward a department's weekly
 * trainee reports to HR Head. For HR trainees the Trainer is themselves an
 * HR Staff member, and the department head IS HR Head -- so this goes
 * straight from Trainer to HR Head in one hop, same as Store/Finance go to
 * their own department head/manager (never the Trainer themselves).
 */
function mapDepartmentToReviewerRole($department)
{
    $map = [
        'store' => 'store_manager',
        'finance' => 'finance_head',
        'hr' => 'hr_head'
    ];
    return $map[$department] ?? null;
}

/**
 * Whether this user is currently training someone (trainer_id on an active
 * trainees row) -- used to decide whether a rank-and-file trainer (not a
 * department head, who always sees it) gets the "Trainee Report" nav tab.
 */
function hasActiveTraineeAsTrainer(?int $userId): bool
{
    if (!$userId) return false;
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT 1 FROM trainees WHERE trainer_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$userId]);
    return (bool)$stmt->fetch();
}

// ============================================
// HIRED CONTRACT ACCEPTANCE
// ============================================

/**
 * Shared "accept a Hired Contract" logic. Only the trainee's own acceptance
 * (app/handlers/trainee/respond_to_contract.php) calls this -- HR cannot
 * accept on a trainee's behalf. Activates the employee account (role upgrade
 * + employee number + schedule) exactly once -- the caller must have already
 * verified the contract is still 'pending' under a row lock so this can
 * never run twice for the same contract.
 *
 * $contract must include: id, applicant_id, employee_user_id (or user_id),
 * target_role, first_name, applicant_email, shift, rest_days.
 */
function activateEmployeeFromAcceptedContract($db, $contract)
{
    $userId = $contract['employee_user_id'] ?? $contract['user_id'];
    $targetRole = $contract['target_role'];

    $dbRole = mapDisplayRoleToDbRole($targetRole);

    // Trainee ID (TR-xxx) upgrades to a real role-prefixed employee number
    // the moment the contract is accepted -- same collision-safe generator
    // used everywhere else a number is minted.
    $newEmployeeNumber = generateEmployeeNumber($dbRole);

    $db->prepare("UPDATE contracts SET status = 'accepted', accepted_at = NOW(), updated_at = NOW() WHERE id = ?")
        ->execute([$contract['id']]);

    $db->prepare("UPDATE applicants SET status = 'hired', updated_at = NOW() WHERE id = ?")
        ->execute([$contract['applicant_id']]);

    $db->prepare("
        UPDATE users SET role = ?, employee_number = ?, can_train = 1, is_first_login = 1, hired_date = NOW(), updated_at = NOW()
        WHERE user_id = ?
    ")->execute([$dbRole, $newEmployeeNumber, $userId]);

    $shift = $contract['shift'];
    $restDaysArray = !empty($contract['rest_days']) ? explode(',', $contract['rest_days']) : [];
    $shiftHours = [
        'opening' => ['08:00:00', '17:00:00'],
        'closing' => ['14:00:00', '22:00:00'],
        'midshift' => ['10:00:00', '18:00:00']
    ];
    $hours = $shiftHours[$shift] ?? ['08:00:00', '17:00:00'];

    $db->prepare("DELETE FROM schedules WHERE user_id = ?")->execute([$userId]);
    $stmt = $db->prepare("INSERT INTO schedules (user_id, day_of_week, time_in, time_out, is_rest_day) VALUES (?, ?, ?, ?, ?)");
    foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day) {
        $isRestDay = in_array($day, $restDaysArray, true) ? 1 : 0;
        $timeIn = $isRestDay ? '00:00:00' : $hours[0];
        $timeOut = $isRestDay ? '00:00:00' : $hours[1];
        $stmt->execute([$userId, $day, $timeIn, $timeOut, $isRestDay]);
    }

    // The trainee record is now historical -- the account itself has just
    // been promoted to a real employee role above, so its trainee record is
    // archived rather than deleted (never overwrites its training history).
    $db->prepare("UPDATE trainees SET archived_at = NOW() WHERE applicant_id = ? AND archived_at IS NULL")
        ->execute([$contract['applicant_id']]);

    logRecruitmentEvent('applicant', $contract['applicant_id'], 'hired', [
        'previous_status' => 'contract_offered', 'new_status' => 'hired'
    ]);

    return ['employee_number' => $newEmployeeNumber, 'role' => $dbRole];
}

// ============================================
// CONTRACT DECISION DEADLINES
// ============================================

/** A random, unguessable per-contract token for the no-login email response link -- never the sequential contract id. */
function generateContractResponseToken(): string
{
    return bin2hex(random_bytes(32));
}

/** Builds an absolute app URL from APP_URL in .env (falls back to the current request's own host if unset). */
function appUrl(string $path = ''): string
{
    $base = rtrim($_ENV['APP_URL'] ?? ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
    return $base . $path;
}

/**
 * Auto-declines any pending contract (Trainee or Hired) whose decision
 * deadline has passed -- the applicant/trainee simply loses the ability to
 * accept once the date is gone, no grace period. Called lazily at the top
 * of every page/endpoint that reads contracts, rather than relying on a
 * cron job, so it's always accurate by the time anyone looks.
 */
function expireOverdueContracts(PDO $db): void
{
    $stmt = $db->prepare("SELECT id, applicant_id FROM contracts WHERE status = 'pending' AND decision_deadline IS NOT NULL AND decision_deadline < CURDATE()");
    $stmt->execute();
    $expired = $stmt->fetchAll();
    if (!$expired) {
        return;
    }

    $db->prepare("UPDATE contracts SET status = 'declined', declined_at = NOW(), response_notes = 'Automatically declined: decision deadline passed.', updated_at = NOW() WHERE status = 'pending' AND decision_deadline IS NOT NULL AND decision_deadline < CURDATE()")->execute();

    $appStmt = $db->prepare("UPDATE applicants SET status = 'contract_declined', updated_at = NOW() WHERE id = ?");
    foreach ($expired as $row) {
        $appStmt->execute([$row['applicant_id']]);
        logRecruitmentEvent('applicant', $row['applicant_id'], 'contract_expired', [
            'previous_status' => 'contract_offered', 'new_status' => 'contract_declined', 'reason' => 'Decision deadline passed'
        ]);
    }
}

/**
 * The actual accept/decline business logic for a contract (Trainee or
 * Hired), shared between the logged-in trainee's own endpoint
 * (respond_to_contract.php) and the no-login, token-based email link
 * (respond_to_contract_token.php). $expectedUserId, when given, is an
 * ownership check (the logged-in path); the token path already proved
 * ownership by knowing the token, so it passes null.
 *
 * Returns ['ok' => bool, 'code' => int, 'message' => string, 'contract' => array|null].
 * Never throws for ordinary business-rule failures (already processed,
 * deadline passed, wrong owner) -- those come back as ok:false with a
 * message, same as any other validation error.
 */
function processContractResponse(PDO $db, int $contractId, string $action, string $responseNotes = '', ?int $expectedUserId = null): array
{
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT c.*, a.target_role, a.email as applicant_email, a.last_name as applicant_last_name,
               u.first_name, u.user_id as employee_user_id
        FROM contracts c
        JOIN applicants a ON c.applicant_id = a.id
        JOIN users u ON c.user_id = u.user_id
        WHERE c.id = ? FOR UPDATE
    ");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();

    if (!$contract) {
        $db->rollBack();
        return ['ok' => false, 'code' => 404, 'message' => 'Contract not found.', 'contract' => null];
    }
    if ($expectedUserId !== null && (int)$contract['user_id'] !== $expectedUserId) {
        $db->rollBack();
        return ['ok' => false, 'code' => 403, 'message' => 'This is not your contract.', 'contract' => null];
    }
    if ($contract['status'] !== 'pending') {
        $db->rollBack();
        return ['ok' => false, 'code' => 400, 'message' => 'This contract has already been processed.', 'contract' => $contract];
    }

    // The decision deadline is a hard cutoff -- no grace period.
    if (!empty($contract['decision_deadline']) && strtotime($contract['decision_deadline'] . ' 23:59:59') < time()) {
        $db->prepare("UPDATE contracts SET status = 'declined', declined_at = NOW(), response_notes = 'Automatically declined: decision deadline passed.', updated_at = NOW() WHERE id = ?")
            ->execute([$contract['id']]);
        $db->prepare("UPDATE applicants SET status = 'contract_declined', updated_at = NOW() WHERE id = ?")
            ->execute([$contract['applicant_id']]);
        logRecruitmentEvent('applicant', $contract['applicant_id'], 'contract_expired', [
            'previous_status' => 'contract_offered', 'new_status' => 'contract_declined', 'reason' => 'Decision deadline passed'
        ]);
        $db->commit();
        return ['ok' => false, 'code' => 400, 'message' => 'The decision deadline for this contract has passed. It has been automatically declined and can no longer be accepted.', 'contract' => $contract];
    }

    $isTraineeContract = $contract['contract_type'] === 'trainee';

    if ($action === 'accept') {
        if ($isTraineeContract) {
            $db->prepare("UPDATE contracts SET status = 'accepted', accepted_at = NOW(), updated_at = NOW() WHERE id = ?")
                ->execute([$contract['id']]);
            $message = 'Trainee Contract accepted! HR will now assign your trainer.';

            $stmt = $db->prepare("SELECT user_id FROM users WHERE role IN ('hr_head', 'hr_staff') AND is_active = 1");
            $stmt->execute();
            foreach ($stmt->fetchAll() as $staff) {
                createNotification($staff['user_id'], 'contract_accepted', "{$contract['first_name']} {$contract['applicant_last_name']} accepted their Trainee Contract. Assign a trainer.", "?page=hr_interviews");
            }
        } else {
            activateEmployeeFromAcceptedContract($db, $contract);
            $message = 'Contract accepted! Your employee account is now active.';

            try {
                $mailer = new \App\Core\Mailer();
                $mailer->sendApplicantStatusUpdate(
                    ['email' => $contract['applicant_email'], 'first_name' => $contract['first_name']],
                    'hired'
                );
            } catch (Exception $e) {
                error_log('processContractResponse: hired email failed: ' . $e->getMessage());
            }

            $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'hr_head' AND is_active = 1");
            $stmt->execute();
            foreach ($stmt->fetchAll() as $head) {
                createNotification($head['user_id'], 'contract_accepted', "{$contract['first_name']} {$contract['applicant_last_name']} accepted their Hired Contract.", "?page=hr_contracts");
            }
        }
    } else {
        $stmt = $db->prepare("UPDATE contracts SET status = 'declined', declined_at = NOW(), response_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$responseNotes !== '' ? $responseNotes : null, $contractId]);

        $stmt = $db->prepare("UPDATE applicants SET status = 'contract_declined', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$contract['applicant_id']]);

        logRecruitmentEvent('applicant', $contract['applicant_id'], 'contract_declined_by_applicant', [
            'previous_status' => 'contract_offered', 'new_status' => 'contract_declined', 'reason' => $responseNotes
        ]);

        $stmt = $db->prepare("SELECT user_id FROM users WHERE role IN ('hr_head', 'hr_staff') AND is_active = 1");
        $stmt->execute();
        $contractLabel = $isTraineeContract ? 'Trainee Contract' : 'Hired Contract';
        foreach ($stmt->fetchAll() as $staff) {
            createNotification($staff['user_id'], 'contract_declined', "{$contract['first_name']} {$contract['applicant_last_name']} declined their {$contractLabel}.", "?page=hr_contracts");
        }

        $message = 'Contract declined.';
    }

    $db->commit();
    return ['ok' => true, 'code' => 200, 'message' => $message, 'contract' => $contract];
}

// ============================================
// RECRUITMENT / JOB-POSTING AUDIT LOG
// ============================================

/**
 * Truthful audit trail for job-posting and recruitment events. Never logs
 * passwords, credentials, or tokens -- only ids/status/reasons/notes.
 */
function logRecruitmentEvent($entityType, $entityId, $action, $options = [])
{
    $db = \App\Core\Database::getInstance()->getConnection();
    $userId = $options['user_id'] ?? (\App\Core\Auth::userId() ?? null);
    $role = $options['role'] ?? (\App\Core\Auth::role() ?? null);

    $stmt = $db->prepare("
        INSERT INTO recruitment_logs (entity_type, entity_id, user_id, role, action, previous_status, new_status, reason, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    return $stmt->execute([
        $entityType,
        $entityId,
        $userId,
        $role,
        $action,
        $options['previous_status'] ?? null,
        $options['new_status'] ?? null,
        $options['reason'] ?? null,
        $options['notes'] ?? null
    ]);
}

function getUnreadNotifications($userId)
{
    $db = \App\Core\Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC
    ");

    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getNotifications($userId, $limit = 10, $offset = 0)
{
    $db = \App\Core\Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT * FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");

    $stmt->execute([$userId, $limit, $offset]);
    return $stmt->fetchAll();
}

function markNotificationAsRead($notificationId)
{
    $db = \App\Core\Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        UPDATE notifications SET is_read = 1 
        WHERE id = ?
    ");

    return $stmt->execute([$notificationId]);
}

function markAllNotificationsRead($userId)
{
    $db = \App\Core\Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        UPDATE notifications SET is_read = 1 
        WHERE user_id = ? AND is_read = 0
    ");

    return $stmt->execute([$userId]);
}

// ============================================
// ROLE HELPERS
// ============================================

function getRoleName($role)
{
    $roles = [
        'owner' => 'Owner',
        'hr_head' => 'HR Head',
        'hr_staff' => 'HR Staff',
        'employee' => 'Employee',
        'finance_head' => 'Finance Head',
        'finance_staff' => 'Finance Staff',
        'trainee' => 'Trainee',
        'store_manager' => 'Store Manager',
        'supplier' => 'Supplier'
    ];

    return $roles[$role] ?? ucfirst($role);
}

function getRoleBadgeClass($role)
{
    $classes = [
        'owner' => 'bg-danger',
        'hr_head' => 'bg-primary',
        'hr_staff' => 'bg-info text-dark',
        'employee' => 'bg-success',
        'finance_head' => 'bg-warning text-dark',
        'finance_staff' => 'bg-secondary',
        'trainee' => 'bg-warning text-dark'
    ];

    return $classes[$role] ?? 'bg-secondary';
}

function getRoleIcon($role)
{
    $icons = [
        'owner' => 'bi-star-fill',
        'hr_head' => 'bi-people-fill',
        'hr_staff' => 'bi-person-fill',
        'employee' => 'bi-cash',
        'finance_head' => 'bi-graph-up-arrow',
        'finance_staff' => 'bi-calculator',
        'trainee' => 'bi-mortarboard-fill'
    ];

    return $icons[$role] ?? 'bi-person';
}

// ============================================
// INTERVIEW HELPERS
// ============================================

function getInterviewStatusColor($status)
{
    $colors = [
        'scheduled' => 'warning',
        'completed' => 'info',
        'cancelled' => 'secondary',
        'passed' => 'success',
        'failed' => 'danger',
        'pending' => 'warning'
    ];

    return $colors[$status] ?? 'secondary';
}

function getInterviewResultText($result)
{
    $texts = [
        'passed' => '✅ Passed',
        'failed' => '❌ Failed',
        'pending' => '⏳ Pending'
    ];

    return $texts[$result] ?? '—';
}

// ============================================
// APPLICANT STATUS HELPERS
// ============================================

function getApplicantStatusColor($status)
{
    $colors = [
        'pending' => 'warning',
        'initial_scheduled' => 'info',
        'initial_passed' => 'primary',
        'initial_failed' => 'danger',
        'final_scheduled' => 'info',
        'final_passed' => 'primary',
        'final_failed' => 'danger',
        'screening' => 'success',
        'contract_offered' => 'warning',
        'contract_accepted' => 'success',
        'contract_declined' => 'danger',
        'hired' => 'success'
    ];

    return $colors[$status] ?? 'secondary';
}

function getApplicantStatusText($status)
{
    $texts = [
        'pending' => 'Pending Review',
        'initial_scheduled' => 'Initial Interview Scheduled',
        'initial_passed' => 'Passed Initial Interview',
        'initial_failed' => 'Failed Initial Interview',
        'final_scheduled' => 'Final Interview Scheduled',
        'final_passed' => 'Passed Final Interview',
        'final_failed' => 'Failed Final Interview',
        'screening' => 'In Training/Screening',
        'contract_offered' => 'Contract Offered',
        'contract_accepted' => 'Contract Accepted',
        'contract_declined' => 'Contract Declined',
        'hired' => 'Hired'
    ];

    return $texts[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

// ============================================
// LEAVE HELPERS - NEW
// ============================================

function getLeaveTypeText($type)
{
    $types = [
        'sick' => 'Sick Leave (Paid)',
        'vacation' => 'Vacation Leave (Paid)',
        'emergency' => 'Emergency Leave (Paid)',
        'other' => 'Other (Unpaid)'
    ];

    return $types[$type] ?? ucfirst($type);
}

function getLeaveStatusColor($status)
{
    $colors = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger'
    ];

    return $colors[$status] ?? 'secondary';
}

function getLeaveStatusText($status)
{
    $texts = [
        'pending' => '⏳ Pending',
        'approved' => '✅ Approved',
        'rejected' => '❌ Rejected'
    ];

    return $texts[$status] ?? $status;
}

function getLeaveIsPaidText($isPaid)
{
    return $isPaid ? '✅ Paid' : '❌ Unpaid';
}

function getLeaveIsPaidBadge($isPaid)
{
    return $isPaid 
        ? '<span class="badge bg-success">✅ Paid</span>'
        : '<span class="badge bg-secondary">❌ Unpaid</span>';
}

// ============================================
// LEAVE BALANCE HELPERS
// ============================================

function getLeaveBalanceForYear($userId, $year = null)
{
    if ($year === null) {
        $year = date('Y');
    }
    
    $db = \App\Core\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM leave_balances 
        WHERE user_id = ? AND year = ?
    ");
    $stmt->execute([$userId, $year]);
    $balance = $stmt->fetch();
    
    if (!$balance) {
        // Create default balance if not exists
        $stmt = $db->prepare("
            INSERT INTO leave_balances (user_id, year) 
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $year]);
        
        return [
            'sick_leave_entitled' => 15,
            'sick_leave_used' => 0,
            'vacation_leave_entitled' => 15,
            'vacation_leave_used' => 0,
            'emergency_leave_entitled' => 5,
            'emergency_leave_used' => 0,
            'other_leave_entitled' => 0,
            'other_leave_used' => 0
        ];
    }
    
    return $balance;
}

function getLeaveSummary($userId)
{
    $balance = getLeaveBalanceForYear($userId);
    
    return [
        'sick' => [
            'entitled' => $balance['sick_leave_entitled'],
            'used' => $balance['sick_leave_used'],
            'remaining' => $balance['sick_leave_entitled'] - $balance['sick_leave_used'],
            'is_paid' => true
        ],
        'vacation' => [
            'entitled' => $balance['vacation_leave_entitled'],
            'used' => $balance['vacation_leave_used'],
            'remaining' => $balance['vacation_leave_entitled'] - $balance['vacation_leave_used'],
            'is_paid' => true
        ],
        'emergency' => [
            'entitled' => $balance['emergency_leave_entitled'],
            'used' => $balance['emergency_leave_used'],
            'remaining' => $balance['emergency_leave_entitled'] - $balance['emergency_leave_used'],
            'is_paid' => true
        ],
        'other' => [
            'entitled' => $balance['other_leave_entitled'] ?? 0,
            'used' => $balance['other_leave_used'] ?? 0,
            'remaining' => 0,
            'is_paid' => false
        ]
    ];
}

function getWorkingDays($startDate, $endDate)
{
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $workingDays = 0;
    foreach ($period as $date) {
        $dayOfWeek = $date->format('N');
        if ($dayOfWeek < 6) {
            $workingDays++;
        }
    }
    
    return $workingDays;
}

function getLeaveDuration($startDate, $endDate)
{
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    return $diff->days + 1;
}

function checkLeaveOverlap($userId, $startDate, $endDate, $excludeId = null)
{
    $db = \App\Core\Database::getInstance()->getConnection();
    
    $sql = "
        SELECT COUNT(*) as count 
        FROM leaves 
        WHERE user_id = ? 
            AND status != 'rejected'
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )
    ";
    
    $params = [$userId, $endDate, $startDate, $startDate, $startDate, $startDate, $endDate];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch()['count'] > 0;
}

// ============================================
// TRAINER STATUS HELPERS
// ============================================

function getTrainerStatus($userId)
{
    $db = \App\Core\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT can_train FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['status' => 'unknown', 'label' => 'Unknown'];
    }
    
    if ($user['can_train'] == 1) {
        return ['status' => 'available', 'label' => '🟢 Available to Train'];
    }
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count, id, applicant_id 
        FROM trainees 
        WHERE trainer_id = ? AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $training = $stmt->fetch();
    
    if ($training && $training['count'] > 0) {
        return [
            'status' => 'locked', 
            'label' => '🔒 Currently Training',
            'trainee_id' => $training['id']
        ];
    }
    
    return ['status' => 'locked', 'label' => '🔒 Unavailable'];
}

// ============================================
// SHIFT HELPERS
// ============================================

function getShiftLabel($shift)
{
    $shifts = [
        'opening' => 'Opening (6:00 AM - 2:00 PM)',
        'closing' => 'Closing (2:00 PM - 10:00 PM)',
        'midshift' => 'MidShift (10:00 AM - 6:00 PM)'
    ];

    return $shifts[$shift] ?? ucfirst($shift);
}

function getShiftTime($shift)
{
    $times = [
        'opening' => ['06:00', '14:00'],
        'closing' => ['14:00', '22:00'],
        'midshift' => ['10:00', '18:00']
    ];

    return $times[$shift] ?? ['00:00', '00:00'];
}

/**
 * Calculate payroll for a single employee for a given period and create/update entry.
 * Returns array of totals.
 */
function calculateAndSavePayrollEntry($userId, $startDate, $endDate, $cycleId) {
    $db = \App\Core\Database::getInstance()->getConnection();

    // Get contract salary -- trainees don't run through payroll at all (see
    // create_payroll_cycle.php, which only calls this for non-trainee users).
    $stmt = $db->prepare("SELECT salary FROM contracts WHERE user_id = ? AND status = 'accepted' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $contract = $stmt->fetch();
    if (!$contract) return null;
    $monthlySalary = $contract['salary'];

    // Hourly rate off the standard full-time annualized conversion
    // (52 weeks x 40 hours = 2080 hours/year), not actual working days this
    // cutoff -- e.g. a contract salary of 1850/month is always 10.67/hour,
    // regardless of how many working days land in a given period.
    $hourlyRate = round(($monthlySalary * 12) / 2080, 4);
    $dailyRate = round($hourlyRate * 8, 2);

    // Get attendance summary. Rest days never count toward payroll at all
    // (not attended, not deducted) -- they're simply excluded from every
    // bucket below. Late/overtime are computed per-day further down from
    // actual clock times vs. the effective schedule, not from this summary.
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_days,
            SUM(CASE WHEN status IN ('present','late','leave_paid','holiday_work') THEN 1 ELSE 0 END) as attended_days,
            SUM(CASE WHEN status IN ('absent','leave_unpaid') THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'holiday_work' THEN 1 ELSE 0 END) as holiday_work_days
        FROM attendance
        WHERE user_id = ? AND date BETWEEN ? AND ?
    ");
    $stmt->execute([$userId, $startDate, $endDate]);
    $att = $stmt->fetch();

    $attendedDays = $att['attended_days'] ?? 0;
    $absentDays = $att['absent_days'] ?? 0;
    $holidayWorkDays = $att['holiday_work_days'] ?? 0;

    // Calculate working days (excluding rest days) -- reporting figure only
    // now (total_working_days column); no longer drives the rate itself.
    $stmt = $db->prepare("
        SELECT COUNT(*) as working_days
        FROM (
            SELECT DATE_ADD(?, INTERVAL n DAY) as date
            FROM (
                SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14
                UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
                UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24
                UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
                UNION SELECT 30 UNION SELECT 31
            ) nums
            WHERE DATE_ADD(?, INTERVAL n DAY) <= ?
        ) dates
        WHERE DAYOFWEEK(dates.date) NOT IN (1,7)
        AND NOT EXISTS (
            SELECT 1 FROM schedules s
            WHERE s.user_id = ? AND s.day_of_week = LOWER(DAYNAME(dates.date)) AND s.is_rest_day = 1
        )
    ");
    $stmt->execute([$startDate, $startDate, $endDate, $userId]);
    $workingDays = $stmt->fetchColumn() ?: 22;

    $regularPay = $attendedDays * $dailyRate;

    // Late minutes and overtime hours: computed per day directly from the
    // actual clock punch vs. that day's effective schedule (baseline +
    // any cutoff override), not from a manually-set status/estimate.
    $stmt = $db->prepare("SELECT date, time_in, time_out, status FROM attendance WHERE user_id = ? AND date BETWEEN ? AND ? AND status IN ('present','late','holiday_work')");
    $stmt->execute([$userId, $startDate, $endDate]);
    $workedRows = $stmt->fetchAll();

    $lateMinutes = 0;
    $overtimeMinutes = 0;
    if ($workedRows) {
        $scheduleModel = new \App\Models\Schedule();
        $scheduleCache = []; // period_key => day_of_week => schedule row
        foreach ($workedRows as $row) {
            $periodKey = \App\Core\CutoffPeriod::getKeyForDate($row['date']);
            if (!isset($scheduleCache[$periodKey])) {
                $byDay = [];
                foreach ($scheduleModel->getEffectiveSchedule($userId, $periodKey) as $s) {
                    $byDay[$s['day_of_week']] = $s;
                }
                $scheduleCache[$periodKey] = $byDay;
            }
            $dayOfWeek = strtolower(date('D', strtotime($row['date'])));
            $sched = $scheduleCache[$periodKey][$dayOfWeek] ?? null;
            if (!$sched || $sched['is_rest_day'] || !$sched['time_in'] || !$sched['time_out']) {
                continue; // no schedule to compare against -- can't be late or OT
            }

            if ($row['time_in']) {
                $scheduledIn = strtotime($row['date'] . ' ' . $sched['time_in']);
                $actualIn = strtotime($row['date'] . ' ' . $row['time_in']);
                if ($actualIn > $scheduledIn) {
                    $lateMinutes += round(($actualIn - $scheduledIn) / 60);
                }
            }
            if ($row['time_out']) {
                $scheduledOut = strtotime($row['date'] . ' ' . $sched['time_out']);
                $actualOut = strtotime($row['date'] . ' ' . $row['time_out']);
                if ($actualOut > $scheduledOut) {
                    $overtimeMinutes += round(($actualOut - $scheduledOut) / 60);
                }
            }
        }
    }
    $overtimeHours = round($overtimeMinutes / 60, 2);

    $overtimePay = $overtimeHours * $hourlyRate * 1.25;
    $holidayPay = $holidayWorkDays * 8 * $hourlyRate * 2.0;
    $lateDeduction = ($lateMinutes / 60) * $hourlyRate;

    // Absent / unpaid-leave days are already excluded from $attendedDays
    // above, so they simply earn nothing for that day (net zero) -- no
    // further deduction on top of that non-payment.
    $grossPay = $regularPay + $overtimePay + $holidayPay;
    $totalDeductions = $lateDeduction;
    $netPay = $grossPay - $totalDeductions;

    // Insert or update entry
    $entryModel = new \App\Models\PayrollEntry();
    $entryData = [
        'payroll_cycle_id' => $cycleId,
        'user_id' => $userId,
        'total_working_days' => $workingDays,
        'attended_days' => $attendedDays,
        'absent_days' => $absentDays,
        'total_overtime_hours' => $overtimeHours,
        'total_holiday_work_hours' => $holidayWorkDays * 8,
        'late_minutes' => $lateMinutes,
        'monthly_salary' => $monthlySalary,
        'daily_rate' => $dailyRate,
        'regular_pay' => $regularPay,
        'overtime_pay' => $overtimePay,
        'holiday_pay' => $holidayPay,
        'late_deduction' => $lateDeduction,
        'absent_deduction' => 0,
        'unpaid_leave_deduction' => 0,
        'other_deductions' => 0,
        'gross_pay' => $grossPay,
        'total_deductions' => $totalDeductions,
        'net_pay' => $netPay,
        'notes' => null
    ];
    $entryModel->create($entryData);
    return [
        'gross' => $grossPay,
        'deductions' => $totalDeductions,
        'net' => $netPay
    ];
}

// ============================================
// PAGINATION HELPER
// ============================================

function renderPagination($currentPage, $totalPages, $baseUrl, $queryParams = [])
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav><ul class="pagination pagination-sm justify-content-center">';

    if ($currentPage > 1) {
        $params = array_merge($queryParams, ['page' => $currentPage - 1]);
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">«</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">«</span></li>';
    }

    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $params = array_merge($queryParams, ['page' => 1]);
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $params = array_merge($queryParams, ['page' => $i]);
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">' . $i . '</a></li>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $params = array_merge($queryParams, ['page' => $totalPages]);
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">' . $totalPages . '</a></li>';
    }

    if ($currentPage < $totalPages) {
        $params = array_merge($queryParams, ['page' => $currentPage + 1]);
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($params) . '">»</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">»</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

// ============================================
// SESSION FLASH HELPERS
// ============================================

function setFlash($message, $type = 'info')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlash()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// ============================================
// VALIDATION HELPERS
// ============================================

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone)
{
    return preg_match('/^[0-9]{10,12}$/', $phone);
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = \DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateFileType($filename, $allowedTypes)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowedTypes);
}

/**
 * Validate an "expected delivery" date for a new/current requisition.
 * An empty value is treated as valid (the field is optional in the existing
 * requisition workflow); a non-empty value must be a real calendar date,
 * not before today, and not more than one year from today.
 *
 * "Today" is computed from the server's configured timezone (php.ini's
 * date.timezone), per this app's existing timezone configuration — not the
 * caller's browser time, which is untrusted for backend validation.
 *
 * Returns null when valid, or a user-facing error message string when invalid.
 */
function validateExpectedDeliveryDate($rawValue)
{
    $value = is_string($rawValue) ? trim($rawValue) : '';
    if ($value === '') {
        return null;
    }

    if (!validateDate($value)) {
        return 'Expected delivery date must be a valid date (YYYY-MM-DD).';
    }

    // createFromFormat('Y-m-d', ...) inherits the current time-of-day for any time
    // components not present in the format string (unlike new DateTime('today'),
    // which is always midnight) — zero it out so this is a pure date comparison.
    $date = \DateTime::createFromFormat('Y-m-d', $value);
    $date->setTime(0, 0, 0);
    $today = new \DateTime('today');
    $maxDate = (clone $today)->modify('+6 months');

    if ($date < $today) {
        return 'Expected delivery date cannot be earlier than today.';
    }
    if ($date > $maxDate) {
        return 'Expected delivery date cannot be more than 6 months from today.';
    }

    return null;
}

// =============================================

function getUsersByRole($role)
{
    $db = \App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT user_id, first_name, last_name, email FROM users WHERE role = ? AND is_active = 1");
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}