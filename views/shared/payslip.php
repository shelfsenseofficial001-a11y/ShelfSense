<?php
// views/shared/payslip.php

$title = 'My Payslip - ShelfSense';
$pageTitle = 'My Payslip';

// ✅ Detect which layout to use
$role = $_SESSION['role'] ?? 'cashier';
$layout = 'cashier';
if (in_array($role, ['hr_head', 'hr_staff', 'owner'])) {
    $layout = 'hr';
}
$activePage = 'payslip';

$additional_js = '<script src="/ShelfSense/public/assets/js/payslip.js"></script>';
$additional_css = '
<style>
    .payslip-card { cursor: pointer; transition: all 0.2s ease; border-left: 4px solid var(--brand-yellow); }
    .payslip-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .payslip-amount { font-weight: 600; }
    .payslip-amount.positive { color: #059669; }
    .payslip-amount.negative { color: #dc2626; }
    .payslip-detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border-color); }
    .payslip-detail-row:last-child { border-bottom: none; }
    .payslip-detail-row .label { color: var(--text-muted); }
    .payslip-detail-row .value { font-weight: 500; }
    .payslip-total-row { border-top: 2px solid var(--brand-yellow); padding-top: 10px; margin-top: 10px; font-weight: 700; font-size: 1.1rem; }
    .payslip-status-badge { font-size: 0.7rem; padding: 2px 10px; border-radius: 12px; }
    .payslip-status-badge.approved { background: #d1fae5; color: #065f46; }
    .payslip-status-badge.pending { background: #fef3c7; color: #92400e; }
    .payslip-status-badge.verified { background: #dbeafe; color: #1e40af; }
    .payslip-status-badge.processed { background: #d1fae5; color: #065f46; }
    .payslip-status-badge.draft { background: #e5e7eb; color: #4b5563; }
    .payslip-status-badge.cancelled { background: #fecaca; color: #991b1b; }
    [data-bs-theme="dark"] .payslip-status-badge.approved { background: #064e3b; color: #6ee7b7; }
    [data-bs-theme="dark"] .payslip-status-badge.pending { background: #78350f; color: #fcd34d; }
    [data-bs-theme="dark"] .payslip-status-badge.verified { background: #1e3a5f; color: #93c5fd; }
    [data-bs-theme="dark"] .payslip-status-badge.processed { background: #064e3b; color: #6ee7b7; }
    [data-bs-theme="dark"] .payslip-status-badge.draft { background: #374151; color: #9ca3af; }
    [data-bs-theme="dark"] .payslip-status-badge.cancelled { background: #7f1d1d; color: #fca5a5; }
</style>
';

$content = <<<'EOT'
<!-- Stats -->
<div class="row g-2 mb-3">
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Total Payslips</small>
            <h5 class="mb-0" id="statTotal">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Total Earnings</small>
            <h5 class="mb-0 text-success" id="statTotalEarnings">₱0.00</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Latest Net Pay</small>
            <h5 class="mb-0 text-primary" id="statLatestNet">₱0.00</h5>
        </div>
    </div>
</div>

<!-- Payslips List -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Pay Period</th>
                        <th>Payment Date</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="payslipsTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading payslips...</p>
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

<!-- Payslip Detail Modal -->
<div class="modal fade" id="payslipDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payslip Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="payslipDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="printPayslipBtn" style="display:none;">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>
EOT;

// Load the appropriate layout
if ($layout === 'hr') {
    require_once __DIR__ . '/../layouts/hr.php';
} else {
    require_once __DIR__ . '/../layouts/cashier.php';
}