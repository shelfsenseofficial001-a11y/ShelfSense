<?php
use App\Core\Auth;
use App\Models\Register;

define('SHELFSENSE_INTERNAL_INCLUDE', true);
require_once __DIR__ . '/../../../app/handlers/store_manager/get_register_status.php';
$initialData = sm_register_status_build_data(new Register(), Auth::userId());
$initialDataJson = json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$title = 'Register Budget - Store Manager';
$pageTitle = 'Register Budget';
$activePage = 'budget';
$additional_js = '<script src="/ShelfSense/public/assets/js/store_manager/budget.js?v=20260908600000"></script>';

$content = '<script>window.__INITIAL_DATA__ = ' . $initialDataJson . ';</script>' . <<<'EOT'
<div id="smBudgetContent">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Loading register status...</p>
    </div>
</div>
EOT;

require_once __DIR__ . '/../../layouts/store_manager.php';
