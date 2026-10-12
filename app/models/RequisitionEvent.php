<?php
namespace App\Models;

use App\Core\Database;

/**
 * Timestamped history/comment thread for a requisition -- the Finance Head's
 * "Request Revision" reason and the Store Manager's resubmission notes live
 * here, same pattern as PoEvent for purchase orders.
 */
class RequisitionEvent
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log($requisitionId, $eventType, $message, $actorUserId = null, $role = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO requisition_events (requisition_id, event_type, message, actor_user_id, role)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$requisitionId, $eventType, $message, $actorUserId, $role]);
    }

    public function getForRequisition($requisitionId)
    {
        $stmt = $this->db->prepare("
            SELECT e.*, e.message as description, CONCAT(u.first_name, ' ', u.last_name) as actor_name
            FROM requisition_events e
            LEFT JOIN users u ON e.actor_user_id = u.user_id
            WHERE e.requisition_id = ?
            ORDER BY e.created_at ASC, e.id ASC
        ");
        $stmt->execute([$requisitionId]);
        return $stmt->fetchAll();
    }
}
