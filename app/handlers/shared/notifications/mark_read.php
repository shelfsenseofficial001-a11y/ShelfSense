<?php
// app/handlers/shared/notifications/mark_read.php
// Marks either one notification (id, ownership-checked) or all of the
// current user's unread notifications ({"all": true}) as read.

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../helpers/functions.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

header('Content-Type: application/json');

if (!Auth::check()) {
    Response::unauthorized('Please login to access this resource');
}

$input = json_decode(file_get_contents('php://input'), true);
$all = !empty($input['all']);
$id = isset($input['id']) ? intval($input['id']) : 0;

if (!$all && $id <= 0) {
    Response::error('Invalid request', 400);
}

try {
    if ($all) {
        markAllNotificationsRead(Auth::userId());
        Response::success([], 'All notifications marked read');
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, Auth::userId()]);
        Response::success([], 'Notification marked read');
    }
} catch (Exception $e) {
    error_log('shared/notifications/mark_read.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
