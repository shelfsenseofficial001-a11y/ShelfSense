<?php
// app/handlers/hr/list_pending_avatars.php
// Owner-only: list users with a profile picture upload awaiting review.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

if (!function_exists('hr_pending_avatars_build_data')) {
/**
 * Builds the list of pending avatar uploads. Shared by the API endpoint
 * (for refresh) and the page itself (server-rendered first paint).
 */
function hr_pending_avatars_build_data(PDO $db): array {
    $stmt = $db->query("
        SELECT user_id, employee_number, first_name, last_name, role, profile_pic, pending_profile_pic
        FROM users
        WHERE pending_profile_pic_status = 'pending' AND pending_profile_pic IS NOT NULL
        ORDER BY updated_at ASC
    ");
    $rows = $stmt->fetchAll();

    $results = array_map(function ($row) {
        return [
            'user_id' => (int)$row['user_id'],
            'employee_number' => $row['employee_number'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'role' => $row['role'],
            'role_label' => getRoleName($row['role']),
            'current_profile_pic' => $row['profile_pic'],
            'pending_profile_pic' => $row['pending_profile_pic'],
        ];
    }, $rows);

    return ['pending' => $results, 'count' => count($results)];
}
}

if (!defined('SHELFSENSE_INTERNAL_INCLUDE')) {
    header('Content-Type: application/json');

    if (!Auth::check()) {
        Response::unauthorized('Please login to access this resource');
    }

    if (!Auth::isOwner()) {
        Response::forbidden('Only the owner can review profile picture uploads');
    }

    $db = Database::getInstance()->getConnection();
    Response::success(hr_pending_avatars_build_data($db));
}
