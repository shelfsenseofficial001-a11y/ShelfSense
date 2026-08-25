<?php
$title = 'Payroll - ShelfSense HR';
$pageTitle = 'Payroll Management';
$activePage = 'payroll';
$additional_js = '<script src="/ShelfSense/public/assets/js/hr/payroll.js"></script>';

$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date('Y-m-d');

// Month options
$monthOptions = '';
for ($m = 1; $m <= 12; $m++) {
    $val = str_pad($m, 2, '0', STR_PAD_LEFT);
    $label = date('F', mktime(0, 0, 0, $m, 1, $currentYear));
    $selected = ($m == date('m')) ? 'selected' : '';
    $monthOptions .= "<option value=\"$val\" $selected>$label</option>";
}

// Year options
$yearOptions = '';
$cy = date('Y');
for ($y = $cy - 1; $y <= $cy + 1; $y++) {
    $selected = ($y == $cy) ? 'selected' : '';
    $yearOptions .= "<option value=\"$y\" $selected>$y</option>";
}

$content = <<<HTML
<style>
    .payroll-stats-card { cursor: default; }
    .payroll-status-badge { font-size: 0.7rem; padding: 4px 10px; border-radius: 12px; }
    .payroll-status-badge.draft { background: #e5e7eb; color: #4b5563; }
    .payroll-status-badge.pending_approval { background: #fef3c7; color: #92400e; }
    .payroll-status-badge.approved { background: #dbeafe; color: #1e40af; }
    .payroll-status-badge.verified { background: #d1fae5; color: #065f46; }
    .payroll-status-badge.processed { background: #d1fae5; color: #065f46; }
    .payroll-status-badge.cancelled { background: #fecaca; color: #991b1b; }
    .action-btn { font-size: 0.7rem; padding: 2px 6px; }
    .payroll-amount { font-weight: 600; }
    .payroll-amount.positive { color: #059669; }
    .payroll-amount.negative { color: #dc2626; }
    .cycle-row:hover { background: var(--light-yellow-subtle); }
    .no-data { color: var(--text-muted); text-align: center; padding: 40px 0; }
    .no-data i { font-size: 3rem; display: block; margin-bottom: 16px; opacity: 0.5; }
</style>

<!-- Filters -->
<div class="alert alert-info" id="payrollReadyAlert" style="display:none;">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Payroll Ready for Review!</strong> 
    A draft payroll cycle has been auto‑generated. Please review and send to Finance.
</div>
<div class="row g-2 mb-3">
    <div class="col-md-2">
        <label class="form-label fw-semibold">Year</label>
        <select id="filterYear" class="form-select searchable-select">
            <option value="">All Years</option>
            $yearOptions
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Month</label>
        <select id="filterMonth" class="form-select searchable-select">
            <option value="">All Months</option>
            $monthOptions
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Status</label>
        <select id="filterStatus" class="form-select searchable-select">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="pending_approval">Pending Approval</option>
            <option value="approved">Approved</option>
            <option value="verified">Verified</option>
            <option value="processed">Processed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-yellow-primary btn-sm w-100" id="loadCyclesBtn">
            <i class="bi bi-refresh"></i> Load Cycles
        </button>
    </div>
    <div class="col-md-3 text-end d-flex align-items-end justify-content-end">
        <button class="btn btn-success btn-sm" id="createCycleBtn">
            <i class="bi bi-plus-circle"></i> Create New Cycle
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-2 mb-3" id="statsRow">
    <div class="col"><div class="modern-card p-2 text-center payroll-stats-card"><small class="text-muted">Total Cycles</small><h5 class="mb-0" id="statTotal">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center payroll-stats-card"><small class="text-muted">Draft</small><h5 class="mb-0 text-secondary" id="statDraft">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center payroll-stats-card"><small class="text-muted">Pending</small><h5 class="mb-0 text-warning" id="statPending">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center payroll-stats-card"><small class="text-muted">Approved</small><h5 class="mb-0 text-primary" id="statApproved">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center payroll-stats-card"><small class="text-muted">Verified</small><h5 class="mb-0 text-success" id="statVerified">0</h5></div></div>
    <div class="col"><div class="modern-card p-2 text-center payroll-stats-card"><small class="text-muted">Processed</small><h5 class="mb-0 text-info" id="statProcessed">0</h5></div></div>
</div>

<!-- Cycles Table -->
<div class="modern-card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cash-coin me-2"></i> Payroll Cycles</span>
            <span class="text-muted small" id="tableInfo">Loading...</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="min-width:140px;">Cycle Name</th>
                        <th>Period</th>
                        <th>Payment Date</th>
                        <th>Employees</th>
                        <th>Gross Pay</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th class="text-center" style="min-width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="cyclesTableBody">
                    <tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading payroll cycles...</p></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="tableCount">0 cycles</span>
            <button class="btn btn-sm btn-outline-secondary" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
        </div>
    </div>
</div>

<!-- Create Cycle Modal -->
<div class="modal fade" id="createCycleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Payroll Cycle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createCycleForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Month</label>
                        <select name="month" id="cycleMonth" class="form-select" required>
                            $monthOptions
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Year</label>
                        <select name="year" id="cycleYear" class="form-select" required>
                            $yearOptions
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Half</label>
                        <select name="half" id="cycleHalf" class="form-select" required>
                            <option value="1">1st Half (1-15/16)</option>
                            <option value="2">2nd Half (16/17-end)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date" name="payment_date" id="cyclePaymentDate" class="form-control" min="$currentDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" id="cycleNotes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                    <div class="alert alert-info" id="cyclePreview">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Period: <span id="previewDates">Select month and half to preview</span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-yellow-primary btn-sm">Generate Payroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Entries Modal -->
<div class="modal fade" id="entriesModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payroll Entries</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="entriesBody"><div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <a href="#" id="exportCsvBtn" class="btn btn-success btn-sm" target="_blank"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
            </div>
        </div>
    </div>
</div>

<!-- Approval Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Approval Logs</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="logsBody"><div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- Reject Reason Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Payroll</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rejection Reason</label>
                    <textarea id="rejectReason" class="form-control" rows="3" placeholder="Please provide a reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmRejectBtn">Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
const STATUS_LABELS = {
    'draft': 'Draft',
    'pending_approval': 'Pending Approval',
    'approved': 'Approved',
    'verified': 'Verified',
    'processed': 'Processed',
    'cancelled': 'Cancelled'
};
const STATUS_CLASSES = {
    'draft': 'draft',
    'pending_approval': 'pending_approval',
    'approved': 'approved',
    'verified': 'verified',
    'processed': 'processed',
    'cancelled': 'cancelled'
};
</script>

HTML;

require_once __DIR__ . '/../../layouts/hr.php';