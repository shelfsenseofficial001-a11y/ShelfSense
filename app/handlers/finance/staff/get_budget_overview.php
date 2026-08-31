<?php
// app/handlers/finance/staff/get_budget_overview.php
// Read-only budget view for Finance Staff. Budget ALLOCATION is managed by Finance Head
// (see app/handlers/finance/head/set_budget.php) — Finance Staff can see the numbers
// used to evaluate payment requests, but cannot create or edit allocations here.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../models/Budget.php';
require_once __DIR__ . '/../../../core/CutoffPeriod.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\CutoffPeriod;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login');
}

if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Staff role required.');
}

$monthYear = isset($_GET['month']) ? trim($_GET['month']) : CutoffPeriod::getCurrentKey();
if (!preg_match('/^\d{4}-\d{2}-H[12]$/', $monthYear)) {
    $monthYear = CutoffPeriod::getCurrentKey();
}

try {
    $db = Database::getInstance()->getConnection();
    $budgetModel = new Budget();

    // Departments with an allocation this month, plus any department that has
    // requisitions this month but no allocation yet (so "No budget allocated" is
    // truthfully surfaced instead of being silently omitted from the view).
    $stmt = $db->prepare("SELECT DISTINCT department FROM budgets WHERE month_year = ?");
    $stmt->execute([$monthYear]);
    $departments = array_column($stmt->fetchAll(), 'department');

    $stmt = $db->prepare("SELECT DISTINCT department FROM store_requisitions WHERE budget_month_year = ?");
    $stmt->execute([$monthYear]);
    foreach ($stmt->fetchAll() as $row) {
        if (!in_array($row['department'], $departments, true)) {
            $departments[] = $row['department'];
        }
    }
    sort($departments);

    $budgets = [];
    foreach ($departments as $dept) {
        $budgets[] = $budgetModel->getBudgetStatus($dept, $monthYear);
    }

    Response::success([
        'month_year' => $monthYear,
        'budgets' => $budgets,
        'generated_at' => date('Y-m-d H:i:s')
    ], 'Budget overview fetched');

} catch (Exception $e) {
    error_log('get_budget_overview.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
