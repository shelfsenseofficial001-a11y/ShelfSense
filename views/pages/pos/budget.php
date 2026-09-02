<?php
$title = 'Budget - ShelfSense POS';
$pageTitle = 'Budget';
$activePage = 'budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/pos/budget.js?v=20260902020000"></script>';

$content = <<<'EOT'
<div id="posBudgetContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading budget...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/pos_terminal.php';
