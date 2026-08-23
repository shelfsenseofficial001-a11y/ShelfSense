<?php
$title = 'Payment Requests - Finance Head';
$pageTitle = 'Approve Payments';
$activePage = 'head_payment_requests';
$additional_js = '<script src="/ShelfSense/public/assets/js/finance/head/payment_requests.js"></script>';

$content = <<<'EOT'
<!-- Tabs -->
<div class="row g-2 mb-3">
    <div class="col-md-12">
        <ul class="nav nav-tabs" id="approvalHistoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-approvals-tab" data-bs-toggle="tab" data-bs-target="#pendingApprovals" type="button" role="tab">
                    <i class="bi bi-clock-history"></i> Pending Approvals
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approval-history-tab" data-bs-toggle="tab" data-bs-target="#approvalHistory" type="button" role="tab">
                    <i class="bi bi-clock-history"></i> Approval History
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">
    <!-- ============================================ -->
    <!-- TAB 1: PENDING APPROVALS -->
    <!-- ============================================ -->
    <div class="tab-pane fade show active" id="pendingApprovals" role="tabpanel">
        <!-- Filters -->
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by requisition #...">
                </div>
            </div>
            <div class="col-md-2">
                <select id="filterStatus" class="form-select searchable-select" data-placeholder="Filter by status...">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-yellow-primary btn-sm w-100" id="applyFiltersBtn">
                    <i class="bi bi-funnel"></i] Apply Filters
                </button>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-yellow-outline btn-sm" id="refreshBtn">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-2 mb-3">
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

        <!-- Payment Requests Table -->
        <div class="modern-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Requisition #</th>
                                <th>Supplier</th>
                                <th>Requested By</th>
                                <th>Amount</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="requestsTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted">Loading requests...</p>
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
    </div>

    <!-- ============================================ -->
    <!-- TAB 2: APPROVAL HISTORY -->
    <!-- ============================================ -->
    <div class="tab-pane fade" id="approvalHistory" role="tabpanel">
        <div class="modern-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Requisition #</th>
                                <th>Supplier</th>
                                <th>Amount</th>
                                <th>Action</th>
                                <th>By</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="approvalHistoryBody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted">Loading history...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="historyTableInfo">Loading...</span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0" id="historyPaginationContainer">
                            <li class="page-item disabled"><span class="page-link">1</span></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve/Reject Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Approve Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="actionModalBody"></div>
                <div class="mb-3" id="reasonField">
                    <label class="form-label fw-semibold">Reason (for rejection)</label>
                    <textarea id="actionReason" class="form-control" rows="3" placeholder="Reason for rejection..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="confirmApproveBtn">Approve</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmRejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../../layouts/finance.php';