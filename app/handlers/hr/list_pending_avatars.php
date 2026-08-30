<?php
// app/handlers/hr/list_pending_avatars.php
// Owner-only: list users with a profile picture upload awaiting review.

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

if (!Auth::isOwner()) {
    Response::forbidden('Only the owner can review profile picture uploads');
}

$db = Database::getInstance()->getConnection();
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

Response::success(['pending' => $results, 'count' => count($results)]);
