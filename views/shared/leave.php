<?php
// views/shared/leave.php

$title = 'My Leaves - ShelfSense';
$pageTitle = 'My Leaves';
$activePage = 'my_leaves';

// Auto-detect layout
$role = $_SESSION['role'] ?? 'cashier';
$layout = 'cashier';
if (in_array($role, ['hr_head', 'hr_staff', 'owner'])) {
    $layout = 'hr';
} elseif ($role === 'store_manager') {
    $layout = 'store_manager';
} elseif (in_array($role, ['finance_head', 'finance_staff'])) {
    $layout = 'finance';
} elseif ($role === 'supplier') {
    $layout = 'supplier';
} elseif ($role === 'trainee') {
    $layout = 'trainee';
}

$additional_js = '<script src="/ShelfSense/public/assets/js/leave.js"></script>';
$additional_css = '
<style>
    .leave-balance-card {
        padding: 16px;
        border-radius: 12px;
        background: var(--bg-card-subtle);
        border: 1px solid var(--border-color);
        text-align: center;
    }
    .leave-balance-card .balance-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--brand-yellow-hover);
    }
    .leave-balance-card .balance-label {
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .leave-balance-card .balance-used {
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .leave-balance-card .balance-remaining {
        font-size: 0.75rem;
        font-weight: 600;
    }
    .leave-balance-card .balance-remaining.positive {
        color: #059669;
    }
    .leave-balance-card .balance-remaining.low {
        color: #dc2626;
    }
    .leave-status-pending { color: #d97706; }
    .leave-status-approved { color: #059669; }
    .leave-status-rejected { color: #dc2626; }
    .leave-type-badge {
        font-size: 0.7rem;
        padding: 2px 10px;
        border-radius: 12px;
    }
    .leave-type-badge.sick { background: #dbeafe; color: #1e40af; }
    .leave-type-badge.vacation { background: #d1fae5; color: #065f46; }
    .leave-type-badge.emergency { background: #fef3c7; color: #92400e; }
    .leave-type-badge.maternity { background: #fce7f3; color: #9d174d; }
    .leave-type-badge.other { background: #f3e8ff; color: #6d28d9; }
    [data-bs-theme="dark"] .leave-type-badge.sick { background: #1e3a5f; color: #93c5fd; }
    [data-bs-theme="dark"] .leave-type-badge.vacation { background: #064e3b; color: #6ee7b7; }
    [data-bs-theme="dark"] .leave-type-badge.emergency { background: #78350f; color: #fcd34d; }
    [data-bs-theme="dark"] .leave-type-badge.maternity { background: #831843; color: #f9a8d4; }
    [data-bs-theme="dark"] .leave-type-badge.other { background: #3b1e5f; color: #c4b5fd; }
    .leave-row:hover {
        background: var(--light-yellow-subtle);
        cursor: pointer;
    }
</style>
';

$content = <<<'EOT'
<!-- Leave Balances -->
<div class="row g-3 mb-4" id="leaveBalances">
    <div class="col-6 col-md-3">
        <div class="leave-balance-card">
            <div class="balance-label">Sick Leave</div>
            <div class="balance-number" id="balanceSick">0</div>
            <div class="balance-used">Used: <span id="usedSick">0</span></div>
            <div class="balance-remaining" id="remainingSick">0 remaining</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="leave-balance-card">
            <div class="balance-label">Vacation Leave</div>
            <div class="balance-number" id="balanceVacation">0</div>
            <div class="balance-used">Used: <span id="usedVacation">0</span></div>
            <div class="balance-remaining" id="remainingVacation">0 remaining</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="leave-balance-card">
            <div class="balance-label">Emergency Leave</div>
            <div class="balance-number" id="balanceEmergency">0</div>
            <div class="balance-used">Used: <span id="usedEmergency">0</span></div>
            <div class="balance-remaining" id="remainingEmergency">0 remaining</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="leave-balance-card">
            <div class="balance-label">Maternity Leave</div>
            <div class="balance-number" id="balanceMaternity">0</div>
            <div class="balance-used">Used: <span id="usedMaternity">0</span></div>
            <div class="balance-remaining" id="remainingMaternity">0 remaining</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="leave-balance-card">
            <div class="balance-label">Other Leave</div>
            <div class="balance-number" id="balanceOther">0</div>
            <div class="balance-used">Used: <span id="usedOther">0</span></div>
            <div class="balance-remaining" id="remainingOther">0 remaining</div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="modern-card p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button class="btn btn-yellow-primary btn-sm" id="applyLeaveBtn">
                    <i class="bi bi-plus-circle"></i> Apply for Leave
                </button>
                <div class="ms-auto">
                    <span class="text-muted small" id="leaveTableInfo">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leave History -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="leaveTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading leave requests...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="tableInfo">Loading...</span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                    <li class="page-item disabled"><span class="page-link">1</span></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply for Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="applyLeaveForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Leave Type</label>
                        <select name="leave_type" id="leaveType" class="form-select" required>
                            <option value="">Select leave type...</option>
                            <option value="sick">Sick Leave</option>
                            <option value="vacation">Vacation Leave</option>
                            <option value="emergency">Emergency Leave</option>
                            <option value="maternity">Maternity Leave</option>
                            <option value="other">Other Leave</option>
                        </select>
                        <div class="form-text" id="leaveBalanceHint">Balance: 0 days</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" name="start_date" id="leaveStartDate" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Date</label>
                            <input type="date" name="end_date" id="leaveEndDate" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-muted small">Duration: <strong id="leaveDuration">0</strong> day(s)</span>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Reason</label>
                        <textarea name="reason" id="leaveReason" class="form-control" rows="3" maxlength="500" placeholder="Reason for leave..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Leave Detail Modal -->
<div class="modal fade" id="leaveDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Leave Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="leaveDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
EOT;

// Load the appropriate layout
if ($layout === 'hr') {
    require_once __DIR__ . '/../layouts/hr.php';
} elseif ($layout === 'store_manager') {
    require_once __DIR__ . '/../layouts/store_manager.php';
} elseif ($layout === 'finance') {
    require_once __DIR__ . '/../layouts/finance.php';
} elseif ($layout === 'supplier') {
    require_once __DIR__ . '/../layouts/supplier.php';
} elseif ($layout === 'trainee') {
    require_once __DIR__ . '/../layouts/trainee.php';
} else {
    require_once __DIR__ . '/../layouts/cashier.php';
}