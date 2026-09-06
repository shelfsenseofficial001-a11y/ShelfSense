<?php
// app/handlers/shared/notifications/list.php
// Any authenticated user's own notification bell -- recent notifications
// plus an unread count, used to drive the bell/badge/dropdown in every
// portal layout.

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

$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 15;

try {
    $notifications = getNotifications(Auth::userId(), $limit);

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([Auth::userId()]);
    $unreadCount = (int)$stmt->fetch()['cnt'];

    Response::success([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ], 'Notifications fetched successfully');
} catch (Exception $e) {
    error_log('shared/notifications/list.php error: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage());
}
