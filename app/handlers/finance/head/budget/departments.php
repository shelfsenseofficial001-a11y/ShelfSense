<?php
// app/handlers/finance/head/budget/departments.php
// GET: list departments. POST {action: create|toggle}: manage the department list.

require_once __DIR__ . '/../../../../core/Database.php';
require_once __DIR__ . '/../../../../core/Auth.php';
require_once __DIR__ . '/../../../../core/Response.php';
require_once __DIR__ . '/../../../../models/Budget.php';

use App\Core\Auth;
use App\Core\Response;
use App\Models\Budget;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}
if (!Auth::isFinanceHead() && !Auth::isFinanceStaff() && !Auth::isStoreManager() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied.');
}

$budgetModel = new Budget();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        Response::success(['departments' => $budgetModel->getAllDepartments(false)], 'Departments fetched successfully');
    } catch (Exception $e) {
        error_log('budget/departments.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
    exit;
}

if (!Auth::isFinanceHead() && !Auth::isSuperAdmin()) {
    Response::forbidden('Access denied. Finance Head role required to manage departments.');
}

$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? trim($input['action']) : '';

try {
    if ($action === 'create') {
        $name = isset($input['name']) ? trim($input['name']) : '';
        $code = isset($input['code']) ? trim($input['code']) : null;
        if ($name === '' || strlen($name) > 50) {
            Response::error('A department name (max 50 chars) is required', 400);
        }
        if ($budgetModel->getDepartmentByName($name)) {
            Response::error('A department with this name already exists', 400);
        }
        $id = $budgetModel->createDepartment($name, $code);
        Response::success(['id' => $id], 'Department created successfully');
    } elseif ($action === 'toggle') {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $active = !empty($input['is_active']);
        if ($id <= 0) {
            Response::error('Invalid department id', 400);
        }
        $budgetModel->setDepartmentActive($id, $active);
        Response::success(['id' => $id, 'is_active' => $active], 'Department updated successfully');
    } else {
        Response::error('Invalid action', 400);
    }
} catch (Exception $e) {
    error_log('budget/departments.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
