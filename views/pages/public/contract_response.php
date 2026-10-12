<?php
// views/pages/public/contract_response.php
// Public, no-login landing page linked from the contract offer email. Shows
// the terms and lets the applicant/trainee accept or decline directly --
// expireOverdueContracts() runs first so a stale "pending" row past its
// deadline never shows live buttons that would just fail when clicked.

require_once __DIR__ . '/../../../app/core/Database.php';
require_once __DIR__ . '/../../../app/helpers/functions.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();
expireOverdueContracts($db);

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$contract = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $db->prepare("
        SELECT c.*, a.first_name AS applicant_first_name, a.last_name AS applicant_last_name, a.target_role
        FROM contracts c
        JOIN applicants a ON c.applicant_id = a.id
        WHERE c.response_token = ?
    ");
    $stmt->execute([$token]);
    $contract = $stmt->fetch();
}

$isTrainee = $contract && $contract['contract_type'] === 'trainee';
$contractLabel = $isTrainee ? 'Trainee Contract' : 'Hired Contract';

$shiftLabels = [
    'opening' => 'Opening (6am-2pm)',
    'closing' => 'Closing (2pm-10pm)',
    'midshift' => 'MidShift (10am-6pm)'
];

// target_role stores the internal role name the job posting was filed under
// (e.g. "Employee"), not always what it should read as to the applicant --
// same "Employee" -> "Cashier" convention used in the HR applicants filter.
$targetRoleLabels = [
    'Employee' => 'Cashier',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($contractLabel) ?> - ShelfSense</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; background: #111827; color: #e5e7eb; margin: 0; padding: 24px 16px; }
    .card { max-width: 560px; margin: 0 auto; background: #1f2937; border-radius: 12px; overflow: hidden; border: 1px solid #374151; }
    .card-header { background: #facc15; color: #1a1a1a; padding: 20px; text-align: center; }
    .card-header h1 { margin: 0; font-size: 1.25rem; }
    .card-body { padding: 24px; }
    .terms { list-style: none; margin: 16px 0; padding: 0; }
    .terms li { padding: 10px 0; border-bottom: 1px solid #374151; }
    .terms li:last-child { border-bottom: none; }
    .terms .label { color: #9ca3af; font-size: 0.8rem; display: block; }
    .deadline { background: #78350f; color: #fcd34d; padding: 14px; border-radius: 8px; margin: 16px 0; font-size: 0.9rem; }
    .btn-row { display: flex; gap: 10px; margin-top: 20px; }
    button { flex: 1; padding: 12px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.95rem; cursor: pointer; }
    .btn-accept { background: #16a34a; color: #fff; }
    .btn-decline { background: transparent; color: #f87171; border: 1px solid #f87171; }
    button:disabled { opacity: 0.5; cursor: not-allowed; }
    .notes-wrap { display: none; margin-top: 14px; }
    textarea { width: 100%; background: #111827; border: 1px solid #374151; color: #e5e7eb; border-radius: 6px; padding: 8px; font-family: inherit; }
    .status-banner { text-align: center; padding: 20px; border-radius: 8px; margin-top: 10px; font-weight: 600; }
    .status-banner.accepted { background: #064e3b; color: #6ee7b7; }
    .status-banner.declined { background: #450a0a; color: #fca5a5; }
    .error-box { text-align: center; padding: 30px 20px; color: #9ca3af; }
</style>
</head>
<body>
<div class="card">
<?php if (!$contract): ?>
    <div class="card-header"><h1>ShelfSense</h1></div>
    <div class="card-body error-box">
        <p>This link is invalid, has already been used, or has expired.</p>
        <p>Please contact HR if you believe this is a mistake.</p>
    </div>
<?php else: ?>
    <div class="card-header"><h1>Your <?= htmlspecialchars($contractLabel) ?></h1></div>
    <div class="card-body">
        <p>Dear <strong><?= htmlspecialchars($contract['applicant_first_name']) ?></strong>,</p>

        <?php if ($contract['status'] !== 'pending'): ?>
            <div class="status-banner <?= $contract['status'] === 'accepted' ? 'accepted' : 'declined' ?>">
                <?php if ($contract['status'] === 'accepted'): ?>
                    ✅ You already accepted this <?= htmlspecialchars($contractLabel) ?>.
                <?php else: ?>
                    This <?= htmlspecialchars($contractLabel) ?> was declined<?= $contract['response_notes'] === 'Automatically declined: decision deadline passed.' ? ' (decision deadline passed).' : '.' ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Please review the terms below:</p>
            <ul class="terms">
                <li><span class="label">Role</span><?= htmlspecialchars($targetRoleLabels[$contract['target_role']] ?? $contract['target_role']) ?></li>
                <?php if ($isTrainee): ?>
                    <li><span class="label">Salary Range (indicative, paid hourly)</span>₱<?= number_format($contract['salary_range_min'], 2) ?> – ₱<?= number_format($contract['salary_range_max'], 2) ?></li>
                    <li><span class="label">Working Hours</span><?= date('g:i A', strtotime($contract['schedule_start'])) ?> – <?= date('g:i A', strtotime($contract['schedule_end'])) ?></li>
                <?php else: ?>
                    <li><span class="label">Shift</span><?= htmlspecialchars($shiftLabels[$contract['shift']] ?? $contract['shift']) ?></li>
                    <li><span class="label">Salary Range</span>₱<?= number_format($contract['salary_range_min'], 2) ?> – ₱<?= number_format($contract['salary_range_max'], 2) ?></li>
                    <li><span class="label">Start Date</span><?= date('F j, Y', strtotime($contract['start_date'])) ?></li>
                <?php endif; ?>
                <?php if (!empty($contract['rest_days'])): ?>
                    <li><span class="label">Rest Days</span><?= htmlspecialchars(ucwords(str_replace(',', ', ', $contract['rest_days']))) ?></li>
                <?php endif; ?>
                <?php if (!empty($contract['job_details'])): ?>
                    <li><span class="label">HR Notes</span><?= nl2br(htmlspecialchars($contract['job_details'])) ?></li>
                <?php endif; ?>
            </ul>

            <?php if (!empty($contract['decision_deadline'])): ?>
                <div class="deadline">⏰ Respond by <strong><?= date('F j, Y', strtotime($contract['decision_deadline'])) ?></strong> -- after this date the offer is automatically declined.</div>
            <?php endif; ?>

            <div id="resultArea"></div>
            <div class="btn-row" id="actionButtons">
                <button class="btn-accept" id="acceptBtn">Accept</button>
                <button class="btn-decline" id="declineBtn">Decline</button>
            </div>
            <div class="notes-wrap" id="notesWrap">
                <textarea id="declineNotes" rows="2" maxlength="500" placeholder="Optional reason for declining..."></textarea>
                <div class="btn-row">
                    <button class="btn-decline" id="confirmDeclineBtn">Confirm Decline</button>
                    <button id="cancelDeclineBtn" style="background:#374151;color:#e5e7eb;">Cancel</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>

<script>
const token = <?= json_encode($token) ?>;

function submitResponse(action, notes) {
    const buttons = document.querySelectorAll('button');
    buttons.forEach(b => b.disabled = true);
    fetch('?page=api_public_contract_response', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, action, response_notes: notes || '' })
    })
    .then(r => r.json())
    .then(result => {
        const area = document.getElementById('resultArea');
        document.getElementById('actionButtons').style.display = 'none';
        document.getElementById('notesWrap').style.display = 'none';
        area.innerHTML = `<div class="status-banner ${result.success && action === 'accept' ? 'accepted' : 'declined'}">${result.message}</div>`;
    })
    .catch(() => {
        buttons.forEach(b => b.disabled = false);
        document.getElementById('resultArea').innerHTML = `<div class="status-banner declined">Something went wrong. Please try again.</div>`;
    });
}

document.getElementById('acceptBtn')?.addEventListener('click', function() {
    if (!confirm('Accept this contract?')) return;
    submitResponse('accept', '');
});
document.getElementById('declineBtn')?.addEventListener('click', function() {
    document.getElementById('notesWrap').style.display = 'block';
    document.getElementById('actionButtons').style.display = 'none';
});
document.getElementById('cancelDeclineBtn')?.addEventListener('click', function() {
    document.getElementById('notesWrap').style.display = 'none';
    document.getElementById('actionButtons').style.display = 'flex';
});
document.getElementById('confirmDeclineBtn')?.addEventListener('click', function() {
    submitResponse('decline', document.getElementById('declineNotes').value.trim());
});
</script>
</body>
</html>
