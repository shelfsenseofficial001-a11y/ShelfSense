<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../vendor/autoload.php';   

// Start session
session_start();

// ============================================
// AUTOLOADER - MUST BE FIRST
// ============================================
spl_autoload_register(function ($class) {
    // Core classes: App\Core\Database, App\Core\Auth, etc.
    if (strpos($class, 'App\\Core\\') === 0) {
        $file = __DIR__ . '/../app/core/' . substr($class, 9) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    
    // Model classes: App\Models\Applicant, App\Models\Interview, etc.
    if (strpos($class, 'App\\Models\\') === 0) {
        $file = __DIR__ . '/../app/models/' . substr($class, 10) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    
    // Handler classes: App\Handlers\*, etc.
    if (strpos($class, 'App\\Handlers\\') === 0) {
        $file = __DIR__ . '/../app/handlers/' . substr($class, 12) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load helpers
require_once __DIR__ . '/../app/helpers/functions.php';

// Load core classes (in case autoloader fails)
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Response.php';

use App\Core\Auth;
use App\Core\Response;

// Get the 'page' parameter from URL
$page = isset($_GET['page']) ? $_GET['page'] : 'home';  

// ============================================
// ROUTE: LOGOUT (Always runs first)
// ============================================

if ($page === 'logout') {
    Auth::logout();
    Response::redirect('?page=home');
    exit;
}

// ============================================
// DASHBOARD REDIRECT
// ============================================

if ($page === 'dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    
    $role = Auth::role();
    switch ($role) {
        case 'owner':
        case 'hr_head':
        case 'hr_staff':
            Response::redirect('?page=hr_dashboard');
            break;
        case 'employee':
            Response::redirect('?page=pos_dashboard');
            break;
        case 'trainee':
            Response::redirect('?page=trainee_dashboard');
            break;
        case 'store_manager':
            Response::redirect('?page=store_manager_dashboard');
            break;
        case 'supplier':
            Response::redirect('?page=supplier_dashboard');
            break;
        case 'finance_head':
            Response::redirect('?page=finance_head_dashboard');
            break;
        case 'finance_staff':
            Response::redirect('?page=finance_staff_dashboard');
            break;
        default:
            Response::redirect('?page=home');
    }
    exit;
}

// ============================================
// API ROUTES (MUST come before HTML routes)
// ============================================

if ($page === 'api_login') {
    require_once __DIR__ . '/../app/handlers/api_login.php';
    exit;
}

if ($page === 'api_apply') {
    require_once __DIR__ . '/../app/handlers/api_apply.php';
    exit;
}

// ============================================
// HR API ROUTES
// ============================================

if ($page === 'api_get_applicants') {
    require_once __DIR__ . '/../app/handlers/hr/get_applicants.php';
    exit;
}

if ($page === 'api_get_applicant') {
    require_once __DIR__ . '/../app/handlers/hr/get_applicant.php';
    exit;
}

if ($page === 'api_update_status') {
    require_once __DIR__ . '/../app/handlers/hr/update_status.php';
    exit;
}

if ($page === 'api_schedule_interview') {
    require_once __DIR__ . '/../app/handlers/hr/schedule_interview.php';
    exit;
}

if ($page === 'api_get_dashboard_stats') {
    require_once __DIR__ . '/../app/handlers/hr/get_dashboard_stats.php';
    exit;
}

if ($page === 'api_get_trainers_by_role') {
    require_once __DIR__ . '/../app/handlers/hr/get_trainers_by_role.php';
    exit;
}

if ($page === 'api_create_trainee_with_trainer') {
    require_once __DIR__ . '/../app/handlers/hr/create_trainee_with_trainer.php';
    exit;
}

// ============================================
// HR PAGE ROUTES (Require authentication)
// ============================================

if ($page === 'hr_dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    $targetRole = Auth::getNormalizedTargetRole();
    $isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);
    if (!Auth::isHR() && !Auth::isOwner() && !$isHrTrainee) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/dashboard.php';
    exit;
}

if ($page === 'hr_applicants') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (Auth::isTrainee()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    if (!Auth::isHR() && !Auth::isOwner()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/applicants.php';
    exit;
}

if ($page === 'hr_job_postings') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isHR() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/job_postings.php';
    exit;
}

// ============================================
// HR JOB POSTINGS API ROUTES
// ============================================

if ($page === 'api_hr_get_job_postings') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/get_job_postings.php';
    exit;
}
if ($page === 'api_hr_get_job_posting') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/get_job_posting.php';
    exit;
}
if ($page === 'api_hr_create_job_posting') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/create_job_posting.php';
    exit;
}
if ($page === 'api_hr_update_job_posting') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/update_job_posting.php';
    exit;
}
if ($page === 'api_hr_submit_job_posting') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/submit_job_posting.php';
    exit;
}
if ($page === 'api_hr_review_job_posting') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/review_job_posting.php';
    exit;
}
if ($page === 'api_hr_archive_job_posting') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/archive_job_posting.php';
    exit;
}
if ($page === 'api_hr_reuse_job_posting') {
    require_once __DIR__ . '/../app/handlers/hr/job_postings/reuse_job_posting.php';
    exit;
}

// ============================================
// PUBLIC HTML ROUTES
// ============================================

if ($page === 'home' || $page === '') {
    require_once __DIR__ . '/../views/pages/landing.php';
    exit;
}

if ($page === 'login') {
    if (Auth::check()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/auth/login.php';
    exit;
}

if ($page === 'apply') {
    require_once __DIR__ . '/../views/pages/auth/apply.php';
    exit;
}

if ($page === 'test') {
    require_once __DIR__ . '/../test.php';
    exit;
}

if ($page === 'debug_api') {
    require_once __DIR__ . '/../public/debug_api.php';
    exit;
}

// ============================================
// HR INTERVIEWS ROUTES
// ============================================

if ($page === 'hr_interviews') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (Auth::isTrainee()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    if (!Auth::isHR() && !Auth::isOwner()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/interviews.php';
    exit;
}

if ($page === 'api_get_interviews') {
    require_once __DIR__ . '/../app/handlers/hr/get_interviews.php';
    exit;
}

if ($page === 'api_update_interview') {
    require_once __DIR__ . '/../app/handlers/hr/update_interview.php';
    exit;
}

// ============================================
// HR TRAINEES ROUTES
// ============================================

if ($page === 'hr_trainees') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (Auth::isTrainee()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    if (!Auth::isHR() && !Auth::isOwner()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/trainees.php';
    exit;
}

if ($page === 'api_get_trainees') {
    require_once __DIR__ . '/../app/handlers/hr/get_trainees.php';
    exit;
}

if ($page === 'api_update_trainee') {
    require_once __DIR__ . '/../app/handlers/hr/update_trainee.php';
    exit;
}

// api_submit_report / api_review_reports (monthly report_1/2/3 model) are kept
// routed for backward compatibility with any historical data/links, but the
// UI now uses the weekly, department-routed trainee_reports workflow below.
if ($page === 'api_submit_report') {
    require_once __DIR__ . '/../app/handlers/hr/submit_report.php';
    exit;
}

if ($page === 'api_review_reports') {
    require_once __DIR__ . '/../app/handlers/hr/review_reports.php';
    exit;
}

// ============================================
// WEEKLY TRAINEE REPORTS (department-routed)
// ============================================

if ($page === 'api_trainee_submit_report') {
    require_once __DIR__ . '/../app/handlers/hr/trainee_reports/submit_report.php';
    exit;
}
if ($page === 'api_trainee_add_observation') {
    require_once __DIR__ . '/../app/handlers/hr/trainee_reports/add_observation.php';
    exit;
}
if ($page === 'api_trainee_get_reports') {
    require_once __DIR__ . '/../app/handlers/hr/trainee_reports/get_reports.php';
    exit;
}
if ($page === 'api_trainee_hr_head_review_report') {
    require_once __DIR__ . '/../app/handlers/hr/trainee_reports/hr_head_review_report.php';
    exit;
}
if ($page === 'api_trainee_respond_to_contract') {
    require_once __DIR__ . '/../app/handlers/trainee/respond_to_contract.php';
    exit;
}

// ============================================
// HR CONTRACTS ROUTES
// ============================================

if ($page === 'hr_contracts') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (Auth::isTrainee()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    if (!Auth::isHR() && !Auth::isOwner()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/contracts.php';
    exit;
}

if ($page === 'api_get_contracts') {
    require_once __DIR__ . '/../app/handlers/hr/get_contracts.php';
    exit;
}

if ($page === 'api_create_contract') {
    require_once __DIR__ . '/../app/handlers/hr/create_contract.php';
    exit;
}

if ($page === 'api_create_contract_from_interview') {
    require_once __DIR__ . '/../app/handlers/hr/create_contract_from_interview.php';
    exit;
}

if ($page === 'api_update_contract') {
    require_once __DIR__ . '/../app/handlers/hr/update_contract.php';
    exit;
}

// ============================================
// HR SCHEDULES ROUTES
// ============================================

if ($page === 'hr_schedules') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    $targetRole = Auth::getNormalizedTargetRole();
    $isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);
    if (!Auth::isHR() && !Auth::isOwner() && !$isHrTrainee) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/schedules.php';
    exit;
}

// ============================================
// HR ATTENDANCE ROUTES
// ============================================

if ($page === 'hr_attendance') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    $targetRole = Auth::getNormalizedTargetRole();
    $isHrTrainee = Auth::isTrainee() && in_array($targetRole, ['hr_head', 'hr_staff']);
    if (!Auth::isHR() && !Auth::isOwner() && !$isHrTrainee) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/attendance.php';
    exit;
}

if ($page === 'hr_attendance_review') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    $targetRole = Auth::getNormalizedTargetRole();
    $isHeadHrTrainee = Auth::isTrainee() && $targetRole === 'hr_head';
    if (!Auth::isHRHead() && !Auth::isOwner() && !$isHeadHrTrainee) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/attendance_review.php';
    exit;
}

// ============================================
// ATTENDANCE API ROUTES
// ============================================

if ($page === 'api_get_weeks_of_month') {
    require_once __DIR__ . '/../app/handlers/hr/get_weeks_of_month.php';
    exit;
}

if ($page === 'api_get_week_attendance') {
    require_once __DIR__ . '/../app/handlers/hr/get_week_attendance.php';
    exit;
}

if ($page === 'api_save_attendance') {
    require_once __DIR__ . '/../app/handlers/hr/save_attendance.php';
    exit;
}

if ($page === 'api_send_week_to_head_hr') {
    require_once __DIR__ . '/../app/handlers/hr/send_week_to_head_hr.php';
    exit;
}

if ($page === 'api_approve_week') {
    require_once __DIR__ . '/../app/handlers/hr/approve_week.php';
    exit;
}

if ($page === 'api_get_month_attendance') {
    require_once __DIR__ . '/../app/handlers/hr/get_month_attendance.php';
    exit;
}

if ($page === 'api_upload_dtr_image') {
    require_once __DIR__ . '/../app/handlers/hr/upload_dtr_image.php';
    exit;
}

if ($page === 'api_delete_dtr_image') {
    require_once __DIR__ . '/../app/handlers/hr/delete_dtr_image.php';
    exit;
}

// ============================================
// SCHEDULES & CONTRACT API
// ============================================

if ($page === 'api_get_schedule') {
    require_once __DIR__ . '/../app/handlers/hr/get_schedule.php';
    exit;
}

if ($page === 'api_save_schedule') {
    require_once __DIR__ . '/../app/handlers/hr/save_schedule.php';
    exit;
}

if ($page === 'api_get_employee_contract') {
    require_once __DIR__ . '/../app/handlers/hr/get_employee_contract.php';
    exit;
}

if ($page === 'api_sync_schedule_from_contract') {
    require_once __DIR__ . '/../app/handlers/hr/sync_schedule_from_contract.php';
    exit;
}

if ($page === 'api_get_all_employees') {
    require_once __DIR__ . '/../app/handlers/hr/get_all_employees.php';
    exit;
}

// ============================================
// PAYROLL ROUTES
// ============================================

if ($page === 'hr_payroll') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    $targetRole = Auth::getNormalizedTargetRole();
    $isHeadHrTrainee = Auth::isTrainee() && $targetRole === 'hr_head';
    if (!Auth::isHR() && !Auth::isOwner() && !$isHeadHrTrainee) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/payroll.php';
    exit;
}

if ($page === 'api_get_payroll_cycles') {
    require_once __DIR__ . '/../app/handlers/hr/get_payroll_cycles.php';
    exit;
}

if ($page === 'api_create_payroll_cycle') {
    require_once __DIR__ . '/../app/handlers/hr/create_payroll_cycle.php';
    exit;
}

if ($page === 'api_get_payroll_entries') {
    require_once __DIR__ . '/../app/handlers/hr/get_payroll_entries.php';
    exit;
}

if ($page === 'api_approve_payroll') {
    require_once __DIR__ . '/../app/handlers/hr/approve_payroll.php';
    exit;
}

if ($page === 'api_verify_payroll') {
    require_once __DIR__ . '/../app/handlers/hr/verify_payroll.php';
    exit;
}

if ($page === 'api_process_payroll') {
    require_once __DIR__ . '/../app/handlers/hr/process_payroll.php';
    exit;
}

if ($page === 'api_export_payroll') {
    require_once __DIR__ . '/../app/handlers/hr/export_payroll.php';
    exit;
}

if ($page === 'api_cancel_payroll') {
    require_once __DIR__ . '/../app/handlers/hr/cancel_payroll.php';
    exit;
}

// ============================================
// POS / employee PAGE ROUTES
// ============================================

if ($page === 'pos_dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isEmployee() && !Auth::isOwner() && !(Auth::isTrainee() && Auth::getTraineeTargetRole() === 'employee')) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/pos/dashboard.php';
    exit;
}

if ($page === 'pos_checkout') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isEmployee() && !Auth::isOwner()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/pos/checkout.php';
    exit;
}

if ($page === 'pos_orders') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isEmployee() && !Auth::isOwner()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/pos/orders.php';
    exit;
}

// ============================================
// POS API ROUTES
// ============================================

if ($page === 'api_get_products') {
    require_once __DIR__ . '/../app/handlers/pos/get_products.php';
    exit;
}
if ($page === 'api_get_product_by_barcode') {
    require_once __DIR__ . '/../app/handlers/pos/get_product_by_barcode.php';
    exit;
}
if ($page === 'api_create_order') {
    require_once __DIR__ . '/../app/handlers/pos/create_order.php';
    exit;
}
if ($page === 'api_get_order') {
    require_once __DIR__ . '/../app/handlers/pos/get_order.php';
    exit;
}
if ($page === 'api_get_orders') {
    require_once __DIR__ . '/../app/handlers/pos/get_orders.php';
    exit;
}
if ($page === 'api_get_daily_sales') {
    require_once __DIR__ . '/../app/handlers/pos/get_daily_sales.php';
    exit;
}
if ($page === 'api_void_order') {
    require_once __DIR__ . '/../app/handlers/pos/void_order.php';
    exit;
}   

// ============================================
// STORE MANAGER ROUTES
// ============================================

if ($page === 'store_manager_dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/store_manager/dashboard.php';
    exit;
}

if ($page === 'store_manager_requisitions') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/store_manager/requisitions.php';
    exit;
}

if ($page === 'api_store_manager_dashboard') {
    require_once __DIR__ . '/../app/handlers/store_manager/get_dashboard_stats.php';
    exit;
}

if ($page === 'api_get_requisitions') {
    require_once __DIR__ . '/../app/handlers/store_manager/get_requisitions.php';
    exit;
}

if ($page === 'api_create_requisition') {
    require_once __DIR__ . '/../app/handlers/store_manager/create_requisition.php';
    exit;
}

if ($page === 'api_get_requisition') {
    require_once __DIR__ . '/../app/handlers/store_manager/get_requisition.php';
    exit;
}

if ($page === 'api_send_requisition_to_supplier') {
    require_once __DIR__ . '/../app/handlers/store_manager/send_requisition_to_supplier.php';
    exit;
}

if ($page === 'api_receive_goods') {
    require_once __DIR__ . '/../app/handlers/store_manager/receive_goods.php';
    exit;
}

if ($page === 'api_forward_to_finance_staff') {
    require_once __DIR__ . '/../app/handlers/store_manager/forward_to_finance_staff.php';
    exit;
}

// ============================================
// STORE MANAGER - INVENTORY
// ============================================

if ($page === 'store_manager_inventory') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isStoreManager() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/store_manager/inventory.php';
    exit;
}

if ($page === 'api_store_manager_inventory') {
    require_once __DIR__ . '/../app/handlers/store_manager/get_inventory.php';
    exit;
}

// ============================================
// SUPPLIER ROUTES
// ============================================

if ($page === 'supplier_dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/supplier/dashboard.php';
    exit;
}

if ($page === 'supplier_requisitions') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/supplier/requisitions.php';
    exit;
}

if ($page === 'supplier_invoices') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/supplier/invoices.php';
    exit;
}

if ($page === 'supplier_products') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isSupplier() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/supplier/products.php';
    exit;
}

if ($page === 'api_supplier_dashboard') {
    require_once __DIR__ . '/../app/handlers/supplier/get_dashboard_stats.php';
    exit;
}

if ($page === 'api_supplier_get_requisitions') {
    require_once __DIR__ . '/../app/handlers/supplier/get_requisitions.php';
    exit;
}

if ($page === 'api_supplier_get_requisition') {
    require_once __DIR__ . '/../app/handlers/supplier/get_requisition.php';
    exit;
}

if ($page === 'api_supplier_process_requisition') {
    require_once __DIR__ . '/../app/handlers/supplier/process_requisition.php';
    exit;
}

if ($page === 'api_supplier_create_invoice') {
    require_once __DIR__ . '/../app/handlers/supplier/create_invoice.php';
    exit;
}

if ($page === 'api_supplier_get_invoices') {
    require_once __DIR__ . '/../app/handlers/supplier/get_invoices.php';
    exit;
}

if ($page === 'api_supplier_get_invoice') {
    require_once __DIR__ . '/../app/handlers/supplier/get_invoice.php';
    exit;
}

if ($page === 'api_supplier_get_products') {
    require_once __DIR__ . '/../app/handlers/supplier/get_supplier_products.php';
    exit;
}

if ($page === 'api_supplier_create_product') {
    require_once __DIR__ . '/../app/handlers/supplier/create_supplier_product.php';
    exit;
}

if ($page === 'api_supplier_update_product') {
    require_once __DIR__ . '/../app/handlers/supplier/update_supplier_product.php';
    exit;
}

if ($page === 'api_supplier_delete_product') {
    require_once __DIR__ . '/../app/handlers/supplier/delete_supplier_product.php';
    exit;
}

// ============================================
// SUPPLIER - SHIP GOODS
// ============================================

if ($page === 'api_supplier_ship_goods') {
    require_once __DIR__ . '/../app/handlers/supplier/ship_goods.php';
    exit;
}

// ============================================
// STORE MANAGER - ADDITIONAL ROUTES
// ============================================

if ($page === 'api_get_products_for_requisition') {
    require_once __DIR__ . '/../app/handlers/store_manager/get_products_for_requisition.php';
    exit;
}

// ============================================
// ✅ FINANCE MODULE ROUTES (NEW)
// ============================================

// --- Finance Staff ---
if ($page === 'finance_staff_dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/finance/staff/dashboard.php';
    exit;
}

if ($page === 'finance_staff_requisitions') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/finance/staff/requisitions.php';
    exit;
}

if ($page === 'finance_staff_payment_requests') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/finance/staff/payment_requests.php';
    exit;
}

if ($page === 'finance_staff_budget') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isFinanceStaff() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/finance/staff/budget.php';
    exit;
}

// --- Finance Head ---
if ($page === 'finance_head_dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/finance/head/dashboard.php';
    exit;
}

if ($page === 'finance_head_payment_requests') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/finance/head/payment_requests.php';
    exit;
}

if ($page === 'finance_head_budget') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/finance/head/budget.php';
    exit;
}

// --- Finance API Routes (Staff) ---
if ($page === 'api_finance_staff_dashboard_stats') {
    require_once __DIR__ . '/../app/handlers/finance/staff/get_dashboard_stats.php';
    exit;
}

if ($page === 'api_finance_get_pending_requisitions') {
    require_once __DIR__ . '/../app/handlers/finance/staff/get_pending_requisitions.php';
    exit;
}

if ($page === 'api_finance_get_requisition_detail') {
    require_once __DIR__ . '/../app/handlers/finance/staff/get_requisition_detail.php';
    exit;
}

if ($page === 'api_finance_create_payment_request') {
    require_once __DIR__ . '/../app/handlers/finance/staff/create_payment_request.php';
    exit;
}

if ($page === 'api_finance_staff_get_payment_requests') {
    require_once __DIR__ . '/../app/handlers/finance/staff/get_payment_requests.php';
    exit;
}

if ($page === 'api_finance_staff_record_payment') {
    require_once __DIR__ . '/../app/handlers/finance/staff/record_payment.php';
    exit;
}

if ($page === 'api_finance_staff_get_budget') {
    require_once __DIR__ . '/../app/handlers/finance/staff/get_budget_overview.php';
    exit;
}

// --- Finance API Routes (Head) ---
if ($page === 'api_finance_head_dashboard_stats') {
    require_once __DIR__ . '/../app/handlers/finance/head/get_dashboard_stats.php';
    exit;
}

if ($page === 'api_finance_get_payment_requests') {
    require_once __DIR__ . '/../app/handlers/finance/head/get_pending_payment_requests.php';
    exit;
}

if ($page === 'api_finance_approve_payment_request') {
    require_once __DIR__ . '/../app/handlers/finance/head/approve_payment_request.php';
    exit;
}

if ($page === 'api_finance_get_budget') {
    require_once __DIR__ . '/../app/handlers/finance/head/get_budget.php';
    exit;
}

if ($page === 'api_finance_set_budget') {
    require_once __DIR__ . '/../app/handlers/finance/head/set_budget.php';
    exit;
}

if ($page === 'api_finance_get_budget_adjustments') {
    require_once __DIR__ . '/../app/handlers/finance/head/get_budget_adjustments.php';
    exit;
}

// ============================================
// SHARED MODULE ROUTES
// ============================================

if ($page === 'my_payslip') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    require_once __DIR__ . '/../views/shared/payslip.php';
    exit;
}

if ($page === 'api_get_payslip') {
    require_once __DIR__ . '/../app/handlers/shared/get_payslip.php';
    exit;
}

if ($page === 'my_leaves') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    require_once __DIR__ . '/../views/shared/leave.php';
    exit;
}

if ($page === 'api_get_categories') {
    require_once __DIR__ . '/../app/handlers/shared/get_categories.php';
    exit;
}

if ($page === 'hr_leave_requests') {
    if (!Auth::check() || (!Auth::isHRHead() && !Auth::isOwner())) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/hr/leave_management.php';
    exit;
}

if ($page === 'api_get_leave_balances') {
    require_once __DIR__ . '/../app/handlers/shared/get_leave_balances.php';
    exit;
}

if ($page === 'api_get_leave_requests') {
    require_once __DIR__ . '/../app/handlers/shared/get_leave_requests.php';
    exit;
}

if ($page === 'api_create_leave_request') {
    require_once __DIR__ . '/../app/handlers/shared/create_leave_request.php';
    exit;
}

if ($page === 'api_update_leave_request') {
    require_once __DIR__ . '/../app/handlers/shared/update_leave_request.php';
    exit;
}

// ============================================
// PASSWORD RESET ROUTES (OTP Based)
// ============================================

if ($page === 'forgot_password') {
    require_once __DIR__ . '/../views/pages/auth/forgot_password.php';
    exit;
}

if ($page === 'api_request_password_reset') {
    require_once __DIR__ . '/../app/handlers/auth/request_password_reset.php';
    exit;
}

if ($page === 'api_verify_otp') {
    require_once __DIR__ . '/../app/handlers/auth/verify_otp.php';
    exit;
}

if ($page === 'api_reset_password') {
    require_once __DIR__ . '/../app/handlers/auth/reset_password.php';
    exit;
}

// ============================================
// TRAINEE ROUTES
// ============================================

if ($page === 'trainee_dashboard') {
    if (!Auth::check()) {
        Response::redirect('?page=login');
        exit;
    }
    if (!Auth::isTrainee() && !Auth::isOwner()) {
        Response::redirect('?page=dashboard');
        exit;
    }
    require_once __DIR__ . '/../views/pages/trainee/dashboard.php';
    exit;
}

if ($page === 'api_trainee_dashboard') {
    require_once __DIR__ . '/../app/handlers/trainee/get_dashboard_data.php';
    exit;
}

// ============================================
// 404 - Page Not Found (MUST be last)
// ============================================

http_response_code(404);
echo "<h1>404 - Page Not Found</h1>";
echo "<p>Page: " . htmlspecialchars($page) . "</p>";
echo "<p><a href='?page=home'>Go Home</a></p>";
exit;