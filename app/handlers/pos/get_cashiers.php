<?php
// app/handlers/pos/get_cashiers.php
// Lists active cashiers (role=employee) for the POS "who's on shift" picker.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

if (!function_exists('pos_cashiers_build_data')) {
/**
 * Builds the "who's ringing up sales" picker list: hired cashiers +
 * Cashier-track trainees. Shared by the API endpoint and the Select
 * Cashier page's first paint.
 */
function pos_cashiers_build_data(PDO $db): array {
    $stmt = $db->query("
        SELECT user_id, first_name, last_name, employee_number, profile_pic
        FROM users
        WHERE role = 'employee' AND is_active = 1
        ORDER BY first_name ASC
    ");
    $cashiers = $stmt->fetchAll();

    // Trainees training for the Employee (cashier-facing) role can also
    // ring up sales under supervision -- shown as a separate group below
    // the hired cashiers. "Employee" is the current target_role label for
    // this track -- "Cashier" was the old label before it was renamed to
    // avoid a one-dimensional job title.
    $stmt = $db->query("
        SELECT u.user_id, u.first_name, u.last_name, u.employee_number, u.profile_pic
        FROM users u
        JOIN trainees t ON t.user_id = u.user_id
        WHERE u.role = 'trainee' AND u.is_active = 1
          AND t.status = 'active' AND t.target_role = 'Employee'
        ORDER BY u.first_name ASC
    ");
    $trainees = $stmt->fetchAll();

    return ['cashiers' => $cashiers, 'trainees' => $trainees];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::posCheck()) {
        Response::unauthorized('Please log in to a register first.');
    }

    try {
        $db = Database::getInstance()->getConnection();
        Response::success(pos_cashiers_build_data($db), 'Cashiers fetched');
    } catch (Exception $e) {
        error_log('get_cashiers.php error: ' . $e->getMessage());
        Response::error('Error: ' . $e->getMessage());
    }
}
