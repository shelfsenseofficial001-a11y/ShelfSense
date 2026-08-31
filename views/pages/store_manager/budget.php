<?php
$title = 'Register Budget - Store Manager';
$pageTitle = 'Register Budget';
$activePage = 'budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/budget.js?v=20260831440000"></script>';

$content = <<<'EOT'
<div id="smBudgetContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading register status...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
