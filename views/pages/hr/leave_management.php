<?php
// views/pages/hr/leave_management.php

use App\Core\Auth;
use App\Core\Response;

$title = 'Manage Leaves - ShelfSense HR';
$pageTitle = 'Manage Leaves';
$activePage = 'leave_management';

// Only HR Head/Owner can access this page
if (!Auth::isHRHead() && !Auth::isOwner()) {
    Response::redirect('?page=dashboard');
    exit;
}

// We're in HR layout
$additional_js = '<script src="/ShelfSense/public/assets/js/hr/leave_management.js?v=20260829181326"></script>';
$additional_css = '
<style>
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
    .leave-type-badge.other { background: #f3e8ff; color: #6d28d9; }
    [data-bs-theme="dark"] .leave-type-badge.sick { background: #1e3a5f; color: #93c5fd; }
    [data-bs-theme="dark"] .leave-type-badge.vacation { background: #064e3b; color: #6ee7b7; }
    [data-bs-theme="dark"] .leave-type-badge.emergency { background: #78350f; color: #fcd34d; }
    [data-bs-theme="dark"] .leave-type-badge.other { background: #3b1e5f; color: #c4b5fd; }
    .leave-row:hover {
        background: var(--light-yellow-subtle);
        cursor: pointer;
    }
    .pending-badge {
        animation: pulse-badge 2s infinite;
    }
    @keyframes pulse-badge {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }
</style>
';

$content = <<<'EOT'
<!-- Stats -->
<div class="row g-2 mb-3">
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Total Requests</small>
            <h5 class="mb-0" id="statTotal">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Pending</small>
            <h5 class="mb-0 text-warning" id="statPending">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Approved</small>
            <h5 class="mb-0 text-success" id="statApproved">0</h5>
        </div>
    </div>
    <div class="col">
        <div class="modern-card p-2 text-center">
            <small class="text-muted">Rejected</small>
            <h5 class="mb-0 text-danger" id="statRejected">0</h5>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Search by employee...">
        </div>
    </div>
    <div class="col-md-2">
        <select id="filterStatus" class="form-select searchable-select" data-placeholder="Filter by status...">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
    <div class="col-md-2">
        <select id="filterLeaveType" class="form-select searchable-select" data-placeholder="Filter by type...">
            <option value="">All Types</option>
            <option value="sick">Sick Leave</option>
            <option value="vacation">Vacation Leave</option>
            <option value="emergency">Emergency Leave</option>
            <option value="maternity">Maternity Leave</option>
            <option value="other">Other Leave</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-yellow-primary btn-sm w-100" id="applyFiltersBtn">
            <i class="bi bi-funnel"></i> Apply Filters
        </button>
    </div>
    <div class="col-md-2 text-end">
        <button class="btn btn-yellow-outline btn-sm w-100" id="refreshBtn">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<div class="active-filter-chips" id="activeFilterChips"></div>

<!-- Leave Requests Table -->
<div class="modern-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="leaveTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-4">
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
                <button type="button" class="btn btn-success btn-sm" id="approveLeaveBtn" style="display:none;">
                    <i class="bi bi-check-circle"></i> Approve
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="rejectLeaveBtn" style="display:none;">
                    <i class="bi bi-x-circle"></i> Reject
                </button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/hr.php';